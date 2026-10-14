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
CREATE TABLE secciones (
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
CREATE TABLE areas_postulacion (
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
CREATE TABLE carreras (
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
CREATE TABLE usuarios (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombres         VARCHAR(100)    NOT NULL,
    apellidos       VARCHAR(100)    NOT NULL,
    usuario         VARCHAR(50)     NOT NULL COMMENT 'Nombre de usuario para iniciar sesión',
    password        VARCHAR(255)    NOT NULL COMMENT 'Contraseña cifrada (bcrypt)',
    rol             ENUM('admin','docente','estudiante') NOT NULL,
    seccion_id      BIGINT UNSIGNED NULL COMMENT 'Obligatoria solo para estudiantes',
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
CREATE TABLE areas (
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
CREATE TABLE temas (
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
CREATE TABLE preguntas (
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
CREATE TABLE alternativas (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pregunta_id  BIGINT UNSIGNED NOT NULL,
    letra        ENUM('A','B','C','D') NOT NULL,
    texto        VARCHAR(500)    NOT NULL,
    es_correcta  TINYINT(1)      NOT NULL DEFAULT 0,
    created_at   TIMESTAMP       NULL DEFAULT NULL,
    updated_at   TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_alternativas_pregunta_letra (pregunta_id, letra),
    CONSTRAINT fk_alternativas_pregunta
        FOREIGN KEY (pregunta_id) REFERENCES preguntas (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cuatro alternativas por pregunta, una correcta';

-- =====================================================================
--  9. simulacros
-- =====================================================================
CREATE TABLE simulacros (
    id                  BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    nombre              VARCHAR(150)      NOT NULL,
    tiempo_limite       SMALLINT UNSIGNED NOT NULL COMMENT 'Duración máxima en minutos',
    total_preguntas     SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    puntaje_correcta    DECIMAL(7,3)      NOT NULL DEFAULT 20.000  COMMENT 'Puntos por respuesta correcta',
    puntaje_incorrecta  DECIMAL(7,3)      NOT NULL DEFAULT -1.125  COMMENT 'Penalización por respuesta incorrecta',
    puntaje_blanco      DECIMAL(7,3)      NOT NULL DEFAULT 0.000   COMMENT 'Puntos por respuesta en blanco o doble marca',
    puntaje_maximo      DECIMAL(8,2)      NOT NULL DEFAULT 2000.00 COMMENT 'total_preguntas x puntaje_correcta',
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
    CONSTRAINT chk_simulacros_fechas      CHECK (fecha_fin > fecha_inicio),
    CONSTRAINT chk_simulacros_p_correcta  CHECK (puntaje_correcta > 0),
    CONSTRAINT chk_simulacros_p_incorrecta CHECK (puntaje_incorrecta <= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuración de cada simulacro';

-- =====================================================================
--  10. area_postulacion_simulacro  (N:M)
-- =====================================================================
CREATE TABLE area_postulacion_simulacro (
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
CREATE TABLE simulacro_area (
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
CREATE TABLE intentos (
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
CREATE TABLE respuestas_estudiante (
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
CREATE TABLE resultados_area (
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
INSERT INTO secciones (nombre, grado, activo, created_at, updated_at) VALUES
    ('A', '5to', 1, NOW(), NOW()),
    ('B', '5to', 1, NOW(), NOW()),
    ('C', '5to', 1, NOW(), NOW()),
    ('D', '5to', 1, NOW(), NOW());

-- Áreas de postulación
INSERT INTO areas_postulacion (codigo, nombre, activo, created_at, updated_at) VALUES
    ('A', 'Ciencias de la Salud', 1, NOW(), NOW()),
    ('B', 'Ciencias Básicas', 1, NOW(), NOW()),
    ('C', 'Ingenierías', 1, NOW(), NOW()),
    ('D', 'Ciencias Económicas y de la Gestión', 1, NOW(), NOW()),
    ('E', 'Humanidades y Ciencias Jurídicas y Sociales', 1, NOW(), NOW());

-- Áreas de contenido (15 + 15 + 70 = 100 preguntas)
INSERT INTO areas (nombre, bloque, activo, created_at, updated_at) VALUES
    ('Habilidad Verbal', 'habilidad_verbal', 1, NOW(), NOW()),
    ('Habilidad Lógico-Matemática', 'habilidad_logico_matematica', 1, NOW(), NOW()),
    ('Matemática', 'conocimientos', 1, NOW(), NOW()),
    ('Ciencias', 'conocimientos', 1, NOW(), NOW()),
    ('Letras', 'conocimientos', 1, NOW(), NOW());

-- Temas iniciales de Habilidad Verbal (los demás los definen los docentes)
INSERT INTO temas (area_id, nombre, activo, created_at, updated_at)
SELECT id, 'Comprensión de lectura', 1, NOW(), NOW() FROM areas WHERE nombre = 'Habilidad Verbal';

INSERT INTO temas (area_id, nombre, activo, created_at, updated_at)
SELECT id, 'Inglés', 1, NOW(), NOW() FROM areas WHERE nombre = 'Habilidad Verbal';

-- Pendientes: catálogo de carreras (tabla carreras) y usuario administrador inicial (tabla usuarios).

-- Verificación
SHOW TABLES;
