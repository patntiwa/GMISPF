#!/usr/bin/env python3
"""HTTP regression suite (Python stdlib + PHP). Only uses throwaway databases.
Run: PHP_BIN=/path/to/php python3 tests/integration.py
"""
import csv
import http.cookiejar
import io
import os
from pathlib import Path
import re
import secrets
import signal
import socket
import sqlite3
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
PHP = os.environ.get('PHP_BIN', 'php')
PASSWORD = secrets.token_urlsafe(25)
checks = 0


def check(condition, label):
    global checks
    if not condition:
        raise AssertionError(label)
    checks += 1
    print('PASS:', label, flush=True)


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args):
        return None


class Client:
    def __init__(self, base):
        self.base = base
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(NoRedirect, urllib.request.HTTPCookieProcessor(self.cookies))

    def request(self, path='/', data=None, method=None):
        req = urllib.request.Request(self.base + path,
            data=urllib.parse.urlencode(data).encode() if data is not None else None, method=method)
        try:
            response = self.opener.open(req, timeout=15)
        except urllib.error.HTTPError as error:
            response = error
        return response.status, response.headers, response.read().decode('utf-8-sig')

    def token(self, path='/?page=login'):
        status, _, text = self.request(path)
        check(status == 200, f'Form loads: {path}')
        return re.search(r'name="csrf" value="([a-f0-9]{64})"', text).group(1)

    def login(self, index):
        token = self.token()
        before = [(c.name, c.value) for c in self.cookies]
        status, headers, _ = self.request('/?page=login',
            {'action': 'login', 'email': f'user{index}@example.invalid', 'password': PASSWORD, 'csrf': token})
        check(status == 303 and headers['Location'] == '/', f'Login succeeds for fixture {index}')
        check(before != [(c.name, c.value) for c in self.cookies], 'Session ID rotated on authentication')


def start_server(env):
    with socket.socket() as sock:
        sock.bind(('0.0.0.0', 0))
        port = sock.getsockname()[1]
    log = tempfile.TemporaryFile()
    process = subprocess.Popen([PHP, '-S', f'0.0.0.0:{port}', '-t', 'public', 'public/router.php'],
        cwd=ROOT, env=env, stdout=log, stderr=log, start_new_session=True)
    base = f'http://127.0.0.1:{port}'
    # A bounded readiness wait, internal to this self-contained test process.
    for _ in range(100):
        if process.poll() is not None:
            log.seek(0)
            raise RuntimeError(log.read().decode())
        try:
            Client(base).request('/?page=login')
            return process, log, base
        except (OSError, urllib.error.URLError):
            time.sleep(.1)
    os.killpg(process.pid, signal.SIGTERM)
    raise RuntimeError('Test server did not start')


def stop_server(process, log):
    if process.poll() is None:
        os.killpg(process.pid, signal.SIGTERM)
        try:
            process.wait(timeout=5)
        except subprocess.TimeoutExpired:
            os.killpg(process.pid, signal.SIGKILL)
            process.wait()
    log.close()


def run():
    with tempfile.TemporaryDirectory(prefix='test-gmispf-', dir=ROOT / 'storage') as temp:
        database = str(Path(temp) / 'test-app.sqlite')
        env = {**os.environ, 'APP_ENV': 'test', 'APP_DEMO': '0', 'APP_HTTPS': '0',
            'APP_DB': database, 'TEST_PASSWORD': PASSWORD}
        subprocess.run([PHP, 'tests/fixture.php'], cwd=ROOT, env=env, check=True)
        process, log, base = start_server(env)
        try:
            guest = Client(base)
            status, headers, _ = guest.request('/?page=reports')
            check(status == 303 and headers['Location'] == '/?page=login', 'Guest cannot read reports')
            check(guest.request('/?page=export')[0] == 303, 'Guest cannot export')
            check(guest.request('/?page=login', {'action': 'login'})[0] == 403, 'Login requires CSRF')
            fresh = Client(base)
            _, headers, _ = fresh.request('/?page=login')
            cookie = headers.get('Set-Cookie', '')
            check('HttpOnly' in cookie and 'SameSite=Lax' in cookie, 'Development session cookie protections')
            check("frame-ancestors 'none'" in headers['Content-Security-Policy'], 'App disallows framing')
            check(headers['X-Content-Type-Options'] == 'nosniff', 'MIME sniffing disabled')
            check('no-store' in headers['Cache-Control'], 'Private pages cannot be cached')
            check('X-Powered-By' not in headers, 'Runtime signature removed')
            check(guest.request('/?page=login', method='DELETE')[0] == 405, 'Unexpected methods rejected')
            for path in ['/../app/bootstrap.php','/app/bootstrap.php','/storage/app.sqlite','/database/schema.sql',
                         '/.env','/.git/config','/gmispf.sql','/fonction.zip','/delete.php?idrapport=1','/public/router.php']:
                check(guest.request(path)[0] == 404, 'Private/legacy path blocked: ' + path)
            check(guest.request('/?page=login', {'x':'a'*17000})[0] == 413, 'Oversized forms rejected')
            admin, agent, provider, other = [Client(base) for _ in range(4)]
            for index, client in enumerate([admin, agent, provider, other]):
                client.login(index)
            form = {'action':'save','title':'Fuite de test','location':'Bâtiment A','category':'Plomberie',
                'priority':'Urgente','status':'À traiter','condition':'Critique','part':'Joint','notes':'Rapport de test',
                'owner_id':'4','version':'0'}
            token = provider.token('/?page=new')
            check(provider.request('/?page=new', form)[0] == 403, 'Write without CSRF rejected')
            check(provider.request('/?page=new', {**form,'csrf':'0'*64})[0] == 403, 'Wrong CSRF rejected')
            check(provider.request('/?page=new', {**form,'csrf':token,'title':'a'})[0] == 422, 'Server validates text length')
            check(provider.request('/?page=new', {**form,'csrf':token,'priority':'critical'})[0] == 422, 'Server validates enum')
            status, headers, _ = provider.request('/?page=new', {**form,'csrf':token})
            check(status == 303, 'Provider creates report')
            report_path = headers['Location']
            report_id = int(urllib.parse.parse_qs(urllib.parse.urlparse(report_path).query)['id'][0])
            con = sqlite3.connect(database)
            check(con.execute('SELECT owner_id FROM reports WHERE id=?',[report_id]).fetchone()[0] == 3, 'Posted owner ignored')
            check(other.request(report_path)[0] == 404, 'Other provider cannot read guessed ID')
            check(other.request(f'/?page=edit&id={report_id}')[0] == 404, 'Other provider cannot edit guessed ID')
            for page in ['reports','export','history']:
                check('Fuite de test' not in other.request('/?page='+page)[2], 'Other provider cannot see report in '+page)
            check(agent.request(report_path)[0] == 200, 'Agent may read report')
            check(agent.request('/?page=new')[0] == 403, 'Agent cannot access creation form')
            check(agent.request(f'/?page=edit&id={report_id}')[0] == 403, 'Agent cannot access edit form')
            agent_csrf = agent.token('/?page=account')
            check(agent.request(report_path, {'action':'archive','csrf':agent_csrf,'version':'1'})[0] == 403, 'Agent cannot archive')
            check(agent.request('/?page=new', {**form,'csrf':agent_csrf})[0] == 403, 'Agent cannot create by forged POST')
            edit_path = f'/?page=edit&id={report_id}'
            edit_token = provider.token(edit_path)
            attack = "<script>alert(1)</script>'); DROP TABLE reports; --"
            status, _, _ = provider.request(edit_path, {**form,'csrf':edit_token,'version':'1','title':attack})
            check(status == 303, 'SQL and XSS payload submitted as inert content')
            html = provider.request(report_path)[2]
            check('&lt;script&gt;' in html and '<script>alert(1)</script>' not in html, 'Stored XSS escaped in detail')
            check('<script>alert(1)</script>' not in provider.request(edit_path)[2], 'Stored XSS escaped in edit inputs')
            check(provider.request(edit_path, {**form,'csrf':edit_token,'version':'1'})[0] == 409, 'Stale update returns 409')
            search = '/?page=reports&q=' + urllib.parse.quote("' OR 1=1 --")
            check('Aucune intervention trouvée' in admin.request(search)[2], 'SQL injection in search returns no extra data')
            check(provider.request('/?page=reports&status=oops')[0] == 400, 'Invalid filter rejected')
            check(provider.request('/?page=report&id[]=1')[0] == 404, 'Array ID rejected without PHP error')
            provider.request(edit_path,{**form,'csrf':edit_token,'version':'2','title':'=SUM(1+1)'})
            status, headers, text = provider.request('/?page=export')
            rows = list(csv.reader(io.StringIO(text), delimiter=';'))
            check(status==200 and 'text/csv' in headers['Content-Type'] and rows[1][1].startswith("'="), 'CSV neutralizes formula')
            admin_token = admin.token('/?page=account')
            check(admin.request(report_path + '&action=archive')[0] == 200, 'GET never performs archive')
            check(con.execute('SELECT archived FROM reports WHERE id=?',[report_id]).fetchone()[0] == 0, 'GET left report intact')
            check(admin.request(report_path, {'action':'archive','csrf':admin_token,'version':'1'})[0] == 409, 'Stale archive returns 409')
            check(admin.request(report_path, {'action':'archive','csrf':admin_token,'version':'3'})[0] == 303, 'Admin archive succeeds')
            check('Aucune intervention trouvée' in admin.request('/?page=reports')[2], 'Archive removed from active listing')
            check(admin.request(report_path)[0] == 200, 'Archived record remains readable')
            check('Intervention archivée' in admin.request('/?page=history')[2], 'Archive remains in audit history')
            check(provider.request(edit_path)[0] == 403, 'Archived record is not editable')
            con.execute('UPDATE users SET active=0 WHERE id=4')
            con.commit()
            check(other.request('/?page=reports')[0] == 303, 'Deactivation revokes existing session')
            con.execute('UPDATE users SET auth_version=auth_version+1 WHERE id=3')
            con.commit()
            check(provider.request('/?page=reports')[0] == 303, 'Password rotation revokes existing session')
            old_session = [(c.name,c.value) for c in admin.cookies]
            check(admin.request('/?page=account', {'action':'logout','csrf':admin_token})[0] == 303, 'Logout via protected POST')
            check(admin.request('/?page=reports')[0] == 303, 'Logged-out session cannot read')
            replay = Client(base)
            replay.opener.addheaders = [('Cookie', '; '.join(f'{k}={v}' for k,v in old_session))]
            check(replay.request('/?page=reports')[0] == 303, 'Old session cookie cannot be replayed')
            token = guest.token()
            for _ in range(5):
                check(guest.request('/?page=login', {'action':'login','csrf':token,'email':'unknown@example.invalid','password':'wrong'})[0] == 422, 'Generic failed login response')
            status, headers, _ = guest.request('/?page=login', {'action':'login','csrf':token,'email':'unknown@example.invalid','password':'wrong'})
            check(status==429 and headers['Retry-After']=='900', 'Brute-force rate limit persists')
            check(all(row[0].startswith('$2y$12$') for row in con.execute('SELECT password_hash FROM users')), 'Only adaptive password hashes stored')
            con.close()
        finally:
            stop_server(process, log)
        demo_env = {**env,'APP_DEMO':'1','APP_DB':str(Path(temp)/'missing.sqlite')}
        process, log, base = start_server(demo_env)
        try:
            demo = Client(base)
            for path in ['/', '/?page=reports','/?page=installations','/?page=history','/?page=help','/?page=account',
                         '/?page=report&id=128','/?page=edit&id=128','/?page=reports&p=2','/?page=login']:
                check(demo.request(path)[0] == 200, 'Demo works without database: '+path)
            token = demo.token('/?page=new')
            check(demo.request('/?page=new',{'action':'save','csrf':token})[0] == 403, 'Demo writes refused server-side')
            check(demo.request('/?page=report&id=128',{'action':'archive','csrf':token})[0] == 403, 'Demo archive refused server-side')
            token = demo.token('/?page=login')
            check(demo.request('/?page=login',{'action':'login','csrf':token,'email':'x@example.invalid','password':'any'})[0] == 403, 'Demo login cannot touch real users')
            check(not Path(demo_env['APP_DB']).exists(), 'Demo never created a database')
        finally:
            stop_server(process, log)
        process, log, base = start_server({**demo_env,'APP_ENV':'production'})
        try:
            status, headers, _ = Client(base).request('/')
            check(status==303, 'Demo switch cannot bypass production authentication')
            cookie = headers.get('Set-Cookie','')
            check('__Host-gmispf=' in cookie and 'secure' in cookie.lower() and 'HttpOnly' in cookie, 'Production cookie is host-only, Secure and HttpOnly')
            check(headers['Strict-Transport-Security']=='max-age=31536000', 'Production HSTS enabled')
            check("frame-ancestors 'none'" in headers['Content-Security-Policy'], 'Production forbids framing')
        finally:
            stop_server(process, log)
    print(f'\n{checks} HTTP assertions passed. Test servers and databases cleaned up.')


if __name__ == '__main__':
    run()
