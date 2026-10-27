"""Cross-container locks, quota, notification and release idempotence; synthetic DB only."""
import concurrent.futures
import json
import secrets
import subprocess
from pathlib import Path

ROOT=Path(__file__).resolve().parents[2]
COMPOSE=['docker','compose','--env-file','.env.docker','-f','compose.production-like.yml']
SOURCE=(ROOT/'scripts/docker/concurrency.php').read_bytes()


def command(replica,mode,*args):
    return COMPOSE+['exec','-T',replica,'php','/dev/stdin',mode,*map(str,args)]


def run(replica,mode,*args):
    p=subprocess.run(command(replica,mode,*args),input=SOURCE,capture_output=True,cwd=ROOT)
    if p.returncode: raise RuntimeError('Container worker failed: '+mode)
    return p.stdout.decode().strip()


def main():
    results={}
    for lock in ['cache','wallet']:
        key=secrets.token_hex(8)
        p=subprocess.Popen(command('web-a',lock+'-hold',key),stdin=subprocess.PIPE,stdout=subprocess.PIPE,stderr=subprocess.PIPE,cwd=ROOT)
        p.stdin.write(SOURCE);p.stdin.close()
        assert p.stdout.readline().strip()==b'ACQUIRED'
        assert run('web-b',lock+'-try',key)=='BUSY'
        assert p.wait(timeout=15)==0
        assert run('web-b',lock+'-try',key)=='ACQUIRED'
        results[lock+'_cross_replica_exclusion']=True
    key=secrets.token_hex(8)
    with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:
        outputs=list(pool.map(lambda i:run('web-a' if i%2 else 'web-b','quota',key),range(20)))
        assert outputs.count('ACCEPTED')==10 and outputs.count('LIMITED')==10, 'quota counts'
        results['iot_quota']={'accepted':10,'limited':10,'workers':8,'replicas':2}
        fixture=json.loads(run('web-a','prepare',key))
        notifications=list(pool.map(lambda i:run('web-a' if i%2 else 'web-b','notify',fixture['activity']),range(8)))
        evaluations=list(pool.map(lambda i:run('web-a' if i%2 else 'web-b','release',fixture['reservation']),range(8)))
    counts=json.loads(run('web-a','verify',fixture['activity'],fixture['reservation']))
    assert counts=={'notifications':1,'evaluations':1}, counts
    assert evaluations.count('CREATED')==1
    results['domain_idempotence']=counts
    results['php_node_ethers_local_rpc']=run('web-b','bridge')=='LOCAL_BRIDGE_OK'
    assert results['php_node_ethers_local_rpc']
    (ROOT/'.local/qa/phase14/concurrency.json').write_text(json.dumps(results,indent=2),encoding='utf-8')
    print(json.dumps(results))


if __name__=='__main__':main()
