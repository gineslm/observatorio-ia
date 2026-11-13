# Tarea semanal de captura (prompt para Claude Desktop)

Copiar el bloque de abajo como instrucciones de una tarea programada semanal en Claude Desktop, con acceso a la carpeta del repo. El resultado queda en un commit **local** para que la persona responsable lo revise antes de hacer `git push`.

---

```
Trabajas en el repo del Observatorio IA (carpeta local del proyecto). Lee CLAUDE.md antes de empezar y respeta sus reglas. Idioma: español.

1. Actualiza: `git pull`. Si hay cambios sin commitear, para y avisa.
2. Lee data/feedback/interest.json (marcas humanas ✓/✗ por pieza; NUNCA lo modifiques). Cruza con data/items/ y resume qué fuentes, tipos de contenido y keywords acumulan más ✓ y más ✗. Úsalo para priorizar el paso 5.
3. Captura feeds: `.venv/Scripts/python scripts/fetch_feeds.py --limit 10 --excerpts`.
4. Para cada pieza de data/items/ con algún texto pendiente en "i18n.es" o "i18n.en":
   - Abre la URL. Si hay muro de pago, bloqueo o condiciones que lo impidan, no lo eludas: deja summary en null y anótalo en el informe.
   - "summary": resumen de 1 a 3 frases con palabras propias, en castellano (es) y en inglés (en). Nunca copies frases del original ni del extracto del feed.
   - "title": traducción fiel del título (si el original ya está en ese idioma, cópialo).
   - "keywords": traducción de "source_keywords" en el mismo orden (los nombres propios se dejan igual).
   - Rellena "content_type" solo si es evidente (noticia, informe, analisis, opinion, traduccion, dataset, otro).
   - No toques "labels" (la clasificación temática está diferida) ni campos con revisión humana.
5. Búsqueda semanal (máximo 8 piezas): busca publicaciones de la última semana de fuentes YA registradas en data/sources.json que no tengan feed, dando prioridad a los temas, fuentes y tipos con más ✓ y evitando los patrones con más ✗ (sin descartar del todo lo distinto, para no cerrar el foco). Comprueba fecha, autoría y acceso libre abriendo cada página. Añádelas con entry_reason {"channel": "consulta", "detail": "<consulta usada>"} y el mismo formato que el script. No añadas fuentes nuevas: proponlas en el informe.
6. Fuentes vinculadas a Anthropic (p. ej. Import AI, Transluce): resume, pero márcalo en el informe para revisión humana reforzada.
7. Valida: `.venv/Scripts/python scripts/validate.py`. Si hay errores, corrígelos o para y avisa.
8. Revisa `git status` (nada de private/, claves ni documentos personales) y haz un commit local:
   "Capturar piezas de la semana AAAA-MM-DD". NO hagas push.
9. Informe final (en tu respuesta): patrones de interés detectados en las marcas, piezas nuevas por fuente, resúmenes pendientes y por qué, feeds caídos, fuentes candidatas y dudas para la revisión humana.
```
