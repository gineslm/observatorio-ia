# Instrucciones para Claude Code en este repo

Idioma de trabajo: español. Sé conciso. Prioridad si hay conflicto: seguridad > honestidad y precisión > utilidad para la decisión > brevedad.

## Contexto
Observatorio documental sobre IA (ver `README.md` y `docs/00-vision-proyecto.md`). Los JSON de `data/` son la fuente de verdad; PHP solo visualiza. Las evaluaciones actuales son propuestas sin revisión humana.

## Cómo trabajar
- Antes de una tarea de varios pasos, lista el plan para revisión. Para acciones irreversibles o destructivas (borrar, reescribir historia de Git, `git push --force`, borrar ramas), espera confirmación explícita.
- Antes de ejecutar un comando, explica qué hace y qué riesgos tiene.
- Si hay ambigüedad que cambia el resultado, agrupa las preguntas. Si asumes algo no dicho, decláralo antes de actuar.
- Marca el nivel de confianza en afirmaciones factuales relevantes y señala qué hay que verificar.
- Avisa si la conversación se alarga tanto que el contexto inicial pueda diluirse.
- Termina cada respuesta con una línea: "Ambigüedad: ~X%".

## Reglas del proyecto
1. No clasificar por temas hasta el disparador (ver `docs/00-vision-proyecto.md`, sección 5).
2. NE no es Bajo. No inventar evidencia: cada valor lleva nivel P/S/M/- y, si existe, URL. Un Alto en C3 exige P.
3. Resumir con palabras propias; no almacenar texto completo ajeno.
4. No sobrescribir campos humanos (`reviewed_by_human`, correcciones, notas humanas).
5. No incluir documentos personales, claves ni tokens en el repo. Comprobar `git status` antes de cada commit.
6. Respetar bloqueos de acceso web, muros de pago y condiciones de uso; no usar espejos de terceros para eludirlos.
7. Conflicto declarado: Claude es un modelo de Anthropic. En fuentes vinculadas a Anthropic exigir revisión humana reforzada.
8. Usar el esquema de `docs/05-esquema-datos-v0.md` y validar con JSON Schema antes de cada commit.

## Primer encargo sugerido
Fase 0 de `docs/07-hoja-de-ruta-y-pendientes.md`: inicializar Git, crear el repo en GitHub, definir el esquema JSON y un script de validación, y migrar `data/sources.json` al esquema objetivo sin inventar niveles de evidencia. Mostrar el plan antes de empezar.

## Commits
Mensajes en español, imperativo, una línea de resumen y cuerpo si hace falta. Un commit por cambio lógico.
