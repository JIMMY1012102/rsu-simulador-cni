"""Puente FastAPI para Gemini y las herramientas MCP."""
import asyncio, json, os
from contextlib import AsyncExitStack
from pathlib import Path
from dotenv import load_dotenv
from fastapi import FastAPI, Header, HTTPException
from google import genai
from google.genai import types
from mcp import ClientSession, StdioServerParameters
from mcp.client.stdio import stdio_client
from pydantic import BaseModel, Field

load_dotenv(Path(__file__).with_name(".env"))
app=FastAPI(title="Agente IA CNI")
historiales={}
MODELO=os.getenv("MODELO") or "gemini-2.5-flash"

class Solicitud(BaseModel):
    sesion: str = Field(min_length=1,max_length=120)
    mensaje: str = Field(min_length=1,max_length=500)
    rol: str
    usuario_id: int

def recortar(historial):
    datos=historial[-11:]
    while datos and datos[0]["role"]!="user": datos.pop(0)
    return datos

@app.post("/chat")
async def chat(datos: Solicitud, x_agente_token: str|None=Header(default=None)):
    token=os.getenv("AGENTE_TOKEN","")
    if not token or x_agente_token!=token: raise HTTPException(status_code=401,detail="No autorizado")
    if datos.rol not in ("estudiante","admin") or datos.usuario_id<1: raise HTTPException(status_code=403,detail="Rol no autorizado")
    clave=f"{datos.rol}:{datos.usuario_id}:{datos.sesion}"
    historial=historiales.setdefault(clave,[])
    historial.append({"role":"user","parts":[{"text":datos.mensaje}]})
    historial=recortar(historial)
    try:
        archivo="instrucciones_estudiante.txt" if datos.rol=="estudiante" else "instrucciones_admin.txt"
        instrucciones=Path(__file__).with_name(archivo).read_text(encoding="utf-8")
        entorno=os.environ.copy()
        entorno.update({"AGENTE_ROL":datos.rol,"AGENTE_USUARIO_ID":str(datos.usuario_id)})
        parametros=StdioServerParameters(command=os.getenv("PYTHON_EXECUTABLE","python"),
            args=[str(Path(__file__).with_name("servidor_mcp.py"))],env=entorno)
        cliente=genai.Client(api_key=os.environ["GEMINI_API_KEY"])
        async with AsyncExitStack() as pila:
            lector,escritor=await pila.enter_async_context(stdio_client(parametros))
            sesion_mcp=await pila.enter_async_context(ClientSession(lector,escritor))
            await sesion_mcp.initialize()
            disponibles=await sesion_mcp.list_tools()
            declaraciones=[types.FunctionDeclaration(name=h.name,description=h.description or "",parameters=h.inputSchema) for h in disponibles.tools]
            herramienta_mcp=types.Tool(function_declarations=declaraciones)
            contenido=[types.Content(role=m["role"],parts=[types.Part.from_text(text=p["text"]) for p in m["parts"]]) for m in historial]
            respuesta=await asyncio.to_thread(cliente.models.generate_content,model=MODELO,contents=contenido,
                config=types.GenerateContentConfig(system_instruction=instrucciones,tools=[herramienta_mcp],temperature=0.2))
            for _ in range(5):
                llamadas=[p.function_call for p in (respuesta.candidates[0].content.parts if respuesta.candidates else []) if p.function_call]
                if not llamadas: break
                partes=[]
                for llamada in llamadas:
                    resultado=await sesion_mcp.call_tool(llamada.name,llamada.args or {})
                    partes.append(types.Part.from_function_response(name=llamada.name,response={"resultado":"\n".join(c.text for c in resultado.content if hasattr(c,"text"))}))
                contenido.extend([respuesta.candidates[0].content,types.Content(role="user",parts=partes)])
                respuesta=await asyncio.to_thread(cliente.models.generate_content,model=MODELO,contents=contenido,
                    config=types.GenerateContentConfig(system_instruction=instrucciones,tools=[herramienta_mcp],temperature=0.2))
            texto=respuesta.text or "No pude completar la consulta. Inténtalo nuevamente."
        historial.append({"role":"model","parts":[{"text":texto}]})
        historiales[clave]=recortar(historial)
        return {"respuesta":texto}
    except Exception:
        return {"respuesta":"Lo siento, no pude consultar la información en este momento. Inténtalo nuevamente en un minuto."}
