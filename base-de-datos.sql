-- =====================================================================
--  Plataforma virtual de preparación académica – I.E. "Imperial CNI"
--  Script de creación de la base de datos (MariaDB 10.4 o superior)
--  Versión 2 – 14 tablas, restricciones, índices y datos iniciales
--
--  Uso en DBeaver:
--    1. Conéctese a MariaDB con un usuario que pueda crear bases de datos.
--    2. Abra este archivo en el editor SQL y ejecútelo completo (Alt + X).
--    3. Para ver el diagrama: clic derecho sobre la base "simulador_cni"
--       > "Ver diagrama" (View Diagram).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS simulador_cni
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE simulador_cni;


-- =====================================================================
--  1. secciones
-- =====================================================================
CREATE TABLE IF NOT EXISTS secciones (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(10)     NOT NULL COMMENT 'Sección: A, B, C, D',
    grado       VARCHAR(20)     NOT NULL DEFAULT '5to',
    activo      TINYINT(1)      NOT NULL DEFAULT 1,
    created_at  TIMESTAMP       NULL DEFAULT NULL,
    updated_at  TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_secciones_nombre_grado (nombre, grado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Secciones del 5.º grado';

-- =====================================================================
--  2. areas_postulacion  (Áreas A–E del examen de admisión)
-- =====================================================================
CREATE TABLE IF NOT EXISTS areas_postulacion (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo      CHAR(1)         NOT NULL COMMENT 'A, B, C, D o E',
    nombre      VARCHAR(100)    NOT NULL,
    activo      TINYINT(1)      NOT NULL DEFAULT 1,
    created_at  TIMESTAMP       NULL DEFAULT NULL,
    updated_at  TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_areas_postulacion_codigo (codigo),
    UNIQUE KEY uq_areas_postulacion_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Áreas de selección A a E';

-- =====================================================================
--  3. carreras
-- =====================================================================
CREATE TABLE IF NOT EXISTS carreras (
    id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    area_postulacion_id  BIGINT UNSIGNED NOT NULL,
    nombre               VARCHAR(150)    NOT NULL,
    activo               TINYINT(1)      NOT NULL DEFAULT 1,
    created_at           TIMESTAMP       NULL DEFAULT NULL,
    updated_at           TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_carreras_nombre (nombre),
    KEY idx_carreras_area_postulacion (area_postulacion_id),
    CONSTRAINT fk_carreras_area_postulacion
        FOREIGN KEY (area_postulacion_id) REFERENCES areas_postulacion (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Carreras profesionales por área de postulación';

-- =====================================================================
--  4. usuarios  (administrador, docente, estudiante)
-- =====================================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombres         VARCHAR(100)    NOT NULL,
    apellidos       VARCHAR(100)    NOT NULL,
    usuario         VARCHAR(50)     NOT NULL COMMENT 'Nombre de usuario para iniciar sesión',
    password        VARCHAR(255)    NOT NULL COMMENT 'Contraseña cifrada (bcrypt)',
    rol             ENUM('admin','docente','estudiante') NOT NULL,
    seccion_id      BIGINT UNSIGNED NULL COMMENT 'Asignada a estudiantes y docentes de sección',
    carrera_id      BIGINT UNSIGNED NULL COMMENT 'Carrera elegida por el estudiante (RF-16)',
    activo          TINYINT(1)      NOT NULL DEFAULT 1,
    remember_token  VARCHAR(100)    NULL DEFAULT NULL,
    created_at      TIMESTAMP       NULL DEFAULT NULL,
    updated_at      TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_usuario (usuario),
    KEY idx_usuarios_seccion (seccion_id),
    KEY idx_usuarios_carrera (carrera_id),
    KEY idx_usuarios_rol_activo (rol, activo),
    CONSTRAINT fk_usuarios_seccion
        FOREIGN KEY (seccion_id) REFERENCES secciones (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_usuarios_carrera
        FOREIGN KEY (carrera_id) REFERENCES carreras (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Administradores, docentes y estudiantes';

-- =====================================================================
--  5. areas  (áreas de contenido del examen)
-- =====================================================================
CREATE TABLE IF NOT EXISTS areas (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(100)    NOT NULL,
    bloque      ENUM('habilidad_verbal','habilidad_logico_matematica','conocimientos') NOT NULL
                COMMENT 'Bloque del examen: 15 + 15 + 70 preguntas',
    activo      TINYINT(1)      NOT NULL DEFAULT 1,
    created_at  TIMESTAMP       NULL DEFAULT NULL,
    updated_at  TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_areas_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Áreas de contenido clasificadas por bloque';

-- =====================================================================
--  6. temas
-- =====================================================================
CREATE TABLE IF NOT EXISTS temas (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    area_id     BIGINT UNSIGNED NOT NULL,
    nombre      VARCHAR(150)    NOT NULL,
    activo      TINYINT(1)      NOT NULL DEFAULT 1,
    created_at  TIMESTAMP       NULL DEFAULT NULL,
    updated_at  TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_temas_area_nombre (area_id, nombre),
    CONSTRAINT fk_temas_area
        FOREIGN KEY (area_id) REFERENCES areas (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Temas de cada área de contenido';

-- =====================================================================
--  7. preguntas  (banco de preguntas)
-- =====================================================================
CREATE TABLE IF NOT EXISTS preguntas (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tema_id     BIGINT UNSIGNED NOT NULL,
    enunciado   TEXT            NOT NULL,
    imagen      VARCHAR(255)    NULL DEFAULT NULL COMMENT 'Ruta de imagen JPG o PNG (opcional)',
    nivel       ENUM('basico','intermedio','avanzado') NOT NULL,
    solucion    TEXT            NOT NULL COMMENT 'Solución breve mostrada en los resultados',
    activo      TINYINT(1)      NOT NULL DEFAULT 1,
    creado_por  BIGINT UNSIGNED NULL,
    created_at  TIMESTAMP       NULL DEFAULT NULL,
    updated_at  TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_preguntas_tema_nivel_activo (tema_id, nivel, activo),
    KEY idx_preguntas_creado_por (creado_por),
    CONSTRAINT fk_preguntas_tema
        FOREIGN KEY (tema_id) REFERENCES temas (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_preguntas_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Banco de preguntas';

-- =====================================================================
--  8. alternativas  (4 por pregunta: A, B, C, D)
-- =====================================================================
CREATE TABLE IF NOT EXISTS alternativas (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pregunta_id  BIGINT UNSIGNED NOT NULL,
    letra        CHAR(1) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    texto        VARCHAR(500)    NOT NULL,
    es_correcta  TINYINT(1)      NOT NULL DEFAULT 0,
    created_at   TIMESTAMP       NULL DEFAULT NULL,
    updated_at   TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_alternativas_pregunta_letra (pregunta_id, letra),
    CONSTRAINT fk_alternativas_pregunta
        FOREIGN KEY (pregunta_id) REFERENCES preguntas (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_alternativas_letra CHECK (letra BETWEEN 'A' AND 'Z')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cuatro alternativas por pregunta, una correcta';

-- =====================================================================
--  9. simulacros
-- =====================================================================
CREATE TABLE IF NOT EXISTS simulacros (
    id                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    nombre              VARCHAR(150)      NOT NULL,
    tiempo_limite       SMALLINT UNSIGNED NOT NULL COMMENT 'Duración máxima en minutos',
    total_preguntas     SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    cantidad_alternativas SMALLINT UNSIGNED NOT NULL DEFAULT 4
                         COMMENT 'Cantidad de opciones por pregunta para este simulacro',
    puntaje_correcta    DECIMAL(7,3)      NOT NULL DEFAULT 20.000  COMMENT 'Puntos por respuesta correcta',
    puntaje_incorrecta  DECIMAL(7,3)      NOT NULL DEFAULT -1.125  COMMENT 'Penalización por respuesta incorrecta',
    puntaje_blanco      DECIMAL(7,3)      NOT NULL DEFAULT 0.000   COMMENT 'Puntos por respuesta en blanco o doble marca',
    puntaje_maximo      DECIMAL(12,3)     AS (total_preguntas * puntaje_correcta) STORED
                         COMMENT 'Calculado como total_preguntas x puntaje_correcta',
    fecha_inicio        DATETIME          NOT NULL,
    fecha_fin           DATETIME          NOT NULL,
    activo              TINYINT(1)        NOT NULL DEFAULT 1,
    creado_por          BIGINT UNSIGNED   NULL,
    created_at          TIMESTAMP         NULL DEFAULT NULL,
    updated_at          TIMESTAMP         NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_simulacros_creado_por (creado_por),
    CONSTRAINT fk_simulacros_creado_por
        FOREIGN KEY (creado_por) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_simulacros_tiempo      CHECK (tiempo_limite > 0),
    CONSTRAINT chk_simulacros_total       CHECK (total_preguntas > 0),
    CONSTRAINT chk_simulacros_alternativas CHECK (cantidad_alternativas BETWEEN 2 AND 26),
    CONSTRAINT chk_simulacros_fechas      CHECK (fecha_fin > fecha_inicio),
    CONSTRAINT chk_simulacros_p_correcta  CHECK (puntaje_correcta > 0),
    CONSTRAINT chk_simulacros_p_incorrecta CHECK (puntaje_incorrecta <= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuración de cada simulacro';

-- =====================================================================
--  10. area_postulacion_simulacro  (N:M)
-- =====================================================================
CREATE TABLE IF NOT EXISTS area_postulacion_simulacro (
    id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    area_postulacion_id  BIGINT UNSIGNED NOT NULL,
    simulacro_id         BIGINT UNSIGNED NOT NULL,
    created_at           TIMESTAMP       NULL DEFAULT NULL,
    updated_at           TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ap_simulacro (area_postulacion_id, simulacro_id),
    KEY idx_ap_simulacro_simulacro (simulacro_id),
    CONSTRAINT fk_ap_simulacro_area
        FOREIGN KEY (area_postulacion_id) REFERENCES areas_postulacion (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ap_simulacro_simulacro
        FOREIGN KEY (simulacro_id) REFERENCES simulacros (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Áreas de postulación para las que está abierto cada simulacro';

-- =====================================================================
--  11. simulacro_area  (preguntas por área y nivel en cada simulacro)
-- =====================================================================
CREATE TABLE IF NOT EXISTS simulacro_area (
    id                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    simulacro_id        BIGINT UNSIGNED   NOT NULL,
    area_id             BIGINT UNSIGNED   NOT NULL,
    nivel               ENUM('basico','intermedio','avanzado','mixto') NOT NULL DEFAULT 'mixto',
    cantidad_preguntas  SMALLINT UNSIGNED NOT NULL,
    created_at          TIMESTAMP         NULL DEFAULT NULL,
    updated_at          TIMESTAMP         NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_simulacro_area_nivel (simulacro_id, area_id, nivel),
    KEY idx_simulacro_area_area (area_id),
    CONSTRAINT fk_simulacro_area_simulacro
        FOREIGN KEY (simulacro_id) REFERENCES simulacros (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_simulacro_area_area
        FOREIGN KEY (area_id) REFERENCES areas (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_simulacro_area_cantidad CHECK (cantidad_preguntas > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cantidad de preguntas por área y nivel que se sortea a cada estudiante';

-- =====================================================================
--  12. intentos  (simulacros rendidos y prácticas)
-- =====================================================================
CREATE TABLE IF NOT EXISTS intentos (
    id               BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    usuario_id       BIGINT UNSIGNED   NOT NULL,
    carrera_id       BIGINT UNSIGNED   NULL COMMENT 'Carrera del estudiante al momento del intento',
    simulacro_id     BIGINT UNSIGNED   NULL COMMENT 'Nulo en prácticas',
    tipo             ENUM('simulacro','practica') NOT NULL,
    area_id          BIGINT UNSIGNED   NULL COMMENT 'Área elegida en una práctica',
    tema_id          BIGINT UNSIGNED   NULL COMMENT 'Tema elegido en una práctica',
    fecha_inicio     DATETIME          NOT NULL,
    fecha_limite     DATETIME          NULL COMMENT 'fecha_inicio + tiempo_limite; nulo en prácticas',
    fecha_fin        DATETIME          NULL,
    estado           ENUM('en_curso','finalizado','enviado_auto') NOT NULL DEFAULT 'en_curso',
    total_preguntas  SMALLINT UNSIGNED NOT NULL,
    puntaje_total    DECIMAL(8,3)      NULL COMMENT 'Puede ser negativo por la penalización',
    correctas        SMALLINT UNSIGNED NULL,
    incorrectas      SMALLINT UNSIGNED NULL,
    en_blanco        SMALLINT UNSIGNED NULL,
    doble_marca      SMALLINT UNSIGNED NULL,
    created_at       TIMESTAMP         NULL DEFAULT NULL,
    updated_at       TIMESTAMP         NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_intentos_usuario_fecha (usuario_id, fecha_inicio),
    KEY idx_intentos_simulacro_estado (simulacro_id, estado),
    KEY idx_intentos_estado_limite (estado, fecha_limite),
    KEY idx_intentos_carrera (carrera_id),
    KEY idx_intentos_area (area_id),
    KEY idx_intentos_tema (tema_id),
    CONSTRAINT fk_intentos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_intentos_carrera
        FOREIGN KEY (carrera_id) REFERENCES carreras (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_intentos_simulacro
        FOREIGN KEY (simulacro_id) REFERENCES simulacros (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_intentos_area
        FOREIGN KEY (area_id) REFERENCES areas (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_intentos_tema
        FOREIGN KEY (tema_id) REFERENCES temas (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cada simulacro rendido o práctica realizada por un estudiante';

-- =====================================================================
--  13. respuestas_estudiante  (examen propio de cada intento)
-- =====================================================================
CREATE TABLE IF NOT EXISTS respuestas_estudiante (
    id                BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    intento_id        BIGINT UNSIGNED   NOT NULL,
    pregunta_id       BIGINT UNSIGNED   NOT NULL,
    orden             SMALLINT UNSIGNED NOT NULL COMMENT 'Posición aleatoria dentro del intento',
    alternativa_id    BIGINT UNSIGNED   NULL COMMENT 'Nula si está en blanco o con doble marca',
    estado_respuesta  ENUM('correcta','incorrecta','en_blanco','doble_marca') NULL
                      COMMENT 'Nulo hasta calificar',
    puntaje_obtenido  DECIMAL(7,3)      NULL COMMENT '+20.000, -1.125 o 0 por defecto',
    respondida_en     DATETIME          NULL,
    created_at        TIMESTAMP         NULL DEFAULT NULL,
    updated_at        TIMESTAMP         NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_respuestas_intento_pregunta (intento_id, pregunta_id),
    UNIQUE KEY uq_respuestas_intento_orden (intento_id, orden),
    KEY idx_respuestas_pregunta (pregunta_id),
    KEY idx_respuestas_alternativa (alternativa_id),
    CONSTRAINT fk_respuestas_intento
        FOREIGN KEY (intento_id) REFERENCES intentos (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_respuestas_pregunta
        FOREIGN KEY (pregunta_id) REFERENCES preguntas (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_respuestas_alternativa
        FOREIGN KEY (alternativa_id) REFERENCES alternativas (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Preguntas sorteadas para cada intento, en orden propio, con la respuesta marcada';

-- =====================================================================
--  14. resultados_area
-- =====================================================================
CREATE TABLE IF NOT EXISTS resultados_area (
    id               BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    intento_id       BIGINT UNSIGNED   NOT NULL,
    area_id          BIGINT UNSIGNED   NOT NULL,
    total_preguntas  SMALLINT UNSIGNED NOT NULL,
    aciertos         SMALLINT UNSIGNED NOT NULL,
    incorrectas      SMALLINT UNSIGNED NOT NULL,
    en_blanco        SMALLINT UNSIGNED NOT NULL COMMENT 'Incluye doble marca',
    puntaje          DECIMAL(8,3)      NOT NULL,
    porcentaje       DECIMAL(5,2)      NOT NULL COMMENT 'Porcentaje de aciertos del área',
    created_at       TIMESTAMP         NULL DEFAULT NULL,
    updated_at       TIMESTAMP         NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_resultados_intento_area (intento_id, area_id),
    KEY idx_resultados_area (area_id),
    CONSTRAINT fk_resultados_intento
        FOREIGN KEY (intento_id) REFERENCES intentos (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_resultados_area
        FOREIGN KEY (area_id) REFERENCES areas (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_resultados_porcentaje CHECK (porcentaje BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Aciertos, puntaje y porcentaje por área de cada intento';

-- =====================================================================
--  DATOS INICIALES
-- =====================================================================

-- Secciones del 5.º grado
INSERT INTO secciones (nombre, grado, activo, created_at, updated_at)
SELECT seed.nombre, seed.grado, 1, NOW(), NOW()
FROM (
    SELECT 'A' AS nombre, '5to' AS grado
    UNION ALL SELECT 'B', '5to'
    UNION ALL SELECT 'C', '5to'
    UNION ALL SELECT 'D', '5to'
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM secciones s
    WHERE s.nombre = seed.nombre AND s.grado = seed.grado
);

-- Áreas de postulación
INSERT INTO areas_postulacion (codigo, nombre, activo, created_at, updated_at)
SELECT seed.codigo, seed.nombre, 1, NOW(), NOW()
FROM (
    SELECT 'A' AS codigo, 'Ciencias de la Salud' AS nombre
    UNION ALL SELECT 'B', 'Ciencias Básicas'
    UNION ALL SELECT 'C', 'Ingenierías'
    UNION ALL SELECT 'D', 'Ciencias Económicas y de la Gestión'
    UNION ALL SELECT 'E', 'Humanidades y Ciencias Jurídicas y Sociales'
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM areas_postulacion ap
    WHERE ap.codigo = seed.codigo OR ap.nombre = seed.nombre
);

-- Áreas de contenido (15 + 15 + 70 = 100 preguntas)
INSERT INTO areas (nombre, bloque, activo, created_at, updated_at)
SELECT seed.nombre, seed.bloque, 1, NOW(), NOW()
FROM (
    SELECT 'Habilidad Verbal' AS nombre, 'habilidad_verbal' AS bloque
    UNION ALL SELECT 'Habilidad Lógico-Matemática', 'habilidad_logico_matematica'
    UNION ALL SELECT 'Matemática', 'conocimientos'
    UNION ALL SELECT 'Ciencias', 'conocimientos'
    UNION ALL SELECT 'Letras', 'conocimientos'
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM areas a WHERE a.nombre = seed.nombre
);

-- Temas iniciales de Habilidad Verbal (los demás los definen los docentes)
INSERT INTO temas (area_id, nombre, activo, created_at, updated_at)
SELECT a.id, 'Comprensión de lectura', 1, NOW(), NOW()
FROM areas a
WHERE a.nombre = 'Habilidad Verbal'
  AND NOT EXISTS (
      SELECT 1 FROM temas t
      WHERE t.area_id = a.id AND t.nombre = 'Comprensión de lectura'
  );

INSERT INTO temas (area_id, nombre, activo, created_at, updated_at)
SELECT a.id, 'Inglés', 1, NOW(), NOW()
FROM areas a
WHERE a.nombre = 'Habilidad Verbal'
  AND NOT EXISTS (
      SELECT 1 FROM temas t
      WHERE t.area_id = a.id AND t.nombre = 'Inglés'
  );

-- Las carreras se cargan con el catálogo oficial aprobado para la institución.
-- El administrador se crea desde Laravel con una contraseña proporcionada de forma segura.

-- Verificación de tablas y datos de referencia
SHOW TABLES;

SELECT 'secciones' AS tabla, COUNT(*) AS filas FROM secciones
UNION ALL SELECT 'areas_postulacion', COUNT(*) FROM areas_postulacion
UNION ALL SELECT 'carreras', COUNT(*) FROM carreras
UNION ALL SELECT 'usuarios', COUNT(*) FROM usuarios
UNION ALL SELECT 'areas', COUNT(*) FROM areas
UNION ALL SELECT 'temas', COUNT(*) FROM temas
UNION ALL SELECT 'preguntas', COUNT(*) FROM preguntas
UNION ALL SELECT 'alternativas', COUNT(*) FROM alternativas
UNION ALL SELECT 'simulacros', COUNT(*) FROM simulacros
UNION ALL SELECT 'area_postulacion_simulacro', COUNT(*) FROM area_postulacion_simulacro
UNION ALL SELECT 'simulacro_area', COUNT(*) FROM simulacro_area
UNION ALL SELECT 'intentos', COUNT(*) FROM intentos
UNION ALL SELECT 'respuestas_estudiante', COUNT(*) FROM respuestas_estudiante
UNION ALL SELECT 'resultados_area', COUNT(*) FROM resultados_area;

SELECT COUNT(*) AS claves_foraneas
FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND CONSTRAINT_TYPE = 'FOREIGN KEY';

SELECT COUNT(DISTINCT CONCAT(TABLE_NAME, ':', INDEX_NAME)) AS indices
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE();
