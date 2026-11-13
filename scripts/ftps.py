"""Operaciones FTPS (explícito) contra el hosting. Lee la conexión de private/servidor-aruba.env.

La contraseña se pide por teclado (getpass): no se muestra, no se guarda y no se pasa
por argumentos. Ejecutar en tu propia terminal:

  .venv/Scripts/python scripts/ftps.py diag-subir    # crea la carpeta remota y sube el diagnóstico
  .venv/Scripts/python scripts/ftps.py diag-borrar   # elimina el diagnóstico y su carpeta de prueba
  .venv/Scripts/python scripts/ftps.py listar        # lista la carpeta remota

El certificado TLS se verifica. Si falla por nombre de host, NO se desactiva solo:
usa --sin-verificar solo si entiendes el riesgo (posible intermediario).
"""
import argparse
import getpass
import io
import secrets
import ssl
import sys
from ftplib import FTP_TLS, error_perm
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
ENV = ROOT / "private" / "servidor-aruba.env"
DIAG = ROOT / "tools" / "diagnostico.php"


def load_env() -> dict:
    if not ENV.exists():
        sys.exit(f"Falta {ENV.relative_to(ROOT)}")
    cfg = {}
    for line in ENV.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if line and not line.startswith("#") and "=" in line:
            k, v = line.split("=", 1)
            cfg[k.strip()] = v.strip()
    return cfg


KEYRING_SERVICE = "observatorio-ia-ftps"


def password(cfg: dict) -> str:
    """Contraseña desde el Administrador de credenciales de Windows (keyring); si no está, se pide."""
    try:
        import keyring
        pwd = keyring.get_password(KEYRING_SERVICE, cfg["FTP_USER"])
        if pwd:
            return pwd
    except Exception:  # noqa: BLE001 - sin keyring se pide por teclado
        pass
    if not sys.stdin.isatty():
        sys.exit("No hay contraseña en el almacén de credenciales y no se puede pedir (ejecución no interactiva).\n"
                 "Ejecuta una vez en tu terminal: .venv/Scripts/python scripts/sync.py guardar-clave")
    return getpass.getpass(f"Contraseña FTPS de {cfg['FTP_USER']}: ")


def connect(cfg: dict, verify: bool = True) -> FTP_TLS:
    ctx = ssl.create_default_context()
    if not verify:
        print("AVISO: certificado TLS sin verificar.")
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
    ftp = FTP_TLS(context=ctx, timeout=30)
    ftp.connect(cfg["FTP_HOST"], int(cfg.get("FTP_PORT", 21)))
    # Hosting compartido: el certificado está a nombre del servidor del proveedor, no del dominio.
    # ftplib verifica contra self.host (canal de control y de datos); se fija el nombre esperado
    # tras abrir la conexión TCP, sin dejar de verificar la cadena ni el nombre.
    if cfg.get("FTP_TLS_NAME"):
        ftp.host = cfg["FTP_TLS_NAME"]
    try:
        ftp.login(cfg["FTP_USER"], password(cfg))
    except error_perm as ex:
        if str(ex).startswith("530"):
            sys.exit("Acceso rechazado por el servidor (530). Revisa:\n"
                     "  1. El filtro de accesos FTP del panel de Aruba (¿está autorizada tu IP pública?).\n"
                     "  2. La contraseña de la cuenta FTP (no se muestra al escribirla).\n"
                     "  3. Si está guardada en el Administrador de credenciales, vuelve a guardarla: sync.py guardar-clave\n"
                     "No reintentes muchas veces seguidas: el servidor puede bloquear la IP tras varios fallos.")
        raise
    ftp.prot_p()  # cifra también el canal de datos
    return ftp


def ensure_dir(ftp: FTP_TLS, path: str) -> None:
    ftp.cwd("/")
    for part in [p for p in path.split("/") if p]:
        try:
            ftp.cwd(part)
        except error_perm:
            ftp.mkd(part)
            ftp.cwd(part)
            print(f"Creada carpeta remota: {part}")


def main():
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    ap = argparse.ArgumentParser()
    ap.add_argument("orden", choices=["diag-subir", "diag-borrar", "listar"])
    ap.add_argument("--sin-verificar", action="store_true", help="no verificar el certificado TLS (desaconsejado)")
    args = ap.parse_args()

    cfg = load_env()
    remote = cfg["FTP_REMOTE_DIR"]
    try:
        ftp = connect(cfg, verify=not args.sin_verificar)
    except ssl.SSLCertVerificationError as ex:
        sys.exit(f"Certificado TLS no válido para {cfg['FTP_HOST']}: {ex}\n"
                 "Comprueba el nombre del servidor FTP con Aruba antes de usar --sin-verificar.")

    try:
        if args.orden == "diag-subir":
            ensure_dir(ftp, remote)
            token = secrets.token_urlsafe(16)
            data = DIAG.read_text(encoding="utf-8").replace("__TOKEN__", token).encode("utf-8")
            ftp.storbinary("STOR diagnostico.php", io.BytesIO(data))
            print("Subido diagnostico.php")
            print(f"Ábrelo en: {cfg['PUBLIC_URL'].rstrip('/')}/diagnostico.php?t={token}")
            print("Cuando termines: diag-borrar")
        elif args.orden == "diag-borrar":
            ftp.cwd(remote)
            for f in ("diag_test/.htaccess", "diag_test/secreto.txt", "diagnostico.php"):
                try:
                    ftp.delete(f)
                    print(f"Borrado {f}")
                except error_perm:
                    pass
            try:
                ftp.rmd("diag_test")
                print("Borrada carpeta diag_test")
            except error_perm:
                pass
        elif args.orden == "listar":
            ftp.cwd(remote)
            ftp.retrlines("LIST")
    finally:
        ftp.quit()


if __name__ == "__main__":
    main()
