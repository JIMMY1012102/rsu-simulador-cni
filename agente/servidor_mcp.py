"""Servidor MCP: consultas de solo lectura con identidad fijada por el proceso."""
import json, os
from datetime import date, datetime
from decimal import Decimal
from pathlib import Path
import mysql.connector
from dotenv import load_dotenv
from mcp.server.fastmcp import FastMCP

load_dotenv(Path(__file__).with_name(".env"))
mcp = FastMCP("Simulador CNI")
ROL, USUARIO_ID = os.getenv("AGENTE_ROL", ""), os.getenv("AGENTE_USUARIO_ID", "")

def normalizar(v):
    if isinstance(v, Decimal): return str(v)
    if isinstance(v, datetime): return v.isoformat(sep=" ")
    if isinstance(v, date): return v.isoformat()
    if isinstance(v, dict): return {k: normalizar(x) for k, x in v.items()}
    if isinstance(v, list): return [normalizar(x) for x in v]
    return v

def consultar(sql, params=()):
    cnx = mysql.connector.connect(host=os.environ["DB_HOST"], user=os.environ["DB_USER"],
        password=os.environ["DB_PASSWORD"], database=os.environ["DB_NAME"], charset="utf8mb4")
    try:
        cur = cnx.cursor(dictionary=True); cur.execute(sql, params)
        rows = cur.fetchall(); cur.close()
        return json.dumps(normalizar(rows), ensure_ascii=False)
    finally: cnx.close()

def limite(n): return max(1, min(int(n), 20))
def estudiante():
    if ROL != "estudiante" or not USUARIO_ID.isdigit(): raise PermissionError("Rol no autorizado.")
def admin():
    if ROL != "admin": raise PermissionError("Rol no autorizado.")

if ROL == "estudiante":
    @mcp.tool(description="Úsala para consultar resultados, puntajes, aciertos y errores recientes del estudiante autenticado.")
    def mis_ultimos_resultados(limite_resultados: int = 5) -> str:
        estudiante()
        return consultar("""SELECT i.tipo,i.fecha_inicio,i.fecha_fin,i.puntaje_total,i.correctas,i.incorrectas,i.en_blanco,s.nombre AS simulacro
        FROM intentos i LEFT JOIN simulacros s ON s.id=i.simulacro_id
        WHERE i.usuario_id=%s AND i.estado IN ('finalizado','enviado_auto') AND i.puntaje_total IS NOT NULL
        ORDER BY COALESCE(i.fecha_fin,i.fecha_inicio) DESC LIMIT %s""",(int(USUARIO_ID),limite(limite_resultados)))

    @mcp.tool(description="Úsala cuando pregunte por su rendimiento o promedio por área; ordena desde el área de menor porcentaje.")
    def mi_rendimiento_por_area() -> str:
        estudiante()
        return consultar("""SELECT a.nombre AS area,ROUND(AVG(ra.porcentaje),2) AS promedio_porcentaje
        FROM resultados_area ra JOIN intentos i ON i.id=ra.intento_id JOIN areas a ON a.id=ra.area_id
        WHERE i.usuario_id=%s AND i.estado IN ('finalizado','enviado_auto') AND i.puntaje_total IS NOT NULL
        GROUP BY a.id,a.nombre ORDER BY promedio_porcentaje ASC LIMIT 50""",(int(USUARIO_ID),))

    @mcp.tool(description="Úsala para encontrar temas que debe reforzar y porcentaje de aciertos; exige al menos tres respuestas calificadas por tema.")
    def mis_temas_a_reforzar(limite_resultados: int = 5) -> str:
        estudiante()
        return consultar("""SELECT a.nombre AS area,t.nombre AS tema,COUNT(*) AS respuestas,
        SUM(CASE WHEN re.estado_respuesta='correcta' THEN 1 ELSE 0 END) AS aciertos,
        ROUND(100.0*SUM(CASE WHEN re.estado_respuesta='correcta' THEN 1 ELSE 0 END)/COUNT(*),2) AS porcentaje_acierto
        FROM respuestas_estudiante re JOIN intentos i ON i.id=re.intento_id JOIN preguntas p ON p.id=re.pregunta_id
        JOIN temas t ON t.id=p.tema_id JOIN areas a ON a.id=t.area_id
        WHERE i.usuario_id=%s AND i.estado IN ('finalizado','enviado_auto') AND i.puntaje_total IS NOT NULL
        AND re.estado_respuesta IS NOT NULL GROUP BY a.id,a.nombre,t.id,t.nombre HAVING COUNT(*)>=3
        ORDER BY porcentaje_acierto ASC LIMIT %s""",(int(USUARIO_ID),limite(limite_resultados)))

    @mcp.tool(description="Úsala cuando consulte los simulacros que puede rendir en este momento según vigencia, estado y carrera.")
    def simulacros_disponibles() -> str:
        estudiante()
        return consultar("""SELECT s.nombre,s.fecha_inicio,s.fecha_fin,s.tiempo_limite,s.total_preguntas
        FROM simulacros s JOIN usuarios u ON u.id=%s WHERE s.activo=1 AND NOW() BETWEEN s.fecha_inicio AND s.fecha_fin
        AND (s.carrera_id IS NULL OR s.carrera_id=u.carrera_id) ORDER BY s.fecha_inicio LIMIT 50""",(int(USUARIO_ID),))
elif ROL == "admin":
    @mcp.tool(description="Úsala para un resumen global de usuarios activos por rol, simulacros y preguntas activos e intentos calificados.")
    def resumen_general() -> str:
        admin()
        return json.dumps({"usuarios_activos_por_rol":json.loads(consultar("SELECT rol,COUNT(*) total FROM usuarios WHERE activo=1 GROUP BY rol LIMIT 10")),
          "simulacros_activos":json.loads(consultar("SELECT COUNT(*) total FROM simulacros WHERE activo=1 LIMIT 1")),
          "preguntas_activas":json.loads(consultar("SELECT COUNT(*) total FROM preguntas WHERE activo=1 LIMIT 1")),
          "intentos_calificados":json.loads(consultar("SELECT COUNT(*) total FROM intentos WHERE estado IN ('finalizado','enviado_auto') AND puntaje_total IS NOT NULL LIMIT 1"))},ensure_ascii=False)

    @mcp.tool(description="Úsala para conocer la cantidad de preguntas activas agrupadas por área y nivel de dificultad.")
    def estado_del_banco_de_preguntas() -> str:
        admin()
        return consultar("""SELECT a.nombre AS area,p.nivel,COUNT(*) AS preguntas_activas FROM preguntas p
        JOIN temas t ON t.id=p.tema_id JOIN areas a ON a.id=t.area_id WHERE p.activo=1
        GROUP BY a.id,a.nombre,p.nivel ORDER BY a.nombre,p.nivel LIMIT 100""")

    @mcp.tool(description="Úsala para revisar simulacros recientes, fechas, estado, número de intentos y puntaje promedio.")
    def simulacros_recientes(limite_resultados: int = 5) -> str:
        admin()
        return consultar("""SELECT s.nombre,s.fecha_inicio,s.fecha_fin,s.activo,COUNT(i.id) cantidad_intentos,
        AVG(i.puntaje_total) puntaje_promedio FROM simulacros s LEFT JOIN intentos i ON i.simulacro_id=s.id
        AND i.estado IN ('finalizado','enviado_auto') AND i.puntaje_total IS NOT NULL
        GROUP BY s.id,s.nombre,s.fecha_inicio,s.fecha_fin,s.activo ORDER BY s.fecha_inicio DESC LIMIT %s""",(limite(limite_resultados),))

    @mcp.tool(description="Úsala para comparar el promedio del último intento calificado de simulacro por sección, considerando estudiantes activos.")
    def rendimiento_por_seccion() -> str:
        admin()
        return consultar("""SELECT se.nombre AS seccion,se.grado,AVG(ult.puntaje_total) puntaje_promedio,
        COUNT(ult.intento_id) estudiantes_con_resultado FROM secciones se
        JOIN usuarios u ON u.seccion_id=se.id AND u.rol='estudiante' AND u.activo=1
        LEFT JOIN (SELECT i.id intento_id,i.usuario_id,i.puntaje_total FROM intentos i JOIN
        (SELECT usuario_id,MAX(COALESCE(fecha_fin,fecha_inicio)) ultima_fecha FROM intentos WHERE tipo='simulacro'
        AND estado IN ('finalizado','enviado_auto') AND puntaje_total IS NOT NULL GROUP BY usuario_id) x
        ON x.usuario_id=i.usuario_id AND x.ultima_fecha=COALESCE(i.fecha_fin,i.fecha_inicio)
        WHERE i.tipo='simulacro' AND i.estado IN ('finalizado','enviado_auto') AND i.puntaje_total IS NOT NULL) ult
        ON ult.usuario_id=u.id WHERE se.activo=1 GROUP BY se.id,se.nombre,se.grado ORDER BY puntaje_promedio LIMIT 100""")

    @mcp.tool(description="Úsala para identificar preguntas con menor porcentaje de acierto; solo incluye preguntas con cinco o más respuestas calificadas.")
    def preguntas_mas_falladas(limite_resultados: int = 10) -> str:
        admin()
        return consultar("""SELECT p.id,LEFT(p.enunciado,120) enunciado,a.nombre area,t.nombre tema,COUNT(*) respuestas,
        SUM(CASE WHEN re.estado_respuesta='correcta' THEN 1 ELSE 0 END) aciertos,
        ROUND(100.0*SUM(CASE WHEN re.estado_respuesta='correcta' THEN 1 ELSE 0 END)/COUNT(*),2) porcentaje_acierto
        FROM respuestas_estudiante re JOIN intentos i ON i.id=re.intento_id JOIN preguntas p ON p.id=re.pregunta_id
        JOIN temas t ON t.id=p.tema_id JOIN areas a ON a.id=t.area_id
        WHERE i.estado IN ('finalizado','enviado_auto') AND i.puntaje_total IS NOT NULL AND re.estado_respuesta IS NOT NULL
        GROUP BY p.id,p.enunciado,a.nombre,t.nombre HAVING COUNT(*)>=5 ORDER BY porcentaje_acierto ASC LIMIT %s""",(limite(limite_resultados),))
else:
    raise RuntimeError("Rol no autorizado")

if __name__ == "__main__": mcp.run()
