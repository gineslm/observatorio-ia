"""Sincronización equipo local ↔ servidor (FTPS explícito). Ver docs/08-arquitectura-servidor.md.

Cada fichero tiene un único autor:
  servidor      -> data/items/feed/*.json           (se BAJAN, nunca se suben)
  equipo local  -> data/sources.json, data/items/consulta/*.json, data/enrichment/*.json,
                   data/feedback/interest.json (estados de valoración; online solo lectura)  (se SUBEN)
  solo local    -> data/gold-set/   (no se sube)

Órdenes:
  guardar-clave  guarda la contraseña FTPS en el Administrador de credenciales de Windows (una vez)
  bajar          descarga lo del servidor, valida y hace commit local. Si algo falla, deja todo como estaba.
  subir          valida y sube al servidor solo los ficheros del equipo local
  desplegar      sube el código del portal y el cron (+ .htaccess y token del cron)
  sembrar        SOLO la primera vez: sube las piezas de feed existentes (se niega si el servidor ya tiene)
  estado         lista lo que hay en el servidor
"""
import argparse
import getpass
import io
import os
import secrets
import subprocess
import sys
from ftplib import error_perm
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
import ftps  # noqa: E402

ROOT = ftps.ROOT
PY = sys.executable

LOCAL_OWNED = ["data/sources.json", "data/items/consulta/*.json", "data/enrichment/*.json",
               "data/feedback/interest.json"]  # estados: se editan en local, online se muestran en solo lectura
SERVER_OWNED_DIR = "data/items/feed"
CODE = {  # destino remoto -> origen local
    "index.php": "public/index.php",
    "fuentes.php": "public/fuentes.php",
    "recursos.php": "public/recursos.php",
    "assets/style.css": "public/assets/style.css",
    "assets/ui.js": "public/assets/ui.js",
    "assets/grid.js": "public/assets/grid.js",
    "inc/app.php": "public/inc/app.php",
    "inc/grid.php": "public/inc/grid.php",
    "inc/sources_page.php": "public/inc/sources_page.php",
    "inc/feedback.php": "public/inc/feedback.php",
    "lang/es.php": "public/lang/es.php",
    "lang/en.php": "public/lang/en.php",
    "cron/fetch_feeds.php": "cron/fetch_feeds.php",
    ".htaccess": "deploy/htaccess-raiz",
    "inc/.htaccess": "deploy/htaccess-denegar",
    "lang/.htaccess": "deploy/htaccess-denegar",
    "data/.htaccess": "deploy/htaccess-denegar",
    "cron/.htaccess": "deploy/htaccess-cron",
}
# No se despliegan: api/ y assets/interest.js (editar el estado es solo local).


def run(*cmd) -> subprocess.CompletedProcess:
    # En Windows el proceso hijo escribe en la codificación del sistema si no se le indica UTF-8.
    env = {**os.environ, "PYTHONIOENCODING": "utf-8", "PYTHONUTF8": "1"}
    return subprocess.run(cmd, cwd=ROOT, capture_output=True, text=True, encoding="utf-8", errors="replace", env=env)


def validate() -> bool:
    r = run(PY, "scripts/validate.py")
    out = (r.stdout or "").strip()
    if r.returncode != 0:
        print(out or (r.stderr or "").strip())  # muestra todos los errores
    else:
        print(out.splitlines()[-1] if out else "Validación correcta.")
    return r.returncode == 0


def git_dirty(path: str) -> bool:
    return bool(run("git", "status", "--porcelain", "--", path).stdout.strip())


def put(ftp, remote_root: str, rel: str, data: bytes) -> None:
    """Sube a un nombre temporal y renombra: el servidor nunca ve un fichero a medias."""
    parent, name = rel.rsplit("/", 1) if "/" in rel else ("", rel)
    ftps.ensure_dir(ftp, f"{remote_root}/{parent}".rstrip("/"))
    ftp.storbinary(f"STOR {name}.subiendo", io.BytesIO(data))
    try:
        ftp.delete(name)
    except error_perm:
        pass
    ftp.rename(f"{name}.subiendo", name)
    print(f"  ↑ {rel}")


def cmd_guardar_clave(cfg):
    import keyring
    pwd = getpass.getpass(f"Contraseña FTPS de {cfg['FTP_USER']} (se guardará en el Administrador de credenciales): ")
    keyring.set_password(ftps.KEYRING_SERVICE, cfg["FTP_USER"], pwd)
    print("Guardada. Para borrarla: Administrador de credenciales de Windows → Credenciales de Windows → " + ftps.KEYRING_SERVICE)


def cmd_bajar(ftp, remote):
    if git_dirty(SERVER_OWNED_DIR):
        sys.exit(f"Hay cambios locales sin commit en {SERVER_OWNED_DIR}: ese directorio solo lo escribe el servidor. Revisa antes de bajar.")
    local_dir = ROOT / SERVER_OWNED_DIR
    local_dir.mkdir(parents=True, exist_ok=True)
    try:
        ftp.cwd(f"{remote}/{SERVER_OWNED_DIR}")
    except error_perm:
        print("El servidor aún no tiene piezas de feed.")
        return
    names = [n for n in ftp.nlst() if n.endswith(".json")]
    for n in names:
        buf = io.BytesIO()
        ftp.retrbinary(f"RETR {n}", buf.write)
        (local_dir / n).write_bytes(buf.getvalue())
        print(f"  ↓ {SERVER_OWNED_DIR}/{n}")
    # Registros del servidor (no versionados) para el informe
    try:
        ftp.cwd(f"{remote}/data/logs")
        (ROOT / "data/logs").mkdir(parents=True, exist_ok=True)
        for n in [n for n in ftp.nlst() if n.endswith(".log")]:
            buf = io.BytesIO()
            ftp.retrbinary(f"RETR {n}", buf.write)
            (ROOT / "data/logs" / n).write_bytes(buf.getvalue())
    except error_perm:
        pass
    if not validate():
        run("git", "checkout", "--", SERVER_OWNED_DIR)
        run("git", "clean", "-fdq", "--", SERVER_OWNED_DIR)
        sys.exit("Los datos del servidor no validan: se han descartado. Revisa el servidor.")
    if git_dirty(SERVER_OWNED_DIR):
        run("git", "add", "--", SERVER_OWNED_DIR)
        r = run("git", "commit", "-q", "-m", "Sincronizar datos del servidor")
        print("Commit local creado." if r.returncode == 0 else r.stderr)
    else:
        print("Sin cambios desde el servidor.")


def cmd_subir(ftp, remote):
    if not validate():
        sys.exit("La validación falla: no se sube nada.")
    files = sorted({p for pat in LOCAL_OWNED for p in ROOT.glob(pat)})
    for p in files:
        put(ftp, remote, p.relative_to(ROOT).as_posix(), p.read_bytes())
    print(f"{len(files)} ficheros subidos.")


def cmd_desplegar(ftp, remote, cfg):
    for dest, src in CODE.items():
        put(ftp, remote, dest, (ROOT / src).read_bytes())
    token = cfg.get("CRON_TOKEN")
    if not token:
        token = secrets.token_urlsafe(32)
        with ftps.ENV.open("a", encoding="utf-8") as f:
            f.write(f"\n# Token para lanzar el cron por HTTP (cabecera X-Cron-Token)\nCRON_TOKEN={token}\n")
        print("Generado CRON_TOKEN y guardado en private/servidor-aruba.env")
    put(ftp, remote, "cron/config.local.php", f"<?php\nreturn ['cron_token' => '{token}'];\n".encode())
    for d in ("data/items/feed", "data/items/consulta", "data/enrichment", "data/logs"):
        ftps.ensure_dir(ftp, f"{remote}/{d}")
    print("Código desplegado. Si es la primera vez, ejecuta también: subir")


def cmd_sembrar(ftp, remote):
    """Solo la primera vez: copia al servidor las piezas de feed que ya existen en local."""
    try:
        ftp.cwd(f"{remote}/{SERVER_OWNED_DIR}")
        if [n for n in ftp.nlst() if n.endswith(".json")]:
            sys.exit("El servidor ya tiene piezas de feed: no se siembra (él es su único autor). Usa 'bajar'.")
    except error_perm:
        pass
    if not validate():
        sys.exit("La validación falla: no se siembra.")
    files = sorted((ROOT / SERVER_OWNED_DIR).glob("*.json"))
    for p in files:
        put(ftp, remote, p.relative_to(ROOT).as_posix(), p.read_bytes())
    print(f"{len(files)} ficheros de feed sembrados. A partir de ahora solo los escribe el servidor.")


def main():
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("orden", choices=["guardar-clave", "bajar", "subir", "desplegar", "sembrar", "estado"])
    args = ap.parse_args()
    cfg = ftps.load_env()
    if args.orden == "guardar-clave":
        return cmd_guardar_clave(cfg)
    remote = cfg["FTP_REMOTE_DIR"].rstrip("/")
    ftp = ftps.connect(cfg)
    try:
        if args.orden == "bajar":
            cmd_bajar(ftp, remote)
        elif args.orden == "subir":
            cmd_subir(ftp, remote)
        elif args.orden == "desplegar":
            cmd_desplegar(ftp, remote, cfg)
        elif args.orden == "sembrar":
            cmd_sembrar(ftp, remote)
        elif args.orden == "estado":
            ftp.cwd(remote)
            ftp.retrlines("LIST")
    finally:
        ftp.quit()


if __name__ == "__main__":
    main()
