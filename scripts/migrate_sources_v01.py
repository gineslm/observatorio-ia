"""Migración única de data/sources.json de v0.1 (plano) a v1.0.

No inventa evidencia: evidence_level queda null (pendiente) y la nota libre
original se conserva íntegra en legacy_evidence_notes. El original queda en
el historial de Git.
"""
import json
import re
from pathlib import Path

PATH = Path(__file__).resolve().parent.parent / "data" / "sources.json"


def crit(value):
    return {"value": value, "evidence_level": None, "evidence_url": None, "note": ""}


def main():
    old = json.loads(PATH.read_text(encoding="utf-8"))
    if old.get("schema_version") != "0.1":
        raise SystemExit("sources.json no está en v0.1; nada que migrar.")
    date, rv = old["evaluated_on"], old["rubric_version"]
    out = []
    for s in old["sources"]:
        sc = s["scores"]
        m = re.search(r"n=(\d+)", s["review_status"])
        c3 = crit(sc["c3"])
        c3["info_date"] = None
        out.append({
            "id": s["id"],
            "name": s["name"],
            # Solo OpenAlex se cita explícitamente como infraestructura en la rúbrica.
            "kind": "infrastructure" if s["id"] == "S14" else None,
            "type": s["type"],
            "region": s["region"],
            "languages": s["languages"],
            "relevance": s["relevance"].lower(),
            "declared_orientation": None,
            "parent_or_sponsor_org": None,
            "ties_and_interests": [],
            "url": s["url"],
            "feeds": [],
            "institutional_evaluation": {
                "date": date, "rubric_version": rv,
                "c1": crit(sc["c1"]), "c3": c3,
            },
            "sampling_evaluation": {
                "date": date, "rubric_version": rv,
                "n_items": int(m.group(1)) if m else None, "item_ids": [],
                "c2": crit(sc["c2"]), "c4": crit(sc["c4"]), "c5": crit(sc["c5"]),
            },
            "status": "propuesta",
            "reviewed_by_human": False,
            "legacy_evidence_notes": s["evidence_notes"],
            "legacy_review_status": s["review_status"],
        })
    new = {
        "schema_version": "1.0",
        "rubric_version": rv,
        "note": "Propuestas del asistente sin revisión humana. evidence_level null = pendiente de asignar desde legacy_evidence_notes.",
        "sources": out,
    }
    PATH.write_text(json.dumps(new, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Migradas {len(out)} fuentes.")


if __name__ == "__main__":
    main()
