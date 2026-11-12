# Observatorio de IA (nombre pendiente)

Observatorio documental sobre inteligencia artificial como fenómeno sociotécnico. Registra fuentes y publicaciones, las resume con palabras propias, evalúa su calidad con una rúbrica explícita y sigue su evolución. Uso personal y profesional, sin monetización. Repositorio público de momento; los JSON son la fuente de verdad y PHP solo visualiza.

Estado: v0, 2026-11-12. Paquete de arranque generado en Claude.ai para continuar en Claude Code.

## Estructura

```
README.md                         este archivo
CLAUDE.md                         instrucciones para Claude Code en este repo
.gitignore
docs/
  00-vision-proyecto.md           visión detallada, riesgos y hoja de ruta
  01-decisiones.md                decisiones tomadas
  02-rubrica-calidad-v0.md        rúbrica C1-C5, escala y reglas
  03-perfil-editorial-v0.md       perfil público (reconstruido; revisar)
  04-resultados-registro-semilla.md  resultados de las 21 fuentes
  05-esquema-datos-v0.md          esquema de fuentes, piezas y correcciones
  06-agente-instrucciones-v0.md   borrador de instrucciones del agente
  07-hoja-de-ruta-y-pendientes.md fases, dependencias y pendientes
data/
  sources.json                    registro semilla (21 fuentes), esquema v1.0
  items/                          piezas por mes (vacío)
  gold-set/                       correcciones humanas y pruebas de deriva
agent/                            espacio para el prompt/skill del agente
schemas/                          JSON Schema de fuentes, piezas y correcciones
scripts/validate.py               validación (ejecutar antes de cada commit)
public/index.php                  visor PHP de solo lectura
```

## Primeros pasos en Claude Code

1. Descomprimir el zip en una carpeta de trabajo y abrirla en Claude Code.
2. Revisar `CLAUDE.md` y `docs/00-vision-proyecto.md`.
3. Inicializar Git y crear el repo en GitHub. Pedir a Claude Code que explique cada comando antes de lanzarlo (por ejemplo `git init`, `git add`, `git commit` y `gh repo create`). Ninguno es destructivo, pero `gh repo create` crea algo en la cuenta de GitHub, así que debe confirmarse.
4. Antes del primer commit, comprobar que no se incluye nada privado (ver `.gitignore`).
5. Seguir `docs/07-hoja-de-ruta-y-pendientes.md`, fase 0.

## Ver el registro

```
php -S 127.0.0.1:8000 -t public
```

Y abrir http://127.0.0.1:8000. Solo lectura: filtra por texto, relevancia y valor de criterio.

## Validar los datos

```
python -m venv .venv
.venv/Scripts/python -m pip install -r requirements.txt
.venv/Scripts/python scripts/validate.py
```

Los errores bloquean; los avisos (p. ej. C3=Alto sin evidencia P en fuentes no revisadas) no.

## Qué NO está en este paquete
- Los documentos personales de la persona responsable (perfil de intereses y perfil motivacional). Son privados; si se usan como semillas de búsqueda, guardarlos fuera del repo o en una carpeta ignorada.
- Credenciales, claves de API o tokens.
- Texto completo de publicaciones ajenas.

## Aviso importante
Todas las evaluaciones de `data/sources.json` son **propuestas del asistente sin revisión humana**. Antes de apoyarse en ellas hay que revisarlas y convertir las correcciones en el gold set. Claude (el asistente que las hizo) es un modelo de Anthropic y tiene un vínculo comercial con alguna fuente evaluada; ver `docs/01-decisiones.md`.
