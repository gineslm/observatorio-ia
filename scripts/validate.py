"""Valida los JSON de data/ contra schemas/ y aplica reglas extra.

Uso: .venv/Scripts/python scripts/validate.py
Sale con código 1 si hay errores. Los avisos no bloquean.
"""
import json
import sys
from pathlib import Path

from jsonschema import Draft202012Validator

ROOT = Path(__file__).resolve().parent.parent
DATA = ROOT / "data"
SCHEMAS = ROOT / "schemas"

errors: list[str] = []
warnings: list[str] = []


def load(path: Path):
    with path.open(encoding="utf-8") as f:
        return json.load(f)


def check_schema(data_path: Path, schema_name: str):
    schema = load(SCHEMAS / schema_name)
    data = load(data_path)
    for e in Draft202012Validator(schema).iter_errors(data):
        loc = "/".join(str(p) for p in e.absolute_path) or "(raíz)"
        errors.append(f"{data_path.relative_to(ROOT)}: {loc}: {e.message}")
    return data


def main() -> int:
    sources = check_schema(DATA / "sources.json", "sources.schema.json")
    source_ids = set()
    for s in sources.get("sources", []):
        sid = s.get("id")
        if sid in source_ids:
            errors.append(f"sources.json: id duplicado {sid}")
        source_ids.add(sid)
        # Regla: un Alto en C3 exige evidencia P.
        c3 = s.get("institutional_evaluation", {}).get("c3", {})
        if c3.get("value") == "Alto" and c3.get("evidence_level") != "P":
            msg = f"sources.json: {sid} C3=Alto sin evidencia P (nivel: {c3.get('evidence_level')})"
            # Aviso mientras no haya revisión humana; error después.
            (errors if s.get("reviewed_by_human") else warnings).append(msg)

    item_ids = set()
    # Piezas: una carpeta por autor (feed = servidor, consulta = equipo local).
    for loose in (DATA / "items").glob("*.json"):
        errors.append(f"{loose.relative_to(ROOT)}: las piezas deben ir en items/feed/ o items/consulta/")
    urls = set()
    for folder, prefix in (("feed", "F"), ("consulta", "C")):
        for path in sorted((DATA / "items" / folder).glob("*.json")):
            rel = path.relative_to(ROOT)
            for it in check_schema(path, "items.schema.json"):
                if not isinstance(it, dict):
                    continue
                iid = it.get("id", "")
                if iid in item_ids:
                    errors.append(f"{rel}: id duplicado {iid}")
                item_ids.add(iid)
                letter = iid[10:11] if len(iid) == 15 else ""
                if letter and letter != prefix:
                    errors.append(f"{rel}: {iid} no corresponde a la carpeta {folder} (esperado {prefix})")
                if it.get("url") in urls:
                    errors.append(f"{rel}: URL duplicada {it.get('url')}")
                urls.add(it.get("url"))
                if it.get("source_id") not in source_ids:
                    errors.append(f"{rel}: {iid} apunta a fuente inexistente {it.get('source_id')}")

    # Enriquecimiento (autor: equipo local).
    enriched = set()
    for path in sorted((DATA / "enrichment").glob("*.json")):
        data = check_schema(path, "enrichment.schema.json")
        for iid in data if isinstance(data, dict) else {}:
            if iid not in item_ids:
                errors.append(f"{path.relative_to(ROOT)}: enriquecimiento de pieza inexistente {iid}")
            enriched.add(iid)
    pending = sorted(item_ids - enriched)
    if pending:
        warnings.append(f"{len(pending)} piezas sin enriquecer (resumen/traducción pendientes): {', '.join(pending[:5])}{'…' if len(pending) > 5 else ''}")

    check_schema(DATA / "gold-set" / "corrections.json", "corrections.schema.json")

    fb_path = DATA / "feedback" / "interest.json"
    if fb_path.exists():
        seen = set()
        for r in check_schema(fb_path, "feedback.schema.json"):
            if not isinstance(r, dict):
                continue
            iid = r.get("item_id")
            if iid in seen:
                errors.append(f"interest.json: marca duplicada para {iid}")
            seen.add(iid)
            if iid not in item_ids:
                errors.append(f"interest.json: marca para pieza inexistente {iid}")

    for w in warnings:
        print(f"AVISO  {w}")
    for e in errors:
        print(f"ERROR  {e}")
    print(f"\n{len(errors)} errores, {len(warnings)} avisos.")
    return 1 if errors else 0


if __name__ == "__main__":
    sys.exit(main())
