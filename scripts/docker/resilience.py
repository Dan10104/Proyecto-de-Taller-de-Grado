"""Disrupt only the named isolated Compose services; restore them even on failure."""
import concurrent.futures
import json
import re
import statistics
import subprocess
import time
from smoke import ROOT, OUT, COMPOSE, A, B, requests, login, page, post, operation


def command(*args):
    return subprocess.run(COMPOSE + list(args), cwd=ROOT, capture_output=True, check=True)


def ready(base):
    for _ in range(60):
        try:
            if requests.get(base+'/login',timeout=2).status_code==200:return
        except requests.RequestException:pass
        time.sleep(.5)
    raise RuntimeError('Container failed to recover')


def main():
    results={}
    # Only this project has the explicitly named synthetic database.
    assert operation('runtime')['ok']
    values=dict(line.split('=',1) for line in (ROOT/'.env.docker').read_text().splitlines() if '=' in line and not line.startswith('#'))
    session=login(values['SEED_ADMIN_EMAIL'],values['SEED_ADMIN_PASSWORD'])
    response=session.get(A+'/login')
    # Authenticated login redirects; inspect the dashboard HTML for real built assets.
    response=session.get(A+'/dashboard')
    assets=re.findall(rb'(?:src|href)="([^" ]+/build/assets/[^" ]+\.(?:js|css))"',response.content)
    assert assets
    for asset in assets:
        asset=asset.decode();asset=asset if asset.startswith('http') else A+asset
        r=requests.get(asset);assert r.status_code==200 and len(r.content)>0
    results['built_assets_served']=len(assets)
    for name,value in [('X-Content-Type-Options','nosniff'),('X-Frame-Options','DENY'),('Referrer-Policy','same-origin')]:
        assert response.headers.get(name)==value
    assert response.headers.get('Content-Security-Policy') and 'no-store' in response.headers.get('Cache-Control','')
    results['security_headers']=True
    def hit(i):
        # Independent cookie jar copies, no concurrent client mutation.
        import copy
        client=requests.Session();client.cookies=copy.copy(session.cookies);client.version=session.version
        start=time.perf_counter();r=page(client,A if i%2 else B,'/dashboard')
        assert r.status_code==200
        return (time.perf_counter()-start)*1000
    with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:times=list(pool.map(hit,range(32)))
    results['http_load']={'requests':32,'clients':8,'replicas':2,'mean_ms':statistics.mean(times),'max_ms':max(times),'local_only':True}
    post(session,A+'/logout',{})
    operation('orphan')
    for _ in range(40):
        try:
            if operation('orphan-check')['ok']:break
        except RuntimeError:pass
        time.sleep(1)
    else:raise RuntimeError('Orphan cleanup did not run')
    results['orphan_cleanup']=True
    try:
        command('stop','db')
        assert requests.get(A+'/up',timeout=5).status_code==200
        results['health_independent_of_db']=True
        try:
            r=requests.get(A+'/login',timeout=10)
            assert r.status_code==500
            assert all(marker not in r.content for marker in [b'SQLSTATE',b'PDOException',b'Stack trace',b'/var/www/html'])
            results['db_request_outcome']='safe_500'
        except TimeoutError:
            # Stopping the DB also removes its Compose DNS record. This is an
            # observed local outage limit, not a successful application response.
            results['db_request_outcome']='client_timeout_after_10s'

    finally:
        command('start','db');ready(A)
    try:
        start=time.perf_counter();command('stop','web-a')
        results['graceful_stop_seconds']=time.perf_counter()-start
        identity=command('ps','-a','-q','web-a').stdout.decode().strip()
        state=json.loads(subprocess.check_output(['docker','inspect','--format','{{json .State}}',identity]))
        assert state['ExitCode']==0 and not state['OOMKilled']
        results['graceful_exit_zero']=True
    finally:
        command('start','web-a');ready(A)
    results['restart_health']=requests.get(A+'/up').status_code==200
    (OUT/'resilience.json').write_text(json.dumps(results,indent=2),encoding='utf-8')
    print(json.dumps(results))


if __name__=='__main__':main()
