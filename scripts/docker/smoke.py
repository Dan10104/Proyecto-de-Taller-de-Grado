"""Local synthetic QA. Run from repository root after Compose bootstrap + qa.php setup."""
import copy
import html
import io
import re
import json
import subprocess
import time
import urllib.parse
from datetime import datetime, timedelta
from pathlib import Path
import urllib.request
import urllib.error
import http.cookiejar
import http.client


class Cookies(http.cookiejar.CookieJar):
    def get(self, name, default=''):
        return next((cookie.value for cookie in self if cookie.name == name), default)


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


class Response:
    def __init__(self, response):
        self.status_code = response.code
        self.headers = response.headers
        self.content = response.read()
        response.close()

    def json(self):
        return json.loads(self.content)


class Session:
    def __init__(self):
        self.cookies = Cookies()

    def request(self, method, url, json=None, headers=None, timeout=30, **kwargs):
        opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies), NoRedirect())
        data = None if json is None else __import__('json').dumps(json).encode()
        headers = dict(headers or {})
        if data is not None:
            headers['Content-Type'] = 'application/json'
        request = urllib.request.Request(url, data=data, headers=headers, method=method)
        try:
            response = opener.open(request, timeout=timeout)
        except urllib.error.HTTPError as error:
            response = error
        return Response(response)

    def get(self, url, **kwargs):
        return self.request('GET', url, **kwargs)

    def post(self, url, **kwargs):
        return self.request('POST', url, **kwargs)


class requests:
    Session = Session
    RequestException = (urllib.error.URLError, ConnectionError, TimeoutError, http.client.RemoteDisconnected)
    get = staticmethod(lambda url, **kwargs: Session().get(url, **kwargs))
    post = staticmethod(lambda url, **kwargs: Session().post(url, **kwargs))


ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / '.local/qa/phase14'
OUT.mkdir(parents=True, exist_ok=True)
COMPOSE = ['docker', 'compose', '--env-file', '.env.docker', '-f', 'compose.production-like.yml']
A, B = 'http://127.0.0.1:8088', 'http://127.0.0.1:8089'
results = {}


def check(condition, name):
    results[name] = bool(condition)
    if not condition:
        raise RuntimeError('Smoke failed: ' + name)


def operation(mode, replica='web-a'):
    result = subprocess.run(COMPOSE + ['exec', '-T', replica, 'php', '/dev/stdin', mode], cwd=ROOT,
                            input=(ROOT / 'scripts/docker/qa.php').read_bytes(), capture_output=True)
    if result.returncode:
        raise RuntimeError('Container QA failed: ' + mode)
    return json.loads(result.stdout)


def post(session, url, data):
    return session.post(url, json=data, headers={'Accept': 'application/json',
        'X-XSRF-TOKEN': urllib.parse.unquote(session.cookies.get('XSRF-TOKEN', ''))}, timeout=45, allow_redirects=False)


def login(email, password):
    session = requests.Session()
    initial = session.get(A + '/login', timeout=15)
    check(initial.status_code == 200, 'login_page')
    session.version = json.loads(re.search(r'<script data-page="[^"]+" type="application/json">(.*?)</script>', initial.content.decode(), re.S).group(1))['version']
    response = post(session, A + '/login', {'correo': email, 'password': password})
    check(response.status_code == 302 and '/inicio' in response.headers.get('Location', ''), 'login_success')
    return session


def page(session, base, path):
    return session.get(base + path, headers={'X-Inertia': 'true', 'X-Inertia-Version': getattr(session, 'version', ''), 'Accept': 'application/json'}, timeout=45, allow_redirects=False)


def main():
    for base in [A, B]:
        for _ in range(40):
            try:
                if requests.get(base+'/up', timeout=2).status_code == 200:
                    break
            except requests.RequestException:
                pass
            time.sleep(0.5)
    values = dict(line.split('=', 1) for line in (ROOT / '.env.docker').read_text().splitlines() if '=' in line and not line.startswith('#'))
    password = values['SEED_ADMIN_PASSWORD']
    session = login(values['SEED_ADMIN_EMAIL'], password)
    for base in [A, B]:
        response = requests.get(base + '/up', timeout=15)
        check(response.status_code == 200, 'health_' + base[-4:])
        check('Strict-Transport-Security' not in response.headers, 'no_local_hsts_' + base[-4:])
    for index, path in enumerate(['/inicio', '/dashboard', '/reservas', '/prestamos', '/notificaciones', '/actividad', '/iot/monitoreo', '/trazabilidad', '/asistente']):
        response = page(session, B if index % 2 else A, path)
        check(response.status_code == 200, 'authenticated_' + path[1:])
    operation('deny-dashboard')
    try:
        check(page(session, B, '/dashboard').status_code == 403, 'revoked_permission_other_replica')
    finally:
        operation('restore-dashboard')
    check(page(session, A, '/dashboard').status_code == 200, 'restored_permission')
    before = operation('counts')['exports']
    for report, fmt, filters in [('reservas', 'pdf', {'periodo': 'personalizado', 'fecha_inicio': datetime.now().strftime('%Y-%m-%d'), 'fecha_fin': (datetime.now()+timedelta(days=2)).strftime('%Y-%m-%d')}), ('equipamiento', 'xlsx', {})]:
        params = {'reporte': report, **{'filtros['+k+']': v for k,v in filters.items()}}
        preview = page(session, A, '/reportes?' + urllib.parse.urlencode(params))
        check(preview.status_code == 200 and preview.json()['props']['preview'] is not None, report+'_preview')
        response = post(session, B + '/reportes/exportar', {'reporte': report, 'filtros': filters, 'formato': fmt})
        check(response.status_code == 200, fmt+'_status')
        check(response.content.startswith(b'%PDF-' if fmt == 'pdf' else b'PK'), fmt+'_signature')
        (OUT / ('container-report.'+fmt)).write_bytes(response.content)
        if fmt == 'pdf':
            from pypdf import PdfReader
            reader = PdfReader(io.BytesIO(response.content))
            text = '\n'.join(page.extract_text() for page in reader.pages)
            check('ESPACIO-U' in text and 'FICCT' in text and 'Docker' in text, 'pdf_text_fonts_metadata')
        else:
            import openpyxl
            book = openpyxl.load_workbook(io.BytesIO(response.content))
            check(len(book.sheetnames) == 1 and not book.vba_archive, 'xlsx_structure')
            cells = [cell for row in book.active for cell in row]
            check(all(cell.data_type != 'f' for cell in cells), 'xlsx_no_formula')
            check(any(cell.value == '=1+1' and cell.data_type == 's' for cell in cells), 'xlsx_inert_user_text')
    check(operation('counts')['exports'] == before+2, 'exact_export_audit')
    qr = post(session, A + '/qr/recurso/1', {})
    check(qr.status_code == 201 and '<svg' in qr.json().get('svg', ''), 'qr_svg_generation')
    # Do not persist, print, or capture the QR payload.
    del qr
    disabled = post(session, A + '/asistente/consultar', {'pregunta': 'Como solicito una reserva?'})
    check(disabled.status_code == 503, 'chatbot_disabled_safe')
    insecure = requests.post(A + '/api/iot/events', json={}, timeout=10)
    check(insecure.status_code == 403, 'iot_plain_http_rejected')
    check(operation('iot', 'web-b')['ok'], 'iot_hmac_linux_kernel')
    check(operation('runtime')['ok'], 'runtime_jcs_extensions_dml_only')
    subprocess.run(COMPOSE + ['restart', 'web-a'], cwd=ROOT, capture_output=True, check=True)
    for _ in range(30):
        try:
            if requests.get(A+'/up', timeout=2).status_code == 200: break
        except requests.RequestException: pass
        time.sleep(0.5)
    check(page(session, A, '/dashboard').status_code == 200, 'session_survives_restart')
    old = copy.copy(session.cookies)
    check(post(session, B+'/logout', {}).status_code == 302, 'logout_other_replica')
    replay = requests.Session(); replay.cookies = old
    check(page(replay, A, '/dashboard').status_code in [302,401], 'logout_replay_rejected')
    session = login(values['SEED_ADMIN_EMAIL'], password)
    operation('revoke')
    check(page(session, B, '/dashboard').status_code in [302,401], 'revocation_other_replica')
    session = login(values['SEED_ADMIN_EMAIL'], password)
    operation('disable')
    try:
        check(page(session, B, '/dashboard').status_code in [302,401], 'inactive_user_other_replica')
    finally:
        operation('enable')
    teacher = login('docker-teacher@espacio-u.test', password)
    check(page(teacher, B, '/dashboard').status_code == 200, 'personal_dashboard_shared_session')
    check(page(teacher, A, '/reportes').status_code == 403, 'teacher_reports_denied')
    post(teacher, B+'/logout', {})
    operation('orphan')
    for _ in range(40):
        try:
            if operation('orphan-check')['ok']:
                check(True, 'orphan_cleanup')
                break
        except RuntimeError:
            pass
        time.sleep(1)
    else:
        check(False, 'orphan_cleanup')
    print(json.dumps({'passed': len(results), 'ok': all(results.values())}))


if __name__ == '__main__':
    try:
        main()
    finally:
        (OUT / 'http-smoke.json').write_text(json.dumps(results, indent=2), encoding='utf-8')
