# Observatorio IA: registro de decisiones (v0)

Estado a 2026-11-12. Documento vivo: se actualiza solo cuando cambia una decisión. Copia del documento del Proyecto `claude/observatorio-ia-decisiones.md` con las secciones de perímetro, principios y decisiones.

## Concepto
Observatorio documental sobre inteligencia artificial: registra fuentes, selecciona publicaciones, las resume, las evalúa y sigue su evolución. Uso personal y profesional, sin ánimo de monetización. Identidad propia, sin referencias personales. Nombre pendiente.

## Perímetro (decidido)
**Tecnología y transformación.** La inteligencia artificial como fenómeno sociotécnico (su aparición, desarrollo y consolidación) y sus relaciones con las personas, las sociedades y el entorno material. Incluye otras tecnologías cuando condicionan, preceden o iluminan ese fenómeno.

## Principios
- La línea editorial decide dónde mirar, no qué conclusión sacar. El juicio crítico se ejerce al leer.
- Dos ejes de evaluación independientes: calidad epistémica y pertinencia al perímetro. Ninguno es ideológico.
- Cada pieza lleva una etiqueta descriptiva de qué argumenta, sin puntuar posiciones.
- Sin ejes-compuerta: nada se descarta por no encajar en una categoría previa.
- Criterios de calidad: autoría identificable, citas verificables, transparencia de financiación e intereses, reconocimiento de incertidumbre, distinción entre hecho, análisis y opinión.
- Pluralidad: originales en español e inglés; chino, italiano y otras lenguas traducidos.
- Resúmenes con palabras propias y enlace al original; nunca reproducir texto ajeno.

## Decisiones
1. Identidad propia del portal.
2. Solo criterios y temas; sin declaración de compromisos políticos.
3. Observatorio, no think tank.
4. Sin cuota de contraste; auditoría periódica del registro.
5. Sin ejes como compuerta; clasificación interna y revisable.
6. Perímetro "Tecnología y transformación" con la regla de otras tecnologías.
7. Perfil público v0 sin mención a centro de investigación; los criterios internos de inclusión quedan fuera del repo.
8. Datos privados (semillas de búsqueda, pesos) fuera del texto público.
9. Cadencia semanal; un día de revisión humana.
10. Datos en repo Git como fuente de verdad (público desde 2026-11-12); PHP solo visualiza.
11. Clasificación temática diferida, con disparador fijado (ver `00-vision-proyecto.md`, sección 5).
12. Estrategia de catalogación "opción 2" (abierta): capa propia fina sobre identificadores abiertos (DOI, arXiv, OpenAlex) más tabla propia para literatura gris.
13. Agente en lugar de especialista: no habrá especialista humano. Se diseña un agente con vocabularios controlados, ficheros versionados y un conjunto de referencia (gold set) con las correcciones humanas; prueba periódica de deriva.
14. Suscripciones pospuestas (Policy Commons, Overton), incluida la prueba gratuita. Se retoma si la estrategia propia no da buenos resultados.
15. Evaluación de fuentes en dos fases (2026-11-12). Las fuentes con páginas inaccesibles se quedan en NE hasta que se aporte el contenido o se encuentre otra vía.
16. Marcador de interés (2026-11-13): la persona responsable marca piezas como interesantes o no desde el portal local (✓/✗). Única escritura permitida desde PHP, solo en local y en `data/feedback/interest.json`; el agente la usa para afinar búsquedas y nunca la modifica.

## Conflictos declarados
- Claude es un modelo de Anthropic. Evaluar fuentes vinculadas a Anthropic (Import AI, Transluce) exige revisión humana reforzada.
- Los documentos personales de la persona responsable (perfil de intereses y perfil motivacional) son privados y no forman parte del repo público.

## Pendiente
- Revisión humana del registro semilla (correcciones = primer gold set) y decisión de calibración de C4.
- Ajuste del esquema de fuentes según la revisión (8 ajustes propuestos en la rúbrica).
- Contenido de las fuentes bloqueadas (AIAAIC, AI Governance DB, AI Index PDF), si la persona responsable lo aporta.
- Nombre del observatorio.
- Campos mínimos de captura (esquema de piezas).
- Prueba de lectura de fuentes chinas (TC260, CAICT, QbitAI) y de WeChat.
- Creación del repo (esquema, registro, rúbrica, perfil editorial, instrucciones del agente).
