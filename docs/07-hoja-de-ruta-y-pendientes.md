# Hoja de ruta y pendientes

Estado a 2026-11-12. El orden propuesto sigue las dependencias; las fases son orientativas.

## Dependencias
- El **esquema de piezas** depende de la decisión sobre qué se evalúa por pieza y qué por fuente.
- La **prueba de ingesta** depende del esquema y de resolver el acceso a webs bloqueadas.
- El **agente** depende del esquema, de la rúbrica y de un primer gold set.
- La **revisión técnica a 50 piezas** depende de la ingesta semanal.
- El **análisis de categorías** depende de 150 piezas o 6 semanas con al menos 10 fuentes.
- La **visualización PHP** depende del esquema, no del agente.

## Fase 0. Preparar el repo (Claude Code)
- [x] Crear el repositorio en GitHub (público de momento) y subir esta estructura.
- [x] Definir `.gitignore` y la carpeta no versionada para documentos privados.
- [x] Añadir validación con JSON Schema (script local).
- [x] Migrar `data/sources.json` a v1.0 (evidencia por criterio pendiente: `evidence_level` null).
- [ ] Repartir `legacy_evidence_notes` en nivel de evidencia por criterio (revisión humana).

## Fase 1. Calibrar
- [ ] Revisión corta del registro semilla por la persona responsable (8 a 10 celdas clave). Ver sugerencias en el apartado siguiente.
- [ ] Decidir la calibración de C4 (incidencia política).
- [ ] Convertir las correcciones en `data/gold-set/corrections.json`.
- [ ] Aplicar los 8 ajustes de esquema de fuentes.
- [ ] Añadir 6 a 10 fuentes nuevas: en español, catalán, del Sur Global y de calidad dudosa para probar la rúbrica.

## Fase 2. Ingesta
- [x] Escribir un lector de feeds RSS/Atom (`scripts/fetch_feeds.py`). OpenAlex pendiente.
- [x] Feeds activos: AlgorithmWatch, AI Now, ChinAI. Data & Society tiene feed vacío; MERICS y DAIR sin feed en la ruta estándar.
- [ ] Revisar el feed de AlgorithmWatch: /en/feed/ devuelve piezas en alemán.
- [ ] Probar con 3 a 5 fuentes, incluida una china de nivel 2 (CAICT o TC260) con traducción automática y marcar su calidad.
- [ ] Probar WeChat (se espera que no sea automatizable).
- [ ] Resolver el acceso a webs bloqueadas (Wired, AIAAIC, etc.): RSS, API, navegador o lectura manual.
- [ ] Decidir dónde se ejecuta la captura: desde el ordenador de la persona responsable con Claude Code, no necesariamente en el hosting.

## Fase 3. Ciclo semanal
- [ ] Primera captura. Contar piezas desde ese día (los disparadores empiezan aquí).
- [ ] Programar en Claude Desktop la tarea de `agent/tarea-semanal.md` y fijar el día de revisión humana.
- [ ] Revisión técnica a las 50 piezas.

## Fase 4. Visualización
- [x] Portal PHP v0: tablero de piezas, fuentes y recursos, con cards y filtros.
- [ ] Decidir si se publica o se mantiene privado (el repo es público; la web, pendiente).

## Fase 5. Categorías
- [ ] Disparador: 150 piezas o 6 semanas con al menos 10 fuentes.
- [ ] Análisis del proyecto (top-down) y de los materiales (bottom-up); comparar.
- [ ] Definir facetas y etiquetas; migrar.

## Sugerencia: celdas de revisión de mayor valor
1. AlgorithmWatch, C4: define si el género de incidencia se penaliza.
2. Import AI y Transluce, C3 y notas: conflicto con Anthropic; la evaluación del asistente es la menos fiable aquí.
3. DigiChina, C2: mezcla traducciones antiguas y un foro reciente.
4. OECD.AI, C2 y C4: Alto pese a clasificar con modelos de lenguaje sin revisión humana descrita.
5. Wired, C1: Alto basado en bylines y pertenencia a un grupo editorial.
6. ChinAI, C4: Alto con solo 2 piezas.
7. AI Index, C2: Alto provisional sin haber leído el PDF.
8. Ada Lovelace, C3: Alto sin fecha de la información.

## Pendientes sin dueño
- Nombre del observatorio.
- Pruebas de lectura de fuentes de China nivel 2.
- Reevaluación de financiación a los 12 meses (nov 2027).
- Verificar acuerdos de licencia de Condé Nast con empresas de IA (Wired).
- Verificar si la clave API de OpenAlex es obligatoria.
- Posible retorno a Policy Commons / Overton si la estrategia propia no funciona.
- Auditoría periódica del registro (en lugar de cuota de contraste): definir periodicidad.

## Riesgos y cómo se vigilan
| Riesgo | Vigilancia |
|---|---|
| Sesgo del evaluador único | Gold set + prueba de deriva |
| Rúbrica sin calibrar | Añadir fuentes malas; medir acuerdo con humano |
| Sesgo anglosajón | Cuota mínima de fuentes no inglesas en el registro (a decidir) |
| Acceso bloqueado | Registrar causa; no eludir bloqueos |
| Crecimiento del alcance | Día fijo de revisión; límite de piezas/semana |
