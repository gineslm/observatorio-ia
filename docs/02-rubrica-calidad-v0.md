# Rúbrica de calidad epistémica v0 (fuentes y piezas)

Estado: borrador de 2026-11-11, pendiente de las correcciones de la persona responsable. Es independiente de la pertinencia al perímetro. No puntúa posiciones: puntúa cómo de comprobable y transparente es una fuente.

## Escala
**Alto / Medio / Bajo / No evaluable (NE).** NE significa "no se pudo verificar"; nunca equivale a Bajo y no penaliza.

## Nivel de evidencia (columna aparte por fuente)
- **P**: página oficial de la fuente.
- **S**: tercero (prensa, wiki, ficha de financiador).
- **M**: memoria o conocimiento previo, no verificado.
- **-**: ninguna.
Un "Alto" en C3 exige evidencia P.

## Criterios

**C1. Autoría y trayectoria identificable**
- Alto: personas o equipo con nombre y la institución con historial comprobable.
- Medio: institución identificable, autoría diluida o trayectoria corta.
- Bajo: sin autoría ni institución identificables.

**C2. Verificabilidad (citas, datos y método)**
- Alto: enlaza sistemáticamente fuentes primarias, datos o método.
- Medio: lo hace a veces.
- Bajo: afirma sin referencias comprobables.

**C3. Transparencia de financiación e intereses**
- Alto: publica los financiadores por nombre y/o un informe financiero o política de independencia.
- Medio: declara el tipo de financiación pero sin nombres, o la información está desactualizada o es incompleta.
- Bajo: no declara, o hay opacidad relevante.

**C4. Reconocimiento de incertidumbre**
- Alto: señala límites y metodología, evita promesas universales.
- Medio: mixto.
- Bajo: tono determinista o promocional.

**C5. Distinción entre hecho, análisis y opinión**
- Alto: etiqueta o separa claramente los tipos de contenido.
- Medio: parcialmente.
- Bajo: mezcla sin señalar.

## Evaluación en dos fases
1. **Institucional** (C1 y C3): se puede hacer desde las páginas "acerca de" y de financiación.
2. **Por muestreo** (C2, C4 y C5): requieren leer unas 3 piezas por fuente. En la primera pasada quedan como NE salvo evidencia clara; se rellenan a partir de las evaluaciones por pieza de las primeras capturas.

## Campos descriptivos (no puntuados)
- **Vínculos e intereses relevantes:** p. ej., financiación del sector evaluado, autoría vinculada a un laboratorio de IA.
- **Orientación declarada:** investigación, incidencia, periodismo, infraestructura. Es una etiqueta de naturaleza, no un juicio.

## Reglas
- Toda evaluación lleva fecha, nivel de evidencia y versión de la rúbrica.
- La financiación se reevalúa cada 12 meses.
- Las correcciones humanas se guardan como conjunto de referencia: caso, criterio, valor inicial, valor corregido y motivo.
- Una fuente puede tener valor parcial sin pasar todos los criterios.

## Ajustes de esquema derivados de la primera pasada
1. Separar en la fuente la evaluación institucional (C1, C3) de la de muestreo (C2, C4, C5).
2. Añadir el campo "vínculos e intereses relevantes".
3. Añadir "orientación declarada" (descriptivo).
4. Guardar, por criterio, el nivel de evidencia y la URL.
5. Añadir "organización matriz o patrocinadora" (DAIR, AI Index, CSET y AIID dependen de otra entidad).
6. Registrar la fecha de la información de financiación (los datos pueden tener años).
7. Distinguir "fuente de contenido" de "infraestructura o índice" (p. ej., OpenAlex) en el tipo.
8. Preferir páginas primarias; marcar las secundarias.

## Cuestiones abiertas tras la segunda pasada (2026-11-12)
- **C4 y el género.** Las piezas de incidencia política (AlgorithmWatch, parte de AI Now) salieron peor en C4 porque no declaran límites y usan tono prescriptivo. Decidir si C4 se mide solo en piezas que afirman resultados empíricos. Recomendación del asistente: sí, y marcar en las demás "no aplica" (NE) en lugar de Bajo.
- **Granularidad por pieza.** Una misma fuente varía entre piezas (Wired: una noticia Alto/Alto/Alto frente a un reportaje de panel Medio/Medio/Medio; CSET y DigiChina: traducciones frente a análisis). Valorar guardar la evaluación por pieza y calcular la de la fuente como resumen.
- **Rúbrica sin fuentes malas.** Casi todas las semillas son reputadas. Añadir fuentes de calidad dudosa al registro para comprobar que la rúbrica distingue.
- **Tamaño de muestra.** Con 1 a 3 piezas por fuente, los valores de C2, C4 y C5 son indicativos. Indicar siempre `n`.
