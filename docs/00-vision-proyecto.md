# Observatorio de IA: visión del proyecto (v0)

Estado a 2026-11-12. Documento de referencia para crear el repositorio y continuar el trabajo en Claude Code. Resume lo decidido, lo construido, lo aprendido y lo pendiente. Los detalles de cada tema están en los demás documentos de `docs/`.

**Cómo leer este documento.** Cada afirmación lleva su origen implícito: lo decidido por la persona responsable está en `01-decisiones.md`; lo evaluado por el asistente (Claude) en `04-resultados-registro-semilla.md` y `data/sources.json` es una **propuesta pendiente de revisión humana**, no un hecho verificado. Donde se cita memoria o inferencia se marca.

---

## 1. Propósito

Un observatorio documental sobre inteligencia artificial como fenómeno sociotécnico: registra fuentes, selecciona publicaciones (noticias, investigación, recursos), las resume con palabras propias, las evalúa y sigue su evolución. Es un lugar único donde ver novedades y localizar recursos, y donde saber qué fuentes y canales son más activos.

- **Uso:** personal y profesional. Sin monetización y sin competir con agregadores existentes.
- **Identidad:** el portal tiene identidad propia, sin referencias personales. Nombre pendiente.
- **Lenguas:** originales en español e inglés; chino, italiano y otras lenguas, traducidos (incluida China).
- **Cadencia:** semanal, con un día de revisión humana.
- **Gasto:** evitar gastos adicionales. Alojamiento Linux con PHP y MySQL ya disponible.

### Lo que es y lo que no es
- Es un **observatorio** (registro, seguimiento, evaluación de calidad de fuentes), no un think tank: no produce posicionamiento propio ni conclusiones.
- La línea editorial decide **dónde mirar**, no **qué conclusión sacar**. El juicio crítico se ejerce al leer.
- No hay cuota de contraste ideológico. En su lugar, auditoría periódica del registro.
- No hay ejes temáticos como compuerta: nada se descarta por no encajar en una categoría previa.

## 2. Perímetro (decidido)

**Tecnología y transformación.** La inteligencia artificial como fenómeno sociotécnico (su aparición, desarrollo y consolidación) y sus relaciones con las personas, las sociedades y el entorno material. Incluye otras tecnologías cuando condicionan, preceden o iluminan ese fenómeno.

Hay criterios internos de inclusión que se guardan fuera del repo.

## 3. Principios de evaluación

1. **Dos ejes independientes:** calidad epistémica y pertinencia al perímetro. Ninguno es ideológico.
2. Cada pieza lleva una etiqueta descriptiva de **qué argumenta**, sin puntuar posiciones.
3. Criterios de calidad: autoría identificable, citas verificables, transparencia de financiación e intereses, reconocimiento de incertidumbre, distinción entre hecho, análisis y opinión.
4. Resúmenes con palabras propias y enlace al original; nunca reproducir texto ajeno.
5. NE (no evaluable) significa "no se pudo verificar": nunca equivale a Bajo y no penaliza.
6. Una fuente puede tener valor parcial sin pasar todos los criterios.

## 4. Arquitectura propuesta

- **Fuente de verdad:** repositorio Git (público de momento). Datos en JSON: `data/sources.json`, `data/items/AAAA-MM.json`.
- **Visualización:** PHP solo lee y muestra los JSON. No contiene lógica de evaluación.
- **Evolución:** Claude.ai o GPT para pensar y decidir; Claude Code para actualizar el repo.
- **Agente de catalogación:** en lugar de un especialista humano, un agente con vocabularios controlados, ficheros versionados y un conjunto de referencia (gold set) con las correcciones de la persona responsable; prueba periódica de deriva. Ver `06-agente-instrucciones-v0.md`.
- **Estrategia de catalogación ("opción 2", abierta):** capa propia fina sobre identificadores abiertos (DOI, arXiv, OpenAlex) más una tabla propia para literatura gris.
- **Infraestructura de metadatos candidata:** OpenAlex (CC0, clave API gratuita; hay información contradictoria sobre si es obligatoria: verificar al construir), Semantic Scholar, CORE/BASE/Zenodo; Dublin Core y el NHS Grey Literature Minimum Data Set como referencia de campos para literatura gris.
- **Descartado de momento:** suscripciones a Policy Commons y Overton (precio opaco, licencia propietaria), incluida la prueba gratuita. Se retoma si la estrategia propia no da buenos resultados.

## 5. Clasificación diferida y disparadores

Las fuentes y piezas se reciben **sin clasificar por temas**. Los metadatos objetivos (fechas, fuente, lengua, URL, resumen, calidad epistémica, "qué argumenta") no cuentan como clasificación temática. De cada pieza se guarda **por qué entró** (feed, consulta o búsqueda manual) para analizar luego el sesgo de selección.

Disparadores, contados desde la primera captura semanal:

- **Revisión técnica a las 50 piezas.** Comprobar que los campos se rellenan bien y que se guarda el motivo de entrada. No se clasifica nada.
- **Primer análisis de categorías**, lo primero que ocurra de: 150 piezas capturadas o 6 semanas de captura, siempre que haya al menos 10 fuentes distintas.
- Si antes aparece la necesidad de clasificar algo, se anota como dato para el diseño de categorías.
- Cifras orientativas, revisables tras el primer ciclo.

Al definir categorías se partirá de dos análisis que se contrastan: (1) del propio proyecto (top-down) y (2) de los materiales capturados (bottom-up). Modelo candidato de facetas independientes: tipo de entidad, tipo de fuente, naturaleza del contenido, origen, relación con el perímetro, ámbito temático, estado de etiqueta.

## 6. Calidad de fuentes: rúbrica v0

Detalle en `02-rubrica-calidad-v0.md`. Resumen:

- Escala Alto / Medio / Bajo / NE.
- Niveles de evidencia: **P** página oficial, **S** tercero, **M** memoria o conocimiento previo sin verificar, **-** ninguna. Un Alto en C3 exige evidencia P.
- Criterios: C1 autoría y trayectoria; C2 verificabilidad; C3 transparencia de financiación e intereses; C4 reconocimiento de incertidumbre; C5 distinción entre hecho, análisis y opinión.
- Evaluación en dos fases: institucional (C1, C3: desde páginas "acerca de" y de financiación) y por muestreo (C2, C4, C5: leyendo unas 3 piezas por fuente).
- Toda evaluación lleva fecha, nivel de evidencia y versión de la rúbrica. La financiación se reevalúa cada 12 meses.

## 7. China y fuentes en otras lenguas

- **Nivel 1 (intermediarios en inglés, ya en el registro):** ChinAI, DigiChina, Concordia AI, CSET; MERICS como perspectiva europea.
- **Nivel 2 (portales chinos, sin probar):** TC260, CAICT, QbitAI, Jiqizhixin, 新智元. Pendiente de una prueba de lectura.
- **WeChat:** no automatizable.
- Hallazgo útil: ChinAI enlaza los originales de WeChat y CAICT; DigiChina enlaza originales de Xinhua y CAICT; CSET archiva el original en perma.cc. El nivel 1 sirve de puerta de entrada al nivel 2.

## 8. Resultados del registro semilla (resumen)

21 fuentes evaluadas (16 núcleo, 5 adyacentes) el 2026-11-12. Detalle y limitaciones en `04-resultados-registro-semilla.md`.

- C1 discrimina poco: 18 Alto y 3 Medio. Era esperable porque las semillas son fuentes reconocidas.
- No hay Bajo salvo uno (C4 en AlgorithmWatch, dudoso por el género de incidencia). La rúbrica **no se ha puesto a prueba con fuentes de baja calidad**.
- C5 es el criterio más difícil de separar: 11 de 21 en Medio.
- C4 y C5 se miden sobre muestras de 1 a 3 piezas: provisional.
- Ninguna fuente semilla es en español, catalán o del Sur Global, lo que contradice el principio de pluralidad. Es el hueco más claro del registro.
- Conflictos de interés a vigilar: Import AI (autor cofundador de Anthropic, y Claude es de Anthropic), Transluce (colabora con OpenAI, Anthropic y Google DeepMind), AI Index (financiación de industria), CSET (dependencia de un financiador), AIID (patrocinios previstos de empresas de cumplimiento).

## 9. Riesgos principales

1. **Sesgo de evaluador único.** Todas las evaluaciones las ha hecho el mismo modelo, que además tiene un vínculo comercial con una de las fuentes. Mitigación: revisión humana, gold set, prueba de deriva.
2. **Rúbrica sin calibrar.** Sin fuentes malas ni correcciones humanas no se sabe si discrimina.
3. **Penalizar el género.** C4 puede castigar la incidencia política por tono, no por calidad. Decisión pendiente.
4. **Sesgo de selección y anglosajón.** El registro semilla es casi todo estadounidense o europeo y en inglés.
5. **Acceso técnico.** Algunas webs bloquean la lectura automatizada o están tras muro de pago; el agente necesitará una vía alternativa (RSS, APIs, navegador).
6. **Alcance.** El proyecto puede crecer más de lo que cabe en una revisión semanal. Mantener la revisión humana en un día fijo limita el riesgo.
7. **Derechos de autor.** Resumir con palabras propias y enlazar; no almacenar texto completo ajeno.

## 10. Hoja de ruta propuesta

Detalle y dependencias en `07-hoja-de-ruta-y-pendientes.md`. Orden recomendado:

1. Crear el repo con esta estructura y los documentos.
2. Revisión corta del registro semilla por la persona responsable (8 a 10 celdas clave) para arrancar el gold set.
3. Cerrar el esquema de piezas (`05-esquema-datos-v0.md`) y aplicar los 8 ajustes de esquema de fuentes.
4. Prueba de ingesta con 3 a 5 fuentes (RSS y páginas), incluida una china de nivel 2.
5. Primer ciclo semanal de captura y revisión humana hacia las 50 piezas.
6. Ampliar el registro con fuentes en español y del Sur Global, y con fuentes de calidad dudosa para calibrar la rúbrica.
7. Revisión técnica a 50 piezas; primer análisis de categorías al disparador.

## 11. Qué falta decidir (resumen)

- Nombre del observatorio.
- Si C4 se mide en piezas de incidencia política o solo en piezas que afirman resultados empíricos (recomendación del asistente: solo en las segundas).
- Campos mínimos de captura de piezas (propuesta en `05`).
- Prueba de lectura de fuentes chinas de nivel 2 y WeChat.
- Qué vía de acceso usar con webs bloqueadas.

## 12. Límites de este documento

- El perfil editorial público (`03`) se ha reconstruido a partir de las decisiones registradas; la versión original se redactó en una conversación anterior. Revisar el tono antes de publicar nada.
- El esquema de datos (`05`) es una propuesta nueva basada en las decisiones y en los 8 ajustes; no es la versión inicial exacta.
- Los documentos personales de la persona responsable (perfil de intereses y perfil motivacional) **no se incluyen** en este paquete: son privados y contienen términos personales que deben quedar fuera de textos públicos. Si hay que usarlos como semillas privadas, guardarlos en una carpeta no versionada o en un repo aparte.
