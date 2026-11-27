"""Puente FastAPI para Gemini y las herramientas MCP."""
import asyncio, os, traceback
from contextlib import AsyncExitStack
from pathlib import Path
from dotenv import load_dotenv
from fastapi import FastAPI, Header, HTTPException
from google import genai
from google.genai import errors, types
from mcp import ClientSession, StdioServerParameters
from mcp.client.stdio import stdio_client
from pydantic import BaseModel, Field

load_dotenv(Path(__file__).with_name(".env"))
app=FastAPI(title="Agente IA CNI")
historiales={}
MODELO=os.getenv("MODELO") or "gemini-3.5-flash-lite"
MODELO_FALLBACK=os.getenv("MODELO_FALLBACK")
MODELOS_RESPALDO=os.getenv("MODELOS_RESPALDO")
ERRORES_TRANSITORIOS={429, 500, 502, 503, 504}

class Solicitud(BaseModel):
    sesion: str = Field(min_length=1,max_length=120)
    mensaje: str = Field(min_length=1,max_length=500)
    rol: str
    usuario_id: int

def recortar(historial):
    datos=historial[-11:]
    while datos and datos[0]["role"]!="user": datos.pop(0)
    return datos

def obtener_modelos_respaldo():
    if MODELOS_RESPALDO is not None:
        candidatos=MODELOS_RESPALDO.split(",")
    elif MODELO_FALLBACK:
        candidatos=[MODELO_FALLBACK]
    else:
        candidatos=["gemini-3.6-flash","gemini-3.5-flash"]
    modelos=[]
    for candidato in candidatos:
        nombre=candidato.strip()
        if nombre and nombre not in modelos and nombre != MODELO:
            modelos.append(nombre)
    return modelos

def generar_con_respaldo(cliente, contenido, configuracion):
    modelos=[MODELO]+obtener_modelos_respaldo()
    ultimo_error=None
    for indice, modelo in enumerate(modelos):
        try:
            return cliente.models.generate_content(
                model=modelo, contents=contenido, config=configuracion
            )
        except errors.APIError as error:
            if error.code not in ERRORES_TRANSITORIOS:
                raise
            ultimo_error=error
            if indice < len(modelos)-1:
                siguiente=modelos[indice+1]
                print(
                    f"Gemini modelo {modelo} no disponible (HTTP {error.code}); "
                    f"probando respaldo {siguiente}.",
                    flush=True,
                )
    raise ultimo_error

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
            configuracion=types.GenerateContentConfig(system_instruction=instrucciones,tools=[herramienta_mcp],temperature=0.2)
            respuesta=await asyncio.to_thread(generar_con_respaldo,cliente,contenido,configuracion)
            for _ in range(5):
                llamadas=[p.function_call for p in (respuesta.candidates[0].content.parts if respuesta.candidates else []) if p.function_call]
                if not llamadas: break
                partes=[]
                for llamada in llamadas:
                    resultado=await sesion_mcp.call_tool(llamada.name,llamada.args or {})
                    partes.append(types.Part.from_function_response(name=llamada.name,response={"resultado":"\n".join(c.text for c in resultado.content if hasattr(c,"text"))}))
                contenido.extend([respuesta.candidates[0].content,types.Content(role="user",parts=partes)])
                respuesta=await asyncio.to_thread(generar_con_respaldo,cliente,contenido,configuracion)
            texto=respuesta.text or "No pude completar la consulta. Inténtalo nuevamente."
        historial.append({"role":"model","parts":[{"text":texto}]})
        historiales[clave]=recortar(historial)
        return {"respuesta":texto}
    except Exception:
        print("Error del agente; traceback completo:", flush=True)
        traceback.print_exc()
        return {"respuesta":"Lo siento, no pude consultar la información en este momento. Inténtalo nuevamente en un minuto."}
