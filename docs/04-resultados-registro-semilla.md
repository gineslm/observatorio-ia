# Resultados del registro semilla v0 (2026-11-12)

Datos completos en `data/sources.json` (y `data/sources.csv`). Todo lo evaluado es una **propuesta del asistente pendiente de revisión humana**; ninguna celda tiene corrección humana todavía.

## Cómo se hizo
- Lista semilla: 21 fuentes (16 núcleo, 5 adyacentes), elegidas para cubrir observatorios y bases de incidentes, centros de investigación, medios, boletines, infraestructura de metadatos y puertas de entrada a China (nivel 1).
- **Fase institucional (C1, C3):** páginas "acerca de" y de financiación, con búsquedas web y lectura de páginas.
- **Fase de muestreo (C2, C4, C5):** lectura de 1 a 3 piezas por fuente cuando fue posible; en algunas, solo del método editorial.
- Limitación de acceso: la herramienta de lectura web solo abre URLs permitidas. Se completó con el navegador Chrome para algunas (Wired, SEP, AI Index). AIAAIC y la web de AI Governance DB no se pudieron leer.

## Distribución de valores (21 fuentes)

| Criterio | Alto | Medio | Bajo | NE |
|---|---|---|---|---|
| C1 Autoría y trayectoria | 18 | 3 | 0 | 0 |
| C2 Verificabilidad | 10 | 6 | 0 | 5 |
| C3 Financiación e intereses | 9 | 9 | 0 | 3 |
| C4 Incertidumbre | 7 | 6 | 1 | 7 |
| C5 Hecho / análisis / opinión | 3 | 11 | 0 | 7 |

## Lectura de los resultados
1. **La rúbrica no se ha puesto a prueba con fuentes malas.** Con casi todo Alto o Medio, no sabemos si distingue calidad baja. Prioridad: añadir fuentes dudosas.
2. **C1 casi no discrimina** (18 Alto). Es esperable con semillas reconocidas.
3. **C5 es el criterio más difícil de separar** (11 Medio). Pocas fuentes etiquetan hechos, análisis y opinión pieza a pieza.
4. **C3 es el más informativo:** nueve Alto (financiadores publicados por nombre en página oficial) frente a nueve Medio (información de terceros, desactualizada o incompleta).
5. **C4 mide en parte el género.** El único Bajo (AlgorithmWatch) proviene de dos piezas de incidencia; probablemente no refleja mala calidad. Ver cuestión abierta en `02-rubrica-calidad-v0.md`.
6. **La calidad varía por pieza dentro de una misma fuente** (Wired, CSET y DigiChina), lo que sugiere guardar evaluación por pieza.
7. **Hueco de pluralidad:** ninguna semilla en español, catalán ni del Sur Global. Por región: 13 de EE. UU. (más 1 mixta EE. UU./China), 3 de Europa (Alemania, Reino Unido), 2 internacionales (OCDE, AIAAIC) y 2 con foco en China (ChinAI, Concordia AI). Contradice el principio de pluralidad.

## Fuentes por situación

**Evaluación más completa (4 o 5 criterios con evidencia):** AI Incident Database, Stanford AI Index, Transluce, AI Now, AlgorithmWatch, Data & Society, Wired (C3 NE), Import AI, SEP, ChinAI, DigiChina, Concordia AI, CSET (C4 NE), MERICS, OECD.AI (C5 NE).

**Evaluación institucional sin muestreo:** Ada Lovelace Institute, arXiv (la calidad se evalúa por preprint), OpenAlex (índice), DAIR (sin piezas propias leídas).

**Sin concluir por acceso:** AIAAIC (solo 1 criterio), AI Governance DB (solo 1).

## Conflictos de interés y vínculos detectados
- **Import AI (S13):** el autor es cofundador y Head of Policy de Anthropic, sin declaración de independencia; fue miembro fundador del AI Index de Stanford (S05). Claude, que ha hecho esta evaluación, es un modelo de Anthropic: revisión humana reforzada.
- **Transluce (S06):** colabora con OpenAI, Anthropic y Google DeepMind con acceso privilegiado; no lista donantes.
- **Stanford AI Index (S05):** apoyos de industria (Google, OpenAI, entre otros, según fuente de terceros) además de Open Philanthropy.
- **CSET (S20):** Open Philanthropy es el principal financiador (más de 100 M USD).
- **AI Incident Database (S02):** patrocinios previstos de empresas de cumplimiento (liderado por Trustible.ai).
- **Wired (S12):** medio comercial que cubre a empresas con las que los medios podrían tener acuerdos de licencia de contenido (no verificado).
- **Vínculos entre fuentes:** expertos de AI Now y Transluce aparecen en un reportaje de Wired.

## Qué NO se ha verificado
- Financiadores de Transluce (donantes), financiación general de la OCDE, apoyos de AI Index según wiki de terceros, página de transparencia actual de DAIR.
- Contenido de las listas de financiadores de Data & Society y AlgorithmWatch (hoy).
- PDF completo del AI Index; informes completos de Concordia AI.
- Acuerdos de licencia entre Condé Nast y empresas de IA.
- Todas las celdas con evidencia [S] o [M] deberían pasar a [P] cuando se pueda.

## Cambios respecto al borrador de ayer
- DigiChina: C2 bajó a NE en la primera pasada (solo portada) y subió a Alto tras leer tres piezas, con matiz (Alto en traducciones, Medio en foros).
- Wired y SEP pasaron de NE a evaluación parcial gracias a la lectura con Chrome.
