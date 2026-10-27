"""Render Cloud Run manifests locally. Never calls gcloud, Docker, or a network API."""
import argparse
import copy
import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]


def validate(c, placeholders=False):
    text = json.dumps(c)
    if not placeholders and 'REPLACE_' in text:
        raise ValueError('Resolve every placeholder before deployment planning.')
    if c['optional_integrations'] != {'chatbot_enabled': False, 'blockchain_enabled': False}:
        raise ValueError('External integrations require their separate approved activation procedure.')
    if not c['app_url'].startswith('https://') or c['database'].endswith('_testing'):
        raise ValueError('Production requires HTTPS and an operational database.')
    if c['runtime_db_user'] == 'root' or c['migration_db_user'] == 'root':
        raise ValueError('Root database credentials are prohibited.')
    if not placeholders:
        for key, pattern in [('project_id', r'[a-z][a-z0-9-]{4,28}[a-z0-9]'), ('region', r'[a-z]+-[a-z]+[0-9]'), ('repository', r'[a-z][a-z0-9-]+'), ('sql_instance', r'[a-z][a-z0-9-]+'), ('database', r'[a-z][a-z0-9_]+'), ('runtime_db_user', r'[a-z][a-z0-9_]+'), ('migration_db_user', r'[a-z][a-z0-9_]+'), ('git_sha', r'[a-f0-9]{40}'), ('image_digest', r'[a-f0-9]{64}')]:
            if not re.fullmatch(pattern, c[key]):
                raise ValueError('Invalid configuration field: '+key)
        from datetime import datetime
        datetime.strptime(c['notification_events_from'], '%Y-%m-%d %H:%M:%S')
        for entry in c['secrets'].values():
            if set(entry) != {'name', 'version'} or not re.fullmatch(r'[a-zA-Z0-9_-]+', entry['name']) or not re.fullmatch(r'[1-9][0-9]*', str(entry['version'])):
                raise ValueError('Secret references must use names and explicit numeric versions only.')
    if c['web_min_instances'] < 0 or c['web_max_instances'] < max(1, c['web_min_instances']) or c['web_concurrency'] < 1:
        raise ValueError('Invalid scaling limits.')


def manifests(c):
    project, region = c['project_id'], c['region']
    image = f"{region}-docker.pkg.dev/{project}/{c['repository']}/espacio-u@sha256:{c['image_digest']}"
    sql = f"{project}:{region}:{c['sql_instance']}"
    sa = lambda role: f'espacio-u-{role}@{project}.iam.gserviceaccount.com'
    common = {
        'APP_NAME':'ESPACIO-U', 'APP_ENV':'production', 'APP_DEBUG':'false', 'APP_URL':c['app_url'],
        'APP_TIMEZONE':'America/La_Paz', 'DEPLOYMENT_TARGET':'cloud-run', 'DB_CONNECTION':'mysql',
        'DB_SOCKET':'/cloudsql/'+sql, 'DB_HOST':'localhost', 'DB_PORT':'3306',
        'DB_DATABASE':c['database'], 'DB_USERNAME':c['runtime_db_user'], 'DB_TIMEZONE':'-04:00',
        'SESSION_DRIVER':'database', 'SESSION_BLOCK_STORE':'database', 'SESSION_SECURE_COOKIE':'true',
        'SESSION_HTTP_ONLY':'true', 'SESSION_SAME_SITE':'lax', 'CACHE_STORE':'database',
        'IOT_RATE_STORE':'database', 'QUEUE_CONNECTION':'database', 'QUEUE_EFFECTS_CONNECTION':'database',
        'QUEUE_FAILED_DRIVER':'null', 'DB_QUEUE_RETRY_AFTER':'120', 'LOG_CHANNEL':'stderr',
        'LOG_EMERGENCY_PATH':'php://stderr', 'LOG_LEVEL':'warning', 'CHATBOT_ENABLED':'false',
        'BLOCKCHAIN_ENABLED':'false', 'BLOCKCHAIN_NODE_BINARY':'/usr/local/bin/node',
        'NOTIFICATIONS_EVENTS_FROM':c['notification_events_from'],
    }
    def env(role):
        values = dict(common)
        if role == 'migrate': values['DB_USERNAME'] = c['migration_db_user']
        entries = [{'name':key, 'value':value} for key,value in values.items()]
        refs = {'APP_KEY':'app_key', 'DB_PASSWORD':'migration_password' if role == 'migrate' else 'database_password'}
        if role == 'web': refs['IOT_MASTER_SECRET'] = 'iot_master_secret'
        for key, ref in refs.items():
            value = c['secrets'][ref]
            entries.append({'name':key, 'valueFrom':{'secretKeyRef':{'name':value['name'], 'key':str(value['version'])}}})
        return entries
    annotations = {'run.googleapis.com/cloudsql-instances':sql, 'run.googleapis.com/execution-environment':'gen2'}
    service = {'apiVersion':'serving.knative.dev/v1','kind':'Service','metadata':{'name':'espacio-u-web','labels':{'cloud.googleapis.com/location':region},'annotations':{'run.googleapis.com/ingress':'all'}},'spec':{'template':{'metadata':{'labels':{'commit':c['git_sha']},'annotations':{**annotations,'autoscaling.knative.dev/minScale':str(c['web_min_instances']),'autoscaling.knative.dev/maxScale':str(c['web_max_instances']),'run.googleapis.com/cpu-throttling':'false'}},'spec':{'serviceAccountName':sa('web'),'containerConcurrency':c['web_concurrency'],'timeoutSeconds':c['web_timeout_seconds'],'containers':[{'image':image,'ports':[{'containerPort':8080}],'env':env('web'),'resources':{'limits':{'cpu':str(c['web_cpu']),'memory':c['web_memory']}},'startupProbe':{'httpGet':{'path':'/up','port':8080},'periodSeconds':5,'timeoutSeconds':2,'failureThreshold':24},'livenessProbe':{'httpGet':{'path':'/up','port':8080},'periodSeconds':30,'timeoutSeconds':5,'failureThreshold':3}}]}}}}
    previous = c['previous_revision']
    if previous:
        service['spec']['traffic'] = [{'revisionName':previous,'percent':100},{'latestRevision':True,'percent':0,'tag':'candidate'}]
    else:
        # First staging service remains IAM-private: never grants allUsers invoker.
        service['spec']['traffic'] = [{'latestRevision':True,'percent':100,'tag':'candidate'}]
    output={'service.json':service}
    commands = {
        'migrate':['php','artisan','migrate','--force','--no-interaction'],
        'scheduler':['php','artisan','schedule:run','--no-interaction'],
        'queue':['php','artisan','queue:work','database','--stop-when-empty','--max-time=50','--max-jobs=100','--timeout=60','--tries=5','--sleep=1','--memory=128','--quiet'],
    }
    for role,args in commands.items():
        output[role+'-job.json']={'apiVersion':'run.googleapis.com/v1','kind':'Job','metadata':{'name':'espacio-u-'+role,'labels':{'cloud.googleapis.com/location':region}},'spec':{'template':{'spec':{'taskCount':1,'parallelism':1,'template':{'metadata':{'annotations':annotations},'spec':{'serviceAccountName':sa('migrate' if role=='migrate' else 'jobs'),'maxRetries':0,'timeoutSeconds':600 if role=='migrate' else 180,'containers':[{'image':image,'args':args,'env':env(role),'resources':{'limits':{'cpu':'1','memory':'1Gi'}}}]}}}}}}
    return output


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--config',type=Path,default=Path(__file__).with_name('config.example.json'))
    parser.add_argument('--output',type=Path,default=ROOT/'.local/deploy/gcp')
    parser.add_argument('--allow-placeholders',action='store_true')
    args=parser.parse_args()
    c=json.loads(args.config.read_text(encoding='utf-8'))
    validate(c,args.allow_placeholders)
    out=args.output.resolve()
    if not out.is_relative_to(ROOT): raise ValueError('Output must stay inside the official workspace.')
    out.mkdir(parents=True,exist_ok=True)
    for name,value in manifests(c).items():
        (out/name).write_text(json.dumps(value,indent=2)+'\n',encoding='utf-8')
    print('Rendered four manifests locally. No cloud operation executed.')


if __name__=='__main__': main()
