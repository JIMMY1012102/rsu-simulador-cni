# Validación en MariaDB del equipo · exclusivamente SELECT

> **Fuente:** `develop` (`eb82cd46c35567c4b7d840a524a339e28c0f18ca`) · **Fecha:** 26/10/2026. **Estado:** esquema esperado en código; MariaDB compartida no consultada en vivo.

**Situación comunicada por el equipo (26/10/2026):** el sistema sigue en DEMO, ya tiene las cinco áreas A–E y **no existe una MariaDB compartida**. Por lo tanto, esta sección **no es una auditoría incompleta de una base central existente**, sino una guía opcional para una instalación local o futura. GitHub Actions ejecuta las migraciones y pruebas en una MariaDB 11.4 temporal. Las siguientes consultas son instrucciones reproducibles, no resultados ejecutados. Ejecutar solo tras identificar correctamente el esquema de desarrollo y usar un usuario de lectura. No adjuntar datos personales, hashes ni contraseñas al PR.

## A. Esquema y columnas realmente instalados

```sql
SELECT DATABASE() AS esquema, VERSION() AS version_mariadb;
SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME;
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()
ORDER BY TABLE_NAME, ORDINAL_POSITION;
SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()
ORDER BY TABLE_NAME, CONSTRAINT_TYPE, CONSTRAINT_NAME;
```

## B. FKs, CHECK e índices realmente instalados

```sql
SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME,CONSTRAINT_NAME,ORDINAL_POSITION;
SELECT TABLE_NAME,INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME
FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE()
ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX;
SELECT tc.TABLE_NAME,tc.CONSTRAINT_NAME,cc.CHECK_CLAUSE
FROM information_schema.TABLE_CONSTRAINTS tc
JOIN information_schema.CHECK_CONSTRAINTS cc
  ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME
WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.CONSTRAINT_TYPE='CHECK';
```

## C. Catálogo y adiciones de octubre

```sql
SELECT codigo,nombre,activo FROM areas_postulacion ORDER BY codigo;
SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE() AND
((TABLE_NAME='usuarios' AND COLUMN_NAME='area_postulacion_id') OR
 (TABLE_NAME='simulacros' AND COLUMN_NAME='carrera_id'));
SELECT COUNT(*) AS total_usuarios FROM usuarios;
SELECT COUNT(*) AS total_intentos FROM intentos;
SELECT COUNT(*) AS total_preguntas FROM preguntas;
```

Si las áreas A–E no coinciden con el seeder, **DETENERSE**; no ejecutar `AreaPostulacionSeeder` ni transformar IDs de una base poblada. Consultar las migraciones ejecutadas en la tabla `migrations` **solo si existe**:

```sql
SELECT migration,batch FROM migrations ORDER BY batch,migration;
```

## D. Coherencia: solo conteos (requiere columnas nuevas ya instaladas)

```sql
SELECT COUNT(*) AS usuarios_de_carrera_y_area_incoherentes
FROM usuarios u JOIN carreras c ON c.id=u.carrera_id
WHERE u.area_postulacion_id IS NOT NULL AND u.area_postulacion_id<>c.area_postulacion_id;
SELECT COUNT(*) AS respuestas_con_opcion_de_otra_pregunta
FROM respuestas_estudiante r JOIN alternativas a ON a.id=r.alternativa_id
WHERE a.pregunta_id<>r.pregunta_id;
SELECT s.id,s.total_preguntas,COALESCE(SUM(sa.cantidad_preguntas),0) AS cuota
FROM simulacros s LEFT JOIN simulacro_area sa ON sa.simulacro_id=s.id
GROUP BY s.id,s.total_preguntas HAVING cuota<>s.total_preguntas;
```

## Control opcional para futuros entornos locales o despliegues

| Verificación | Objetivo según código | MariaDB del equipo |
|---|---|---|
| Tablas de aplicación + histórico tokens | 15 `Schema::create` | No aplica a una base compartida inexistente |
| Columnas definidas en migraciones | 138 | Sin comprobar |
| FK declaradas en migraciones | 24 | Sin comprobar |
| Áreas de postulación | A, B, C, D, E | Sin comprobar |
| Columnas añadidas 22/10 | `usuarios.area_postulacion_id`, `simulacros.carrera_id` | Sin comprobar |
| Índices, CHECK y datos heredados | Según 03-integridad | Sin comprobar |

**Prohibido en esta fase:** `migrate:fresh`, `migrate:rollback`, importar `base-de-datos.sql`, alterar tablas, reseed, modificar `.env` o hacer cambios en producción/Oracle Cloud.
