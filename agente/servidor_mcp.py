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
        port=int(os.getenv("DB_PORT", "3306")),
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
    @mcp.tool(description=(
        "Panel ejecutivo del superadministrador: concentra usuarios activos/inactivos por rol, "
        "catálogo, banco, simulacros, intentos y actividad reciente. Úsala primero ante "
        "saludos como '¿cómo está la plataforma?', 'dame un panorama' o '¿qué debo atender hoy?'."
    ))
    def panel_superadmin() -> str:
        admin()
        datos = {
            "usuarios_por_rol_y_estado": json.loads(consultar(
                "SELECT rol, activo, COUNT(*) total FROM usuarios GROUP BY rol, activo ORDER BY rol, activo LIMIT 20"
            )),
            "catalogo": json.loads(consultar(
                """SELECT
                    (SELECT COUNT(*) FROM areas WHERE activo=1) asignaturas_activas,
                    (SELECT COUNT(*) FROM temas WHERE activo=1) temas_activos,
                    (SELECT COUNT(*) FROM preguntas WHERE activo=1) preguntas_activas,
                    (SELECT COUNT(*) FROM alternativas) alternativas_totales
                  LIMIT 1"""
            )),
            "simulacros": json.loads(consultar(
                """SELECT
                    SUM(CASE WHEN activo=1 AND fecha_inicio<=NOW() AND fecha_fin>=NOW() THEN 1 ELSE 0 END) vigentes,
                    SUM(CASE WHEN activo=1 AND fecha_inicio>NOW() THEN 1 ELSE 0 END) proximos,
                    SUM(CASE WHEN fecha_fin<NOW() THEN 1 ELSE 0 END) finalizados_o_vencidos,
                    COUNT(*) total
                  FROM simulacros LIMIT 1"""
            )),
            "intentos": json.loads(consultar(
                """SELECT estado, tipo, COUNT(*) total,
                    ROUND(AVG(CASE WHEN puntaje_total IS NOT NULL THEN puntaje_total END),3) promedio_puntaje
                  FROM intentos GROUP BY estado,tipo ORDER BY estado,tipo LIMIT 20"""
            )),
            "actividad_ultimos_7_dias": json.loads(consultar(
                """SELECT COUNT(*) intentos_iniciados,
                    COUNT(DISTINCT usuario_id) usuarios_participantes,
                    SUM(CASE WHEN estado IN ('finalizado','enviado_auto') AND puntaje_total IS NOT NULL THEN 1 ELSE 0 END) intentos_calificados
                  FROM intentos WHERE fecha_inicio>=DATE_SUB(NOW(),INTERVAL 7 DAY) LIMIT 1"""
            )),
            "preguntas_con_problemas": json.loads(consultar(
                """SELECT COUNT(*) total FROM (
                    SELECT p.id FROM preguntas p
                    LEFT JOIN alternativas al ON al.pregunta_id=p.id
                    GROUP BY p.id
                    HAVING COUNT(al.id)<>4 OR SUM(CASE WHEN al.es_correcta=1 THEN 1 ELSE 0 END)<>1
                  ) incidencias LIMIT 1"""
            )),
        }
        return json.dumps(datos, ensure_ascii=False)

    @mcp.tool(description=(
        "Busca usuarios por nombre, apellido, nombre de usuario o ID. Permite filtrar por rol "
        "(admin/docente/estudiante) y estado (todos/activos/inactivos). Nunca devuelve contraseñas, "
        "hashes ni tokens. Úsala para localizar cuentas y revisar su adscripción."
    ))
    def buscar_usuarios(buscar: str, rol: str = "", estado: str = "todos", limite: int = 10) -> str:
        admin()
        buscar = str(buscar or "").strip()[:100]
        if len(buscar) < 2:
            return json.dumps({"necesita_dato": "Escribe al menos dos caracteres del nombre, apellido, usuario o ID."}, ensure_ascii=False)
        if rol and rol not in ("admin", "docente", "estudiante"):
            return json.dumps({"error": "Rol no válido."}, ensure_ascii=False)
        if estado not in ("todos", "activos", "inactivos"):
            return json.dumps({"error": "Estado debe ser todos, activos o inactivos."}, ensure_ascii=False)
        filtros = ["(u.nombres LIKE %s OR u.apellidos LIKE %s OR u.usuario LIKE %s OR CAST(u.id AS CHAR)=%s)"]
        patron = f"%{buscar}%"
        params = [patron, patron, patron, buscar]
        if rol:
            filtros.append("u.rol=%s")
            params.append(rol)
        if estado != "todos":
            filtros.append("u.activo=%s")
            params.append(1 if estado == "activos" else 0)
        params.append(acotar_limite(limite, 25))
        return consultar(
            f"""SELECT u.id,u.nombres,u.apellidos,u.usuario,u.rol,u.activo,u.created_at,
                       se.nombre AS seccion,se.grado,ca.nombre AS carrera,ap.codigo AS area_postulacion
                FROM usuarios u
                LEFT JOIN secciones se ON se.id=u.seccion_id
                LEFT JOIN carreras ca ON ca.id=u.carrera_id
                LEFT JOIN areas_postulacion ap ON ap.id=COALESCE(u.area_postulacion_id,ca.area_postulacion_id)
                WHERE {' AND '.join(filtros)}
                ORDER BY u.activo DESC,u.rol,u.apellidos,u.nombres LIMIT %s""",
            tuple(params),
        )

    @mcp.tool(description=(
        "Obtiene la ficha académica resumida de un estudiante por ID o nombre de usuario exacto: "
        "adscripción, estado, fecha de alta, cantidad de intentos y sus últimos resultados. "
        "No devuelve credenciales. Pide el ID o usuario si la búsqueda es ambigua."
    ))
    def ficha_estudiante(identificador: str) -> str:
        admin()
        identificador = str(identificador or "").strip()[:50]
        if not identificador:
            return json.dumps({"necesita_dato": "Indica el ID o nombre de usuario exacto del estudiante."}, ensure_ascii=False)
        filas = json.loads(consultar(
            """SELECT u.id,u.nombres,u.apellidos,u.usuario,u.activo,u.created_at,
                      se.nombre AS seccion,se.grado,ca.nombre AS carrera,ap.nombre AS area_postulacion
               FROM usuarios u
               LEFT JOIN secciones se ON se.id=u.seccion_id
               LEFT JOIN carreras ca ON ca.id=u.carrera_id
               LEFT JOIN areas_postulacion ap ON ap.id=COALESCE(u.area_postulacion_id,ca.area_postulacion_id)
               WHERE u.rol='estudiante' AND (u.usuario=%s OR CAST(u.id AS CHAR)=%s)
               LIMIT 1""",
            (identificador, identificador),
        ))
        if not filas:
            return json.dumps({"resultado": "No se encontró un estudiante con ese ID o usuario."}, ensure_ascii=False)
        usuario = filas[0]
        usuario["resumen_intentos"] = json.loads(consultar(
            """SELECT tipo,estado,COUNT(*) cantidad,
                      MAX(COALESCE(fecha_fin,fecha_inicio)) ultima_actividad,
                      ROUND(AVG(CASE WHEN puntaje_total IS NOT NULL THEN puntaje_total END),3) promedio_puntaje
               FROM intentos WHERE usuario_id=%s GROUP BY tipo,estado ORDER BY tipo,estado LIMIT 10""",
            (usuario["id"],),
        ))
        usuario["ultimos_resultados"] = json.loads(consultar(
            """SELECT i.id intento_id,i.tipo,i.fecha_inicio,i.fecha_fin,i.puntaje_total,
                      i.correctas,i.incorrectas,i.en_blanco,s.nombre simulacro
               FROM intentos i LEFT JOIN simulacros s ON s.id=i.simulacro_id
               WHERE i.usuario_id=%s AND i.estado IN ('finalizado','enviado_auto')
                 AND i.puntaje_total IS NOT NULL
               ORDER BY COALESCE(i.fecha_fin,i.fecha_inicio) DESC,i.id DESC LIMIT 5""",
            (usuario["id"],),
        ))
        return json.dumps(usuario, ensure_ascii=False)

    @mcp.tool(description=(
        "Detecta estudiantes activos sin intentos recientes o que nunca han rendido. "
        "Permite definir antigüedad en días (1 a 180) y priorizar seguimiento académico."
    ))
    def estudiantes_sin_actividad(dias: int = 14, limite: int = 20) -> str:
        admin()
        dias = max(1, min(int(dias), 180))
        return consultar(
            f"""SELECT u.id,u.nombres,u.apellidos,u.usuario,se.nombre seccion,se.grado,
                       ca.nombre carrera,ult.ultimo_intento,
                       DATEDIFF(NOW(),ult.ultimo_intento) dias_sin_actividad
                FROM usuarios u
                LEFT JOIN secciones se ON se.id=u.seccion_id
                LEFT JOIN carreras ca ON ca.id=u.carrera_id
                LEFT JOIN (SELECT usuario_id,MAX(COALESCE(fecha_fin,fecha_inicio)) ultimo_intento
                           FROM intentos GROUP BY usuario_id) ult ON ult.usuario_id=u.id
                WHERE u.rol='estudiante' AND u.activo=1
                  AND (ult.ultimo_intento IS NULL OR ult.ultimo_intento<DATE_SUB(NOW(),INTERVAL {dias} DAY))
                ORDER BY ult.ultimo_intento IS NOT NULL,ult.ultimo_intento ASC,u.apellidos
                LIMIT %s""",
            (acotar_limite(limite, 50),),
        )

    @mcp.tool(description=(
        "Resume rendimiento por asignatura para toda la plataforma: estudiantes evaluados, "
        "intentos, promedio porcentual, aciertos, errores y blancos. Úsala para detectar áreas débiles."
    ))
    def rendimiento_por_asignatura() -> str:
        admin()
        return consultar(
            """SELECT a.id area_id,a.nombre asignatura,a.bloque,
                      COUNT(DISTINCT i.usuario_id) estudiantes_evaluados,
                      COUNT(DISTINCT i.id) intentos_calificados,
                      ROUND(AVG(ra.porcentaje),2) promedio_porcentaje,
                      SUM(ra.aciertos) aciertos,SUM(ra.incorrectas) incorrectas,
                      SUM(ra.en_blanco) en_blanco
               FROM resultados_area ra
               JOIN intentos i ON i.id=ra.intento_id
               JOIN areas a ON a.id=ra.area_id
               WHERE i.estado IN ('finalizado','enviado_auto') AND i.puntaje_total IS NOT NULL
               GROUP BY a.id,a.nombre,a.bloque
               ORDER BY promedio_porcentaje ASC,a.nombre LIMIT 100"""
        )

    @mcp.tool(description=(
        "Lista estudiantes cuyo último simulacro calificado tiene un puntaje menor al umbral indicado. "
        "Úsala para identificar casos que requieren acompañamiento; no diagnostiques causas sin evidencia."
    ))
    def estudiantes_en_riesgo(umbral_puntaje: float = 50, limite: int = 20) -> str:
        admin()
        umbral = max(-1000.0, min(float(umbral_puntaje), 10000.0))
        return consultar(
            """SELECT u.id,u.nombres,u.apellidos,u.usuario,se.nombre seccion,se.grado,
                      ult.fecha_fin,ult.puntaje_total,ult.correctas,ult.incorrectas,ult.en_blanco
               FROM usuarios u
               JOIN (
                   SELECT i.usuario_id,i.id intento_id,i.fecha_fin,i.puntaje_total,
                          i.correctas,i.incorrectas,i.en_blanco
                   FROM intentos i
                   WHERE i.tipo='simulacro' AND i.estado IN ('finalizado','enviado_auto')
                     AND i.puntaje_total IS NOT NULL
                     AND i.id=(SELECT i2.id FROM intentos i2
                               WHERE i2.usuario_id=i.usuario_id AND i2.tipo='simulacro'
                                 AND i2.estado IN ('finalizado','enviado_auto')
                                 AND i2.puntaje_total IS NOT NULL
                               ORDER BY COALESCE(i2.fecha_fin,i2.fecha_inicio) DESC,i2.id DESC LIMIT 1)
               ) ult ON ult.usuario_id=u.id
               LEFT JOIN secciones se ON se.id=u.seccion_id
               WHERE u.rol='estudiante' AND u.activo=1 AND ult.puntaje_total<%s
               ORDER BY ult.puntaje_total ASC,ult.fecha_fin DESC LIMIT %s""",
            (umbral, acotar_limite(limite, 50)),
        )

    @mcp.tool(description=(
        "Muestra el avance de cada simulacro: vigencia, áreas de postulación asociadas, "
        "preguntas configuradas, participantes, intentos calificados y promedio."
    ))
    def simulacros_operativos(limite: int = 20) -> str:
        admin()
        return consultar(
            """SELECT s.id,s.nombre,s.fecha_inicio,s.fecha_fin,s.activo,s.total_preguntas,
                      s.tiempo_limite,s.carrera_id,c.nombre carrera,
                      COUNT(DISTINCT aps.area_postulacion_id) areas_postulacion,
                      COALESCE(SUM(DISTINCT sa.cantidad_preguntas),0) preguntas_configuradas,
                      COUNT(DISTINCT i.id) intentos,
                      COUNT(DISTINCT CASE WHEN i.estado IN ('finalizado','enviado_auto')
                           AND i.puntaje_total IS NOT NULL THEN i.id END) intentos_calificados,
                      ROUND(AVG(CASE WHEN i.puntaje_total IS NOT NULL THEN i.puntaje_total END),3) promedio_puntaje
               FROM simulacros s
               LEFT JOIN carreras c ON c.id=s.carrera_id
               LEFT JOIN area_postulacion_simulacro aps ON aps.simulacro_id=s.id
               LEFT JOIN simulacro_area sa ON sa.simulacro_id=s.id
               LEFT JOIN intentos i ON i.simulacro_id=s.id
               GROUP BY s.id,s.nombre,s.fecha_inicio,s.fecha_fin,s.activo,s.total_preguntas,
                        s.tiempo_limite,s.carrera_id,c.nombre
               ORDER BY s.fecha_inicio DESC LIMIT %s""",
            (acotar_limite(limite, 50),),
        )

    @mcp.tool(description=(
        "Consulta un simulacro específico por ID y devuelve su configuración, áreas de postulación "
        "y distribución de preguntas por asignatura. Solicita el ID si no se identifica el simulacro."
    ))
    def detalle_simulacro(simulacro_id: int) -> str:
        admin()
        simulacro_id = max(1, int(simulacro_id))
        cabecera = json.loads(consultar(
            """SELECT s.id,s.nombre,s.fecha_inicio,s.fecha_fin,s.activo,s.tiempo_limite,
                      s.total_preguntas,s.cantidad_alternativas,s.puntaje_correcta,
                      s.puntaje_incorrecta,s.puntaje_blanco,s.puntaje_maximo,c.nombre carrera
               FROM simulacros s LEFT JOIN carreras c ON c.id=s.carrera_id
               WHERE s.id=%s LIMIT 1""",
            (simulacro_id,),
        ))
        if not cabecera:
            return json.dumps({"resultado": "No se encontró ese simulacro."}, ensure_ascii=False)
        cabecera[0]["areas_postulacion"] = json.loads(consultar(
            """SELECT ap.codigo,ap.nombre FROM area_postulacion_simulacro aps
               JOIN areas_postulacion ap ON ap.id=aps.area_postulacion_id
               WHERE aps.simulacro_id=%s ORDER BY ap.codigo LIMIT 20""",
            (simulacro_id,),
        ))
        cabecera[0]["distribucion"] = json.loads(consultar(
            """SELECT a.nombre asignatura,sa.nivel,sa.cantidad_preguntas
               FROM simulacro_area sa JOIN areas a ON a.id=sa.area_id
               WHERE sa.simulacro_id=%s ORDER BY a.nombre,sa.nivel LIMIT 100""",
            (simulacro_id,),
        ))
        return json.dumps(cabecera[0], ensure_ascii=False)

    @mcp.tool(description=(
        "Audita la calidad estructural del banco. Detecta preguntas que no tienen exactamente "
        "cuatro alternativas o no tienen exactamente una respuesta correcta. Devuelve IDs y contexto "
        "para corregirlas desde el panel, sin modificar datos."
    ))
    def auditar_banco_preguntas(limite: int = 30) -> str:
        admin()
        return consultar(
            """SELECT p.id,p.codigo,LEFT(p.enunciado,160) enunciado,p.nivel,p.activo,
                      a.nombre asignatura,t.nombre tema,COUNT(al.id) cantidad_alternativas,
                      COALESCE(SUM(CASE WHEN al.es_correcta=1 THEN 1 ELSE 0 END),0) respuestas_correctas
               FROM preguntas p
               JOIN temas t ON t.id=p.tema_id
               JOIN areas a ON a.id=t.area_id
               LEFT JOIN alternativas al ON al.pregunta_id=p.id
               GROUP BY p.id,p.codigo,p.enunciado,p.nivel,p.activo,a.nombre,t.nombre
               HAVING COUNT(al.id)<>4 OR COALESCE(SUM(CASE WHEN al.es_correcta=1 THEN 1 ELSE 0 END),0)<>1
               ORDER BY p.activo DESC,p.id DESC LIMIT %s""",
            (acotar_limite(limite, 100),),
        )

    @mcp.tool(description=(
        "Resume la cobertura del catálogo por asignatura: bloque, temas activos/inactivos, "
        "preguntas activas y preguntas válidas para simulacros (cuatro alternativas y una correcta)."
    ))
    def cobertura_catalogo_academico() -> str:
        admin()
        return consultar(
            """SELECT a.id,a.nombre asignatura,a.bloque,
                      COUNT(DISTINCT CASE WHEN t.activo=1 THEN t.id END) temas_activos,
                      COUNT(DISTINCT CASE WHEN t.activo=0 THEN t.id END) temas_inactivos,
                      COUNT(DISTINCT CASE WHEN p.activo=1 THEN p.id END) preguntas_activas,
                      COUNT(DISTINCT CASE WHEN p.activo=1
                           AND (SELECT COUNT(*) FROM alternativas al WHERE al.pregunta_id=p.id)=4
                           AND (SELECT COUNT(*) FROM alternativas ac WHERE ac.pregunta_id=p.id AND ac.es_correcta=1)=1
                           THEN p.id END) preguntas_validas
               FROM areas a
               LEFT JOIN temas t ON t.area_id=a.id
               LEFT JOIN preguntas p ON p.tema_id=t.id
               GROUP BY a.id,a.nombre,a.bloque ORDER BY a.nombre LIMIT 100"""
        )

    @mcp.tool(description=(
        "Detecta intentos de examen que siguen en_curso durante más horas que el umbral indicado. "
        "Sirve para localizar sesiones posiblemente abandonadas o problemas de cierre automático."
    ))
    def intentos_atrapados(horas: int = 3, limite: int = 20) -> str:
        admin()
        horas = max(1, min(int(horas), 168))
        return consultar(
            f"""SELECT i.id intento_id,i.tipo,i.fecha_inicio,i.fecha_limite,i.estado,
                       u.id usuario_id,u.nombres,u.apellidos,u.usuario,s.nombre simulacro
                FROM intentos i JOIN usuarios u ON u.id=i.usuario_id
                LEFT JOIN simulacros s ON s.id=i.simulacro_id
                WHERE i.estado='en_curso'
                  AND i.fecha_inicio<DATE_SUB(NOW(),INTERVAL {horas} HOUR)
                ORDER BY i.fecha_inicio ASC LIMIT %s""",
            (acotar_limite(limite, 50),),
        )

    @mcp.tool(description=(
        "Muestra actividad de la plataforma agrupada por día, tipo de intento y estado, "
        "con usuarios participantes y puntaje promedio. Úsala para tendencias recientes."
    ))
    def actividad_plataforma(dias: int = 7, limite: int = 30) -> str:
        admin()
        dias = max(1, min(int(dias), 90))
        return consultar(
            f"""SELECT DATE(fecha_inicio) fecha,tipo,estado,COUNT(*) intentos,
                       COUNT(DISTINCT usuario_id) usuarios,
                       ROUND(AVG(CASE WHEN puntaje_total IS NOT NULL THEN puntaje_total END),3) promedio_puntaje
                FROM intentos
                WHERE fecha_inicio>=DATE_SUB(NOW(),INTERVAL {dias} DAY)
                GROUP BY DATE(fecha_inicio),tipo,estado
                ORDER BY fecha DESC,tipo,estado LIMIT %s""",
            (acotar_limite(limite, 100),),
        )

    @mcp.tool(description=(
        "Compara el rendimiento de las áreas de postulación A-E: estudiantes activos, "
        "carreras disponibles, simulacros asociados y participación en intentos."
    ))
    def resumen_por_area_postulacion() -> str:
        admin()
        return consultar(
            """SELECT ap.id,ap.codigo,ap.nombre,ap.activo,
                      COUNT(DISTINCT CASE WHEN u.rol='estudiante' AND u.activo=1 THEN u.id END) estudiantes_activos,
                      COUNT(DISTINCT CASE WHEN c.activo=1 THEN c.id END) carreras_activas,
                      COUNT(DISTINCT CASE WHEN s.activo=1 THEN s.id END) simulacros_activos
               FROM areas_postulacion ap
               LEFT JOIN usuarios u ON u.area_postulacion_id=ap.id
                    OR u.carrera_id IN (SELECT c2.id FROM carreras c2 WHERE c2.area_postulacion_id=ap.id)
               LEFT JOIN carreras c ON c.area_postulacion_id=ap.id
               LEFT JOIN area_postulacion_simulacro aps ON aps.area_postulacion_id=ap.id
               LEFT JOIN simulacros s ON s.id=aps.simulacro_id
               GROUP BY ap.id,ap.codigo,ap.nombre,ap.activo
               ORDER BY ap.codigo LIMIT 20"""
        )

    @mcp.tool(description=(
        "Lista las cuentas docentes y su sección asignada, para revisar cobertura de acompañamiento. "
        "No devuelve credenciales ni información de autenticación."
    ))
    def resumen_docentes(limite: int = 30) -> str:
        admin()
        return consultar(
            """SELECT u.id,u.nombres,u.apellidos,u.usuario,u.activo,
                      se.nombre seccion,se.grado,
                      COUNT(DISTINCT CASE WHEN e.rol='estudiante' AND e.activo=1 THEN e.id END) estudiantes_asignados
               FROM usuarios u
               LEFT JOIN secciones se ON se.id=u.seccion_id
               LEFT JOIN usuarios e ON e.seccion_id=u.seccion_id
               WHERE u.rol='docente'
               GROUP BY u.id,u.nombres,u.apellidos,u.usuario,u.activo,se.nombre,se.grado
               ORDER BY u.activo DESC,se.grado,se.nombre,u.apellidos LIMIT %s""",
            (acotar_limite(limite, 100),),
        )

    @mcp.tool(description="Úsala para comparar el promedio del último intento calificado por sección, considerando estudiantes activos.")
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
            ORDER BY porcentaje_acierto ASC LIMIT %s""", (acotar_limite(limite, 50),))

else:
    raise RuntimeError("Rol no autorizado")


if __name__ == "__main__":
    mcp.run()
