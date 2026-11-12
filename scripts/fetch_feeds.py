"""Lee los feeds RSS/Atom de data/sources.json y añade piezas nuevas a data/items/AAAA-MM.json.

Guarda solo metadatos (título, URL, fecha, autoría, keywords declaradas por la
fuente). NUNCA guarda el texto ni el extracto del feed (regla 3); el resumen
queda en null hasta que se escriba con palabras propias.

Uso:
  .venv/Scripts/python scripts/fetch_feeds.py [--limit 5] [--excerpts]
  --excerpts  muestra en pantalla (sin guardar) el extracto de las piezas nuevas
              y de las que tienen el resumen pendiente, como ayuda para resumir.
"""
import argparse
import html
import json
import re
import sys
import urllib.request
import xml.etree.ElementTree as ET
from datetime import date, datetime
from email.utils import parsedate_to_datetime
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DATA = ROOT / "data"
UA = "observatorio-feed-reader/0.1 (+https://github.com/gineslm/observatorio-ia)"
NS = {"atom": "http://www.w3.org/2005/Atom", "dc": "http://purl.org/dc/elements/1.1/"}


def text(el):
    return (el.text or "").strip() if el is not None else ""


def clean(s: str) -> str:
    return re.sub(r"\s+", " ", html.unescape(re.sub(r"<[^>]+>", " ", s))).strip()


def to_date(s: str):
    if not s:
        return None
    try:
        return parsedate_to_datetime(s).date().isoformat()
    except (TypeError, ValueError):
        pass
    try:
        return datetime.fromisoformat(s.replace("Z", "+00:00")).date().isoformat()
    except ValueError:
        return None


def parse(xml_bytes: bytes):
    root = ET.fromstring(xml_bytes)
    out = []
    if root.tag.endswith("rss") or root.find("channel") is not None:
        ch = root.find("channel")
        lang = text(ch.find("language"))[:2].lower() or None
        for it in ch.findall("item"):
            out.append({
                "title": clean(text(it.find("title"))),
                "url": text(it.find("link")),
                "published_at": to_date(text(it.find("pubDate"))),
                "authors": [a for a in (clean(text(x)) for x in it.findall("dc:creator", NS)) if a],
                "keywords": [clean(text(c)) for c in it.findall("category") if text(c)],
                "language": lang,
                "excerpt": clean(text(it.find("description"))),
            })
    else:  # Atom
        lang = (root.get("{http://www.w3.org/XML/1998/namespace}lang") or "")[:2].lower() or None
        for en in root.findall("atom:entry", NS):
            link = en.find("atom:link[@rel='alternate']", NS)
            if link is None:  # ojo: un Element sin hijos es falsy, no usar "or"
                link = en.find("atom:link", NS)
            out.append({
                "title": clean(text(en.find("atom:title", NS))),
                "url": link.get("href", "") if link is not None else "",
                "published_at": to_date(text(en.find("atom:published", NS)) or text(en.find("atom:updated", NS))),
                "authors": [clean(text(a)) for a in en.findall("atom:author/atom:name", NS) if text(a)],
                "keywords": [c.get("term", "") for c in en.findall("atom:category", NS) if c.get("term")],
                "language": lang,
                "excerpt": clean(text(en.find("atom:summary", NS)) or text(en.find("atom:content", NS))),
            })
    for e in out:  # el idioma de la URL (/de/, /en/...) manda sobre el del canal
        m = re.search(r"https?://[^/]+/([a-z]{2})/", e["url"])
        if m:
            e["language"] = m.group(1)
    return [e for e in out if e["url"] and e["title"]]


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--limit", type=int, default=5, help="piezas más recientes por feed")
    ap.add_argument("--excerpts", action="store_true")
    args = ap.parse_args()
    sys.stdout.reconfigure(encoding="utf-8")

    sources = json.loads((DATA / "sources.json").read_text(encoding="utf-8"))["sources"]
    items_dir = DATA / "items"
    all_items = {}
    for f in items_dir.glob("*.json"):
        all_items[f.name] = json.loads(f.read_text(encoding="utf-8"))
    known = {it["url"] for lst in all_items.values() for it in lst}
    pending = {it["url"] for lst in all_items.values() for it in lst
               if not all(it.get("i18n", {}).get(l, {}).get("summary") for l in ("es", "en"))}

    today = date.today()
    month_file = f"{today:%Y-%m}.json"
    month = all_items.setdefault(month_file, [])
    prefix = f"P-{today:%Y-%m}-"
    seq = max((int(it["id"][-4:]) for it in month if it["id"].startswith(prefix)), default=0)

    added = 0
    for s in sources:
        for feed in s.get("feeds", []):
            try:
                req = urllib.request.Request(feed, headers={"User-Agent": UA})
                with urllib.request.urlopen(req, timeout=20) as r:
                    entries = parse(r.read())
            except Exception as ex:  # noqa: BLE001 - un feed caído no debe parar el resto
                print(f"[{s['id']}] ERROR {feed}: {ex}")
                continue
            entries.sort(key=lambda e: e["published_at"] or "", reverse=True)
            for e in entries[: args.limit]:
                if e["url"] in known:
                    if args.excerpts and e["url"] in pending:
                        print(f"\n[pendiente] {e['url']}\n  {e['excerpt'][:1200]}")
                    continue
                seq += 1
                item = {
                    "id": f"{prefix}{seq:04d}",
                    "source_id": s["id"],
                    "url": e["url"],
                    "title": e["title"],
                    "authors": e["authors"],
                    "published_at": e["published_at"],
                    "captured_at": today.isoformat(),
                    "language": e["language"],
                    "original_language": e["language"],
                    "translated_from": None,
                    "content_type": None,
                    "source_keywords": e["keywords"],
                    # Textos por idioma: si el original ya está en es/en se copia el título y las keywords.
                    "i18n": {
                        lng: {
                            "title": e["title"] if e["language"] == lng else None,
                            "summary": None,
                            "keywords": e["keywords"] if e["language"] == lng else [],
                        }
                        for lng in ("es", "en")
                    },
                    "entry_reason": {"channel": "feed", "detail": feed},
                    "argues": "",
                    "identifiers": {"doi": None, "arxiv": None, "openalex": None},
                    "links_to_primary": [],
                    "review": {"reviewed_by_human": False, "date": None},
                    "labels": [],
                }
                month.append(item)
                known.add(e["url"])
                added += 1
                print(f"[{s['id']}] + {item['id']} {e['title'][:90]}")
                if args.excerpts:
                    print(f"  {e['excerpt'][:1200]}")

    for name, lst in all_items.items():
        if lst:
            (items_dir / name).write_text(json.dumps(lst, ensure_ascii=False, indent=2) + "\n", encoding="utf-8", newline="\n")
    print(f"\n{added} piezas nuevas.")


if __name__ == "__main__":
    main()
