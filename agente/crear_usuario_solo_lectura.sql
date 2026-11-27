-- Ejecuta como administrador. ANTES de ejecutar, reemplaza `simulador_cni` por el nombre exacto de tu base de datos en TODAS las sentencias GRANT de este archivo.
-- Sin permisos de escritura ni acceso a usuarios.password.
CREATE USER IF NOT EXISTS 'agente'@'localhost' IDENTIFIED BY 'CAMBIAR_POR_UNA_CLAVE_SEGURA';
GRANT SELECT (id, nombres, apellidos, rol, seccion_id, carrera_id, area_postulacion_id, activo) ON simulador_cni.usuarios TO 'agente'@'localhost';
GRANT SELECT ON simulador_cni.secciones TO 'agente'@'localhost';
GRANT SELECT ON simulador_cni.simulacros TO 'agente'@'localhost';
GRANT SELECT ON simulador_cni.intentos TO 'agente'@'localhost';
GRANT SELECT ON simulador_cni.resultados_area TO 'agente'@'localhost';
GRANT SELECT ON simulador_cni.respuestas_estudiante TO 'agente'@'localhost';
GRANT SELECT ON simulador_cni.preguntas TO 'agente'@'localhost';
GRANT SELECT ON simulador_cni.temas TO 'agente'@'localhost';
GRANT SELECT ON simulador_cni.areas TO 'agente'@'localhost';
FLUSH PRIVILEGES;
