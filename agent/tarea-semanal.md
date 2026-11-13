# Tarea programada de ampliación (prompt para Claude Desktop)

Copiar el bloque de abajo como instrucciones de una tarea programada en Claude Desktop, con acceso a la carpeta del repo.

Requisito previo (una vez, en tu terminal): `.venv/Scripts/python scripts/sync.py guardar-clave`, para que la tarea pueda conectar con el servidor sin pedir contraseña.

Flujo fijo: **actualizar desde el servidor → ampliar → validar → publicar**. Si un paso falla, la tarea se detiene sin publicar.

---

```
Trabajas en el repo del Observatorio IA (carpeta local del proyecto). Lee CLAUDE.md y docs/08-arquitectura-servidor.md antes de empezar y respeta sus reglas. Idioma: español.

Autores de los ficheros (no los mezcles):
- data/items/feed/        → SOLO el servidor. Nunca lo edites en local.
- data/items/consulta/    → tú (piezas encontradas por búsqueda).
- data/enrichment/        → tú (títulos traducidos, resúmenes, keywords traducidas, content_type).
- data/feedback/          → SOLO la persona responsable. Léelo, nunca lo modifiques.

1. ACTUALIZAR
   a. `git status`: si hay cambios sin commit, para y avisa.
   b. Si existe remoto git: `git pull --rebase`. Si falla, para y avisa.
   c. `.venv/Scripts/python scripts/sync.py bajar`. Si falla, para y avisa (no sigas con datos viejos).
2. LEER INTERESES: cruza data/feedback/interest.json con las piezas y resume qué fuentes, tipos y keywords acumulan más ✓ y más ✗.
3. ENRIQUECER: para cada pieza (feed o consulta) sin entrada completa en data/enrichment/AAAA-MM.json:
   - Abre la URL. Si hay muro de pago, bloqueo o condiciones que lo impidan, no lo eludas: deja summary en null y anótalo.
   - i18n.es / i18n.en: "title" (traducción fiel; si el original ya está en ese idioma, cópialo), "summary" (1 a 3 frases con palabras propias; nunca copies frases del original ni del extracto), "keywords" (traducción de source_keywords en el mismo orden).
   - "content_type" solo si es evidente (noticia, informe, analisis, opinion, traduccion, dataset, otro).
   - No toques "labels" (clasificación temática diferida) ni campos con revisión humana.
4. BUSCAR (máximo 8 piezas): publicaciones de la última semana de fuentes YA registradas en data/sources.json sin feed, priorizando los patrones con más ✓ y evitando los de más ✗ (sin cerrar del todo el foco). Comprueba fecha, autoría y acceso libre abriendo cada página. Añádelas a data/items/consulta/AAAA-MM.json con id P-AAAA-MM-C#### (siguiente número libre), entry_reason {"channel": "consulta", "detail": "<consulta>"} y su entrada en data/enrichment/. No añadas fuentes nuevas: proponlas en el informe.
5. Fuentes vinculadas a Anthropic (p. ej. Import AI, Transluce, o piezas sobre Anthropic): resume, pero márcalo en el informe para revisión humana reforzada.
6. VALIDAR: `.venv/Scripts/python scripts/validate.py`. Si hay errores, corrígelos en tus ficheros o para y avisa.
7. PUBLICAR
   a. `git status` (nada de private/, claves ni documentos personales) y commit: "Ampliar piezas AAAA-MM-DD".
   b. `.venv/Scripts/python scripts/sync.py subir`. Si falla, para y avisa.
   c. Si existe remoto git: `git push`.
8. INFORME (en tu respuesta): patrones de interés, piezas nuevas por fuente (feed y consulta), resúmenes pendientes y por qué, feeds con error (data/logs/), fuentes candidatas y dudas para revisión humana.
```
