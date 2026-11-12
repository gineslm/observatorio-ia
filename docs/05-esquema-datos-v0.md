# Esquema de datos v0 (propuesta)

Propuesta nueva basada en `01-decisiones.md` y en los 8 ajustes de `02-rubrica-calidad-v0.md`. Es un punto de partida para el repo, no una versión final. Todo en JSON, versionado en Git. PHP solo lee.

## Principios del esquema
- Un fichero por tipo de entidad: fuentes (`data/sources.json`), piezas (`data/items/AAAA-MM.json`), correcciones (`data/gold-set/corrections.json`).
- Campos objetivos antes que etiquetas. La clasificación temática está **diferida**: no hay campo de categoría hasta el primer análisis (ver disparadores).
- Cada evaluación lleva fecha, nivel de evidencia y versión de la rúbrica.
- Cada pieza guarda por qué entró (para analizar luego el sesgo de selección).
- Valores `null` permitidos; NE se escribe como `"NE"` en puntuaciones.
- Identificadores estables: `S##` para fuentes, `P-AAAA-MM-####` para piezas.

## Fuente (`sources.json`)

El fichero actual (`data/sources.json`) es la versión **plana** de la primera pasada. El esquema objetivo incorpora los 8 ajustes:

```json
{
  "id": "S07",
  "name": "AI Now Institute",
  "kind": "content_source",
  "type": "Instituto de investigación",
  "region": "EE. UU.",
  "languages": ["EN"],
  "relevance": "nucleo",
  "declared_orientation": "incidencia",
  "parent_or_sponsor_org": null,
  "ties_and_interests": [],
  "url": "https://ainowinstitute.org",
  "feeds": [],
  "institutional_evaluation": {
    "date": "2026-11-12",
    "rubric_version": "v0",
    "c1": {"value": "Alto", "evidence_level": "P", "evidence_url": null, "note": ""},
    "c3": {"value": "Alto", "evidence_level": "P", "evidence_url": "https://ainowinstitute.org/...", "info_date": null, "note": ""}
  },
  "sampling_evaluation": {
    "date": "2026-11-12",
    "rubric_version": "v0",
    "n_items": 1,
    "item_ids": [],
    "c2": {"value": "Medio", "evidence_level": "P", "note": ""},
    "c4": {"value": "Medio", "evidence_level": "P", "note": ""},
    "c5": {"value": "Medio", "evidence_level": "P", "note": ""}
  },
  "status": "propuesta",
  "reviewed_by_human": false
}
```

### Campos nuevos (ajustes de la rúbrica)
| Ajuste | Campo |
|---|---|
| 1 | Separar `institutional_evaluation` (C1, C3) y `sampling_evaluation` (C2, C4, C5) |
| 2 | `ties_and_interests` (lista de textos) |
| 3 | `declared_orientation` (investigación, incidencia, periodismo, infraestructura…) |
| 4 | Por criterio: `evidence_level` (P/S/M/-) y `evidence_url` |
| 5 | `parent_or_sponsor_org` (DAIR, AI Index, CSET, AIID) |
| 6 | `info_date` en C3 |
| 7 | `kind`: `content_source` o `infrastructure` |
| 8 | Marcar como secundaria cualquier evidencia [S] y preferir [P] |

Migración: convertir las 21 entradas planas de `sources.json` al esquema objetivo. Los textos de `evidence_notes` se pueden repartir entre `note` de cada criterio; en la primera pasada son libres y habrá que separarlos con cuidado (hay que no inventar niveles de evidencia que no estén en el texto).

## Pieza (`items/AAAA-MM.json`)

```json
{
  "id": "P-2026-11-0001",
  "source_id": "S12",
  "url": "https://...",
  "title": "...",
  "authors": ["..."],
  "published_at": "2026-11-11",
  "captured_at": "2026-11-12",
  "language": "en",
  "original_language": "en",
  "translated_from": null,
  "content_type": "noticia | informe | analisis | opinion | traduccion | dataset | otro",
  "entry_reason": {"channel": "feed | consulta | busqueda_manual | otro", "detail": "..."},
  "summary": "Resumen propio, sin reproducir texto ajeno.",
  "argues": "Qué argumenta (descriptivo, sin puntuar).",
  "quality": {
    "date": "2026-11-12",
    "rubric_version": "v0",
    "c2": {"value": "Alto", "evidence_level": "P", "note": ""},
    "c4": {"value": "Alto", "evidence_level": "P", "note": ""},
    "c5": {"value": "Alto", "evidence_level": "P", "note": ""}
  },
  "identifiers": {"doi": null, "arxiv": null, "openalex": null},
  "links_to_primary": [],
  "review": {"reviewed_by_human": false, "date": null},
  "labels": []
}
```

- `labels` queda vacío hasta el primer análisis de categorías.
- `entry_reason` es obligatorio: permite medir el sesgo de selección.
- C1 y C3 se heredan de la fuente; en la pieza se evalúan C2, C4 y C5.
- `content_type` permite aplicar C4 solo a piezas que afirman resultados empíricos (cuestión abierta).

## Corrección humana (`gold-set/corrections.json`)

```json
{
  "id": "G-0001",
  "date": "2026-11-20",
  "target": {"type": "source | item", "id": "S09"},
  "criterion": "c4",
  "initial_value": "Bajo",
  "corrected_value": "Medio",
  "reason": "Texto libre",
  "rubric_version": "v0"
}
```

## Validación recomendada
- Esquema JSON (JSON Schema) para los tres ficheros, ejecutado en CI o en un script local antes de cada commit.
- Valores permitidos en puntuaciones: `Alto`, `Medio`, `Bajo`, `NE`.
- Niveles de evidencia: `P`, `S`, `M`, `-`.
- Cualquier `Alto` en C3 exige `evidence_level == "P"`.

## Cuestiones abiertas
- Evaluar por pieza y resumir por fuente, o mantener solo fuente (ver `02`).
- Cómo almacenar ficheros grandes (PDF): no versionar PDF ni texto completo ajeno; guardar solo enlace y resumen propio.
- Formato de fechas: ISO 8601.
- Si se añaden más campos de literatura gris tomados del NHS Grey Literature MDS (tipo de documento, institución emisora, serie, ISBN/ISSN).

## Internacionalización (implementado, 2026-11-12)
- El esquema vigente está en `schemas/*.schema.json`; este documento es la propuesta inicial.
- Piezas: `title` y `source_keywords` guardan el original. Los textos que se muestran van en `i18n.es` e `i18n.en` (`title`, `summary`, `keywords`). `summary` siempre con palabras propias; `null` = pendiente.
- Fuentes: `i18n.es.summary` e `i18n.en` (`summary`, `type`, `region`). Los campos base `type` y `region` están en castellano.
- Las notas de evaluación (`legacy_evidence_notes`) solo existen en castellano.
- Textos de interfaz: `public/lang/es.php` y `public/lang/en.php`.
