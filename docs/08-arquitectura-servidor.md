# Arquitectura servidor + equipo local

Estado: 2026-11-13. **Implementado en local, sin desplegar.** Diagnóstico de Aruba hecho y favorable; falta confirmar el cron y desplegar.

## 0. Puesta en marcha (lo ejecuta la persona responsable en su terminal)
1. Diagnóstico: `.venv/Scripts/python scripts/ftps.py diag-subir`, abrir la URL que devuelve, revisar el resultado, abrirla de nuevo con `&limpiar=1` y después `diag-borrar`.
2. Guardar la contraseña FTPS en el Administrador de credenciales de Windows: `.venv/Scripts/python scripts/sync.py guardar-clave`.
3. Desplegar el código: `sync.py desplegar`. Genera `CRON_TOKEN` en `private/servidor-aruba.env` y protege `data/`, `inc/` y `lang/` con `.htaccess`.
4. Primera carga de datos: `sync.py sembrar` (piezas de feed existentes, solo una vez) y `sync.py subir` (fuentes, consulta y enriquecimiento).
5. Programar la captura:
   - Si el panel de Aruba tiene cron: `php <ruta>/cron/fetch_feeds.php`. La ruta de PHP sale del diagnóstico.
   - Si no lo tiene: un servicio externo de cron que haga POST a `<URL>/cron/fetch_feeds.php` con la cabecera `X-Cron-Token: <CRON_TOKEN>`.
6. Programar en Claude Desktop la tarea de `agent/tarea-semanal.md`.

**Implementado:** reparto por autor (`items/feed`, `items/consulta`, `enrichment`) con ids `F`/`C`, `cron/fetch_feeds.php` (CLI o POST con token, bloqueo, escritura atómica, logs), `scripts/sync.py` (`bajar`, `subir`, `desplegar`, `sembrar`, `guardar-clave`), `.htaccess` en `deploy/`. El marcador ✓/✗ solo funciona con `php -S` en local; en el hosting no aparece y `api/` no se despliega.

## 1. Lo decidido por la persona responsable
- El servidor de producción es un **hosting compartido Linux de Aruba con PHP**.
- Cuando esté online, **PHP ejecuta acciones automáticas**: lee los RSS y escribe los hallazgos en JSON.
- Los **JSON son la fuente de datos**. Más adelante, si todo va bien, se pasará a **SQL**.
- **Segunda vía de entrada**: acciones programadas desde el equipo local que amplían esos JSON (búsquedas, resúmenes, traducciones).
- Toda acción programada sigue el orden: **actualizar desde el remoto → ampliar → publicar**.
- Los resúmenes y traducciones los redacta la **tarea local** (sin API de IA en el servidor).
- Se valora **prescindir de GitHub** y trabajar local ↔ Aruba.

## 2. Recomendación sobre Git y GitHub
- **Mantener Git en local** aunque se elimine GitHub: el historial permite deshacer errores del agente y auditar el gold set.
- GitHub queda como **copia de seguridad opcional**. Opciones: mantenerlo, pasarlo a privado o eliminarlo. Lo decide la persona responsable; no se toca sin confirmación.
- Si se elimina GitHub, la decisión 10 de `01-decisiones.md` se reescribe así: «Datos en JSON en el servidor; historial en Git local».

## 3. Principio clave: un solo autor por fichero
Servidor y equipo local escriben a la vez sobre los mismos datos. Para no pisarse, cada fichero tiene un único autor:

| Fichero | Lo escribe | Contenido |
|---|---|---|
| `data/items/feed/AAAA-MM.json` | Servidor (cron) | Piezas de RSS: metadatos y keywords de la fuente, sin resumen |
| `data/items/consulta/AAAA-MM.json` | Tarea local | Piezas encontradas por búsqueda |
| `data/enrichment/AAAA-MM.json` | Tarea local | Por id de pieza: `i18n.es`/`i18n.en` (título, resumen, keywords) y `content_type` |
| `data/feedback/interest.json` | Persona responsable (portal local) | Marcas ✓/✗ |
| `data/sources.json`, `data/gold-set/*` | Equipo local (humano o agente) | Registro y correcciones |

- El portal combina al leer: pieza (feed o consulta) + enriquecimiento por id.
- Los ids no deben chocar. Propuesta: `P-AAAA-MM-F####` para el servidor y `P-AAAA-MM-C####` para la consulta local. Implica ajustar el patrón del esquema y migrar las 23 piezas actuales.

## 4. Flujos

### 4.1 Servidor (Aruba)
1. Un cron lanza `cron/fetch_feeds.php`. Si el panel no lo permite, se usa un cron externo que llame a una URL protegida con token, o la propia tarea local lo dispara.
2. Bloqueo (`flock`) para que no se ejecute dos veces a la vez.
3. Lee los feeds de `sources.json`, descarta duplicados por URL y añade las piezas nuevas a `data/items/feed/AAAA-MM.json`.
4. Registra la ejecución en `data/logs/` (fecha, feeds caídos, piezas nuevas). Los logs no se suben a ningún repo público.

### 4.2 Tarea local programada (Claude Desktop)
1. **Actualizar**: descargar del servidor los ficheros cuyo autor es el servidor y hacer commit local («Sincronizar datos del servidor»). Si falla, parar.
2. **Ampliar**: leer marcas, buscar piezas, escribir resúmenes y traducciones en `enrichment` y `consulta`.
3. **Validar** con JSON Schema. Si hay errores, parar sin publicar.
4. **Publicar**: subir al servidor **solo los ficheros cuyo autor es el equipo local**, hacer commit local y, si se mantiene GitHub, `git push`.
5. Informe: piezas nuevas, pendientes, feeds caídos y patrones de interés.

### 4.3 Código del portal
- Se despliega desde el equipo local subiendo `public/` y `cron/`.
- Nunca se suben `.venv/`, `private/`, `.git/` ni `scripts/` de desarrollo.
- El marcador ✓/✗ sigue siendo solo local. En el servidor no aparece y su endpoint rechaza las peticiones.

## 5. Comprobaciones en Aruba (antes de programar)
Confianza baja-media: depende del plan contratado. Hay que verificarlo en el panel o con soporte.

**Ya conocido (2026-11-13, de otro proyecto en el mismo hosting).** Host, usuario, carpeta y URL están en `private/servidor-aruba.env`, que no se versiona; la contraseña nunca se guarda:
- El certificado TLS del FTP compartido está a nombre del servidor de Aruba, no del dominio. `scripts/ftps.py` conecta al host del dominio pero verifica el certificado contra `FTP_TLS_NAME` (en `private/`): sigue comprobando la autoridad de certificación y el nombre exacto. No se usa `--sin-verificar`.
- Transferencia por **FTPS explícito** en el puerto 21. En Python: `ftplib.FTP_TLS` y después `prot_p()`. No consta que haya SFTP ni SSH, así que no se puede usar git en el servidor y el despliegue y la sincronización se hacen por FTPS.
- El portal irá en una **subcarpeta del dominio**, todavía no creada. Al estar dentro del document root, hay que proteger `data/`, `cron/` e `inc/` del acceso web.

**Resultado del diagnóstico (2026-11-13), ya borrado del servidor:**
- PHP 8.4 (FPM) sobre Apache. Memoria 1024 MB y ejecución máxima 900 s.
- Extensiones: curl, openssl, mbstring, json, simplexml, dom, zip, pdo_mysql y **pdo_sqlite**. SQLite sirve para el futuro paso a SQL sin contratar base de datos.
- **cURL funciona**: lee el RSS de prueba (HTTP 200, 10 items). `allow_url_fopen` está desactivado; no afecta, porque el lector usa cURL.
- PHP **puede escribir** en la carpeta del portal.
- **`.htaccess` protege** las carpetas (403).
- `exec`, `shell_exec`, `proc_open` y `system` están **desactivados**: no hay git ni comandos en el servidor. La sincronización va por FTPS, como estaba previsto.
- El acceso FTP requirió revisar el filtro de accesos o la contraseña en el panel (error 530 la primera vez).

**Pendiente:**
1. **Cron / tareas programadas** para PHP: ¿existe en el panel? ¿con qué frecuencia mínima? Si no existe, usar un cron externo por HTTP POST con token. Ya está implementado y no requiere `exec`.
2. **Salida HTTP desde PHP**: cURL o `allow_url_fopen` activos, necesarios para leer los RSS.
3. ~~Acceso de transferencia~~: resuelto, FTPS explícito.
4. **Versión de PHP** y extensiones disponibles: `mbstring`, `json`, `simplexml`/`dom`, `curl`.
5. **Escritura en disco**: ¿PHP puede escribir en la carpeta de datos? Mejor fuera del document root, si el plan lo permite.
6. **Protección de `data/`**: está dentro del document root, así que hay que bloquear el acceso web con `.htaccess` (`Require all denied`). Hay que comprobar que Aruba usa Apache y respeta `.htaccess`.

Para resolver los puntos 1, 2, 4, 5 y 6 de una vez: subir un `diagnostico.php` temporal que informe de la versión de PHP, las extensiones, `allow_url_fopen`/cURL, una petición de prueba a un RSS, si puede escribir en disco y si `.htaccess` bloquea una carpeta de prueba. Se borra en cuanto se lea el resultado.

## 6. Seguridad
- Los datos de conexión (sin contraseña) están en `private/servidor-aruba.env`. La contraseña FTPS la introduce la persona responsable al desplegar o la toma del gestor de credenciales del sistema (p. ej. `keyring` en Python). El token del cron, si lo hay, también va en `private/`. Nada de esto se guarda en el repo ni en el código.
- El repo de GitHub es público: el host, el usuario y la ruta del servidor no deben aparecer en ningún fichero versionado.
- La URL del cron, si se expone, exige token y solo acepta POST o una cabecera concreta.
- Los ficheros cuyo autor es el servidor se validan en local antes de integrarlos: si el JSON del servidor no valida, se para.

## 7. Paso a SQL (más adelante)
- Solo cambian la capa de acceso (`load_sources`, `load_items`, `load_feedback` en `public/inc/`) y la escritura de `cron/fetch_feeds.php`.
- Se conserva la exportación a JSON para el historial en Git y para la tarea local.
- Disparador sugerido: cuando el volumen haga lentas las búsquedas en el portal, o cuando haya escrituras concurrentes que el reparto de autores no cubra.

## 8. Plan de implementación sugerido (sesión nueva)
1. Responder las comprobaciones de Aruba (sección 5).
2. Decidir el destino de GitHub (sección 2).
3. Migrar los datos al reparto por autores: `items/feed`, `items/consulta`, `enrichment` y nuevos ids. Ajustar esquemas, validador y portal.
4. Portar el lector de RSS a PHP (`cron/fetch_feeds.php`) y probarlo en local con `php cron/fetch_feeds.php`.
5. Script local de sincronización (descargar, validar y subir por autor) y actualización de `agent/tarea-semanal.md`.
6. Primer despliegue en Aruba y prueba del ciclo completo.
7. Actualizar `01-decisiones.md` (decisiones 10 y 16) y `07-hoja-de-ruta-y-pendientes.md`.
