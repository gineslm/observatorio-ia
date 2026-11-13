# Arquitectura servidor + equipo local (propuesta, pendiente de decisión)

Estado: 2026-11-13. Propuesta del asistente a partir de lo indicado por la persona responsable. **No implementado.** Sirve de punto de partida para una sesión nueva.

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
1. **Cron / tareas programadas** para PHP: ¿existe en el panel? ¿con qué frecuencia mínima?
2. **Salida HTTP desde PHP**: cURL o `allow_url_fopen` activos, necesarios para leer los RSS.
3. **Acceso de transferencia**: ¿SFTP o solo FTP? ¿hay SSH?
4. **Versión de PHP** y extensiones disponibles: `mbstring`, `json`, `simplexml`/`dom`, `curl`.
5. **Escritura en disco**: ¿PHP puede escribir en la carpeta de datos? Mejor fuera del document root, si el plan lo permite.
6. **Protección de `data/`**: si tiene que estar dentro del document root, bloquear el acceso web con `.htaccess`.

## 6. Seguridad
- Las credenciales de FTP/SFTP y el token del cron, si lo hay, van en `private/` (ignorado por Git) o en el gestor de credenciales del sistema. Nunca en el repo ni en el código.
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
