# Despliegue manual en Oracle Cloud

El repositorio no publica cambios automaticamente al hacer git push. Este workflow permite desplegar manualmente la rama OFICIAL#1 mediante SSH. No copia .env ni storage y no borra archivos del servidor.

## Requisitos del servidor

- VM Linux con SSH accesible desde GitHub Actions.
- PHP 8.2 o superior, Composer y un directorio de aplicacion ya creado.
- .env existente en el servidor, con APP_ENV=production, APP_DEBUG=false, APP_URL, conexion a la base existente y GEMINI_API_KEY configurada.
- El usuario SSH debe poder escribir el directorio y ejecutar PHP/Composer.
- El servidor debe tener rsync. La huella SSH se registra de forma verificada en known_hosts.
- La base de datos debe tener respaldo antes de aplicar migraciones.

## Secrets de GitHub

En Settings > Secrets and variables > Actions, crea estos repository secrets:

- ORACLE_DEPLOY_HOST: host SSH de la VM (no la URL publica si SSH usa otra direccion).
- ORACLE_DEPLOY_USER: usuario SSH.
- ORACLE_DEPLOY_KEY: clave privada SSH de despliegue; no compartirla en el chat.
- ORACLE_DEPLOY_KNOWN_HOSTS: entrada known_hosts con la huella SSH verificada por el administrador.
- ORACLE_APP_PATH: ruta absoluta a la raiz Laravel; el directorio debe existir y conservar su .env y storage.

## Ejecutar el despliegue

1. Abre Actions > Deploy to Oracle server > Run workflow.
2. Selecciona la rama OFICIAL#1.
3. Deja run_migrations y seed_demo_data desactivados salvo que se haya revisado el cambio y respaldado la base.
4. Para una base demo aislada, seed_demo_data requiere ademas en el .env remoto CNI_DOCENTE_DEMO_SEED_ENABLED=true y CNI_DOCENTE_DEMO_DATABASE igual al nombre exacto de esa base. El usuario docente_demo debe existir y estar asignado a una seccion demo. No ejecutar con calificaciones reales.

El workflow solo se inicia manualmente en OFICIAL#1; no corre automaticamente en cada push. Al terminar, probar /docente con una cuenta docente activa.