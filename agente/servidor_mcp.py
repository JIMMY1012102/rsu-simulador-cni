"""Servidor MCP CNI: herramientas SELECT con identidad fijada por Laravel."""
import json
import os
import re
from datetime import date, datetime
from decimal import Decimal
from pathlib import Path

import mysql.connector
from dotenv import load_dotenv
from mcp.server.fastmcp import FastMCP

load_dotenv(Path(__file__).with_name(".env"))
mcp = FastMCP("Simulador CNI")
ROL = os.getenv("AGENTE_ROL", "")
USUARIO_ID = os.getenv("AGENTE_USUARIO_ID", "")


def normalizar(valor):
    if isinstance(valor, Decimal):
        return str(valor)
    if isinstance(valor, datetime):
        return valor.isoformat(sep=" ")
    if isinstance(valor, date):
        return valor.isoformat()
    if isinstance(valor, dict):
        return {k: normalizar(v) for k, v in valor.items()}
    if isinstance(valor, list):
        return [normalizar(v) for v in valor]
    return valor


def consultar(sql, params=()):
    """Ejecuta exclusivamente SELECT acotados y parametrizados."""
    if not re.match(r"^\s*SELECT\b", sql, flags=re.IGNORECASE):
        raise ValueError("El agente solo permite consultas SELECT.")
    if not re.search(r"\bLIMIT\s+\d+|\bLIMIT\s+%s", sql, flags=re.IGNORECASE):
        raise ValueError("Toda consulta MCP debe tener LIMIT.")
    cnx = mysql.connector.connect(
        host=os.environ["DB_HOST"],
        user=os.environ["DB_USER"],
        password=os.environ["DB_PASSWORD"],
        database=os.environ["DB_NAME"],
        charset="utf8mb4",
        connection_timeout=5,
        read_timeout=15,
    )
    try:
        cur = cnx.cursor(dictionary=True)
        cur.execute(sql, params)
        filas = cur.fetchall()
        cur.close()
        return json.dumps(normalizar(filas), ensure_ascii=False)
    finally:
        cnx.close()


def acotar_limite(numero, maximo=20):
    return max(1, min(int(numero), maximo))


def estudiante():
    if ROL != "estudiante" or not USUARIO_ID.isdigit() or int(USUARIO_ID) < 1:
        raise PermissionError("Rol o identidad de estudiante no autorizados.")


def admin():
    if ROL != "admin" or not USUARIO_ID.isdigit() or int(USUARIO_ID) < 1:
        raise PermissionError("Rol o identidad de administrador no autorizados.")


if ROL == "estudiante":
    # La identidad y el área se obtienen de usuarios; nunca se reciben del modelo.
    AREAS_DEL_ESTUDIANTE = """
        SELECT DISTINCT sa.area_id
        FROM usuarios u
        LEFT JOIN carreras c ON c.id = u.carrera_id
        JOIN area_postulacion_simulacro aps
          ON aps.area_postulacion_id = COALESCE(u.area_postulacion_id, c.area_postulacion_id)
        JOIN simulacro_area sa ON sa.simulacro_id = aps.simulacro_id
        JOIN simulacros s ON s.id = aps.simulacro_id
        WHERE u.id = %s AND u.rol = 'estudiante'
    """

    @mcp.tool(description=(
        "Consulta los últimos resultados CALIFICADOS del estudiante autenticado: "
        "fecha, simulacro, puntaje, correctas, incorrectas y en blanco. "
        "Úsala para resultados recientes, mejor/peor intento o comparar con el anterior."
    ))
    def consultar_mis_resultados(limite: int = 5) -> str:
        estudiante()
        return consultar(
            """SELECT i.id AS intento_id, i.tipo, i.fecha_inicio, i.fecha_fin,
                      i.puntaje_total, i.correctas, i.incorrectas, i.en_blanco,
                      i.total_preguntas, s.nombre AS simulacro
               FROM intentos i
               LEFT JOIN simulacros s ON s.id = i.simulacro_id
               WHERE i.usuario_id = %s
                 AND i.estado IN ('finalizado', 'enviado_auto')
                 AND i.puntaje_total IS NOT NULL
               ORDER BY COALESCE(i.fecha_fin, i.fecha_inicio) DESC, i.id DESC
               LIMIT %s""",
            (int(USUARIO_ID), acotar_limite(limite, 10)),
        )

    @mcp.tool(description=(
        "Consulta el rendimiento del estudiante por asignatura/área académica, "
        "con porcentaje promedio, intentos y cantidades de preguntas. "
        "Úsala para identificar fortalezas, debilidades y evolución por asignatura."
    ))
    def consultar_mi_rendimiento() -> str:
        estudiante()
        return consultar(
            """SELECT a.id AS area_id, a.nombre AS asignatura,
                      ROUND(AVG(ra.porcentaje), 2) AS promedio_porcentaje,
                      COUNT(DISTINCT i.id) AS simulacros_calificados,
                      SUM(ra.total_preguntas) AS preguntas_evaluadas,
                      SUM(ra.aciertos) AS aciertos,
                      SUM(ra.incorrectas) AS incorrectas,
                      SUM(ra.en_blanco) AS en_blanco
               FROM resultados_area ra
               JOIN intentos i ON i.id = ra.intento_id
               JOIN areas a ON a.id = ra.area_id
               WHERE i.usuario_id = %s
                 AND i.estado IN ('finalizado', 'enviado_auto')
                 AND i.puntaje_total IS NOT NULL
               GROUP BY a.id, a.nombre
               ORDER BY promedio_porcentaje ASC, a.nombre ASC
               LIMIT 20""",
            (int(USUARIO_ID),),
        )

    @mcp.tool(description=(
        "Consulta el avance por tema, incluyendo temas sin práctica, cantidad de preguntas "
        "disponibles, preguntas distintas respondidas, aciertos y porcentaje de acierto. "
        "Úsala para recomendar qué tema estudiar primero."
    ))
    def consultar_mi_progreso(limite: int = 15) -> str:
        estudiante()
        sql = f"""SELECT a.nombre AS asignatura, t.id AS tema_id, t.nombre AS tema,
                         COUNT(DISTINCT p.id) AS preguntas_disponibles,
                         COUNT(DISTINCT CASE WHEN i.puntaje_total IS NOT NULL THEN re.pregunta_id END) AS preguntas_respondidas,
                         SUM(CASE WHEN i.puntaje_total IS NOT NULL AND re.estado_respuesta = 'correcta' THEN 1 ELSE 0 END) AS aciertos,
                         SUM(CASE WHEN i.puntaje_total IS NOT NULL AND re.estado_respuesta = 'incorrecta' THEN 1 ELSE 0 END) AS incorrectas,
                         CASE WHEN SUM(CASE WHEN i.puntaje_total IS NOT NULL AND re.estado_respuesta IS NOT NULL THEN 1 ELSE 0 END) > 0
                              THEN ROUND(100.0 * SUM(CASE WHEN i.puntaje_total IS NOT NULL AND re.estado_respuesta = 'correcta' THEN 1 ELSE 0 END)
                                   / SUM(CASE WHEN i.puntaje_total IS NOT NULL AND re.estado_respuesta IS NOT NULL THEN 1 ELSE 0 END), 2)
                              ELSE NULL END AS porcentaje_acierto
                  FROM temas t
                  JOIN areas a ON a.id = t.area_id AND a.activo = 1
                  JOIN preguntas p ON p.tema_id = t.id AND p.activo = 1
                  LEFT JOIN respuestas_estudiante re ON re.pregunta_id = p.id
                  LEFT JOIN intentos i ON i.id = re.intento_id AND i.usuario_id = %s
                       AND i.estado IN ('finalizado', 'enviado_auto') AND i.puntaje_total IS NOT NULL
                  WHERE t.activo = 1
                    AND a.id IN ({AREAS_DEL_ESTUDIANTE})
                  GROUP BY a.id, a.nombre, t.id, t.nombre
                  ORDER BY CASE
                               WHEN SUM(CASE WHEN i.puntaje_total IS NOT NULL AND re.estado_respuesta IS NOT NULL THEN 1 ELSE 0 END) = 0 THEN 0
                               ELSE 1
                           END,
                           ROUND(
                               100.0 * SUM(CASE WHEN i.puntaje_total IS NOT NULL AND re.estado_respuesta = 'correcta' THEN 1 ELSE 0 END)
                               / NULLIF(SUM(CASE WHEN i.puntaje_total IS NOT NULL AND re.estado_respuesta IS NOT NULL THEN 1 ELSE 0 END), 0),
                               2
                           ) ASC,
                           COUNT(DISTINCT CASE WHEN i.puntaje_total IS NOT NULL THEN re.pregunta_id END) ASC,
                           t.nombre ASC
                  LIMIT %s"""
        return consultar(sql, (int(USUARIO_ID), int(USUARIO_ID), acotar_limite(limite, 20)))

    @mcp.tool(description=(
        "Consulta simulacros publicados para el área/carrera del estudiante. "
        "estado admite disponibles, proximos o historial. Devuelve fechas, duración, "
        "preguntas y, en historial, el resultado propio. Nunca muestra exámenes de otra área."
    ))
    def consultar_mis_simulacros(estado: str = "disponibles", limite: int = 10) -> str:
        estudiante()
        if estado not in ("disponibles", "proximos", "historial"):
            return json.dumps({"error": "El estado debe ser disponibles, proximos o historial."}, ensure_ascii=False)
        limite_sql = acotar_limite(limite, 20)
        filtro_historial = estado == "historial"
        filtro_fecha = (
            "i.usuario_id = %s AND i.tipo = 'simulacro' "
            "AND i.estado IN ('en_curso', 'finalizado', 'enviado_auto')"
            if filtro_historial else
            ("s.fecha_inicio > NOW()" if estado == "proximos" else
             "s.fecha_inicio <= NOW() AND s.fecha_fin >= NOW()")
        )
        sql = f"""SELECT s.id AS simulacro_id, s.nombre, s.fecha_inicio, s.fecha_fin,
                         s.tiempo_limite, s.total_preguntas, s.activo,
                         i.estado AS estado_intento, i.puntaje_total,
                         i.correctas, i.incorrectas, i.en_blanco
                  FROM simulacros s
                  JOIN area_postulacion_simulacro aps ON aps.simulacro_id = s.id
                  JOIN usuarios u ON u.id = %s AND u.rol = 'estudiante'
                  LEFT JOIN carreras c ON c.id = u.carrera_id
                  LEFT JOIN intentos i ON i.simulacro_id = s.id AND i.usuario_id = u.id
                  WHERE aps.area_postulacion_id = COALESCE(u.area_postulacion_id, c.area_postulacion_id)
                    AND s.activo = 1 AND {filtro_fecha}
                  ORDER BY s.fecha_inicio {'DESC' if filtro_historial else 'ASC'}
                  LIMIT %s"""
        params = (int(USUARIO_ID), int(USUARIO_ID), limite_sql) if filtro_historial else (int(USUARIO_ID), limite_sql)
        return consultar(sql, params)

    @mcp.tool(description=(
        "Busca preguntas académicas activas del banco en asignaturas habilitadas para el área "
        "del estudiante. Permite filtrar por tema, dificultad basico/intermedio/avanzado o texto. "
        "Por defecto NO revela solución ni clave; incluir_solucion=true solo cuando el alumno "
        "pida explicación o haya intentado responder. Máximo 10 preguntas."
    ))
    def consultar_banco_academico(
        tema: str = "",
        nivel: str = "",
        buscar: str = "",
        incluir_solucion: bool = False,
        limite: int = 5,
    ) -> str:
        estudiante()
        if nivel and nivel not in ("basico", "intermedio", "avanzado"):
            return json.dumps({"error": "Nivel no válido."}, ensure_ascii=False)
        tema = str(tema or "").strip()[:100]
        buscar = str(buscar or "").strip()[:120]
        if not tema and not buscar:
            return json.dumps({"necesita_dato": "Indica una asignatura, tema o concepto para buscar preguntas."}, ensure_ascii=False)

        filtros = [
            "p.activo = 1", "t.activo = 1", "a.activo = 1",
            f"a.id IN ({AREAS_DEL_ESTUDIANTE})",
        ]
        parametros = []
        if tema:
            filtros.append("t.nombre LIKE %s")
            parametros.append(f"%{tema}%")
        if nivel:
            filtros.append("p.nivel = %s")
            parametros.append(nivel)
        if buscar:
            filtros.append("(p.enunciado LIKE %s OR t.nombre LIKE %s)")
            parametros.extend([f"%{buscar}%", f"%{buscar}%"])

        solucion_select = (
            "p.solucion AS solucion, "
            "(SELECT GROUP_CONCAT(CONCAT(ac.letra, '. ', ac.texto) ORDER BY ac.letra SEPARATOR ' | ') "
            " FROM alternativas ac WHERE ac.pregunta_id = p.id AND ac.es_correcta = 1 LIMIT 26) AS respuesta_correcta"
            if incluir_solucion else
            "NULL AS solucion, NULL AS respuesta_correcta"
        )
        sql = f"""SELECT p.id AS pregunta_id, a.nombre AS asignatura, t.nombre AS tema,
                         p.nivel, p.enunciado,
                         (SELECT GROUP_CONCAT(CONCAT(al.letra, '. ', al.texto) ORDER BY al.letra SEPARATOR ' | ')
                          FROM alternativas al WHERE al.pregunta_id = p.id LIMIT 26) AS alternativas,
                         {solucion_select}
                  FROM preguntas p
                  JOIN temas t ON t.id = p.tema_id
                  JOIN areas a ON a.id = t.area_id
                  WHERE {' AND '.join(filtros)}
                  ORDER BY CASE p.nivel WHEN 'basico' THEN 1 WHEN 'intermedio' THEN 2 ELSE 3 END,
                           p.id DESC
                  LIMIT %s"""
        parametros = [int(USUARIO_ID)] + parametros + [acotar_limite(limite, 10)]
        return consultar(sql, tuple(parametros))

elif ROL == "admin":
    @mcp.tool(description="Úsala para un resumen global de usuarios activos por rol, simulacros y preguntas activos e intentos calificados.")
    def resumen_general() -> str:
        admin()
        return json.dumps({
            "usuarios_activos_por_rol": json.loads(consultar(
                "SELECT rol, COUNT(*) total FROM usuarios WHERE activo=1 GROUP BY rol LIMIT 10"
            )),
            "simulacros_activos": json.loads(consultar(
                "SELECT COUNT(*) total FROM simulacros WHERE activo=1 LIMIT 1"
            )),
            "preguntas_activas": json.loads(consultar(
                "SELECT COUNT(*) total FROM preguntas WHERE activo=1 LIMIT 1"
            )),
            "intentos_calificados": json.loads(consultar(
                "SELECT COUNT(*) total FROM intentos WHERE estado IN ('finalizado','enviado_auto') "
                "AND puntaje_total IS NOT NULL LIMIT 1"
            )),
        }, ensure_ascii=False)

    @mcp.tool(description="Úsala para conocer la cantidad de preguntas activas agrupadas por área y nivel de dificultad.")
    def estado_del_banco_de_preguntas() -> str:
        admin()
        return consultar("""SELECT a.nombre AS area,p.nivel,COUNT(*) AS preguntas_activas
            FROM preguntas p JOIN temas t ON t.id=p.tema_id JOIN areas a ON a.id=t.area_id
            WHERE p.activo=1 GROUP BY a.id,a.nombre,p.nivel ORDER BY a.nombre,p.nivel LIMIT 100""")

    @mcp.tool(description="Úsala para revisar simulacros recientes, fechas, estado, número de intentos y puntaje promedio.")
    def simulacros_recientes(limite: int = 5) -> str:
        admin()
        return consultar("""SELECT s.nombre,s.fecha_inicio,s.fecha_fin,s.activo,
            COUNT(i.id) cantidad_intentos,AVG(i.puntaje_total) puntaje_promedio
            FROM simulacros s LEFT JOIN intentos i ON i.simulacro_id=s.id
            AND i.estado IN ('finalizado','enviado_auto') AND i.puntaje_total IS NOT NULL
            GROUP BY s.id,s.nombre,s.fecha_inicio,s.fecha_fin,s.activo
            ORDER BY s.fecha_inicio DESC LIMIT %s""", (acotar_limite(limite),))

    @mcp.tool(description="Úsala para comparar el promedio del último intento calificado de simulacro por sección, considerando estudiantes activos.")
    def rendimiento_por_seccion() -> str:
        admin()
        return consultar("""SELECT se.nombre AS seccion,se.grado,AVG(ult.puntaje_total) puntaje_promedio,
            COUNT(ult.intento_id) estudiantes_con_resultado
            FROM secciones se JOIN usuarios u ON u.seccion_id=se.id AND u.rol='estudiante' AND u.activo=1
            LEFT JOIN (SELECT i.id intento_id,i.usuario_id,i.puntaje_total FROM intentos i JOIN
                (SELECT usuario_id,MAX(COALESCE(fecha_fin,fecha_inicio)) ultima_fecha
                 FROM intentos WHERE tipo='simulacro' AND estado IN ('finalizado','enviado_auto')
                 AND puntaje_total IS NOT NULL GROUP BY usuario_id) x
              ON x.usuario_id=i.usuario_id AND x.ultima_fecha=COALESCE(i.fecha_fin,i.fecha_inicio)
              WHERE i.tipo='simulacro' AND i.estado IN ('finalizado','enviado_auto')
                AND i.puntaje_total IS NOT NULL) ult ON ult.usuario_id=u.id
            WHERE se.activo=1 GROUP BY se.id,se.nombre,se.grado
            ORDER BY puntaje_promedio LIMIT 100""")

    @mcp.tool(description="Úsala para identificar preguntas con menor porcentaje de acierto; solo incluye preguntas con cinco o más respuestas calificadas.")
    def preguntas_mas_falladas(limite: int = 10) -> str:
        admin()
        return consultar("""SELECT p.id,LEFT(p.enunciado,120) enunciado,a.nombre area,t.nombre tema,
            COUNT(*) respuestas,SUM(CASE WHEN re.estado_respuesta='correcta' THEN 1 ELSE 0 END) aciertos,
            ROUND(100.0*SUM(CASE WHEN re.estado_respuesta='correcta' THEN 1 ELSE 0 END)/COUNT(*),2) porcentaje_acierto
            FROM respuestas_estudiante re JOIN intentos i ON i.id=re.intento_id
            JOIN preguntas p ON p.id=re.pregunta_id JOIN temas t ON t.id=p.tema_id
            JOIN areas a ON a.id=t.area_id
            WHERE i.estado IN ('finalizado','enviado_auto') AND i.puntaje_total IS NOT NULL
              AND re.estado_respuesta IS NOT NULL
            GROUP BY p.id,p.enunciado,a.nombre,t.nombre HAVING COUNT(*)>=5
            ORDER BY porcentaje_acierto ASC LIMIT %s""", (acotar_limite(limite),))
else:
    raise RuntimeError("Rol no autorizado")


if __name__ == "__main__":
    mcp.run()
