"""Print future cloud commands as JSON argument arrays. Deliberately has NO execute mode."""
import argparse
import json
from pathlib import Path
from render import ROOT, validate


def plan(c, directory):
    p,r=c['project_id'],c['region']
    scope=['--project',p]
    region=['--region',r]+scope
    sa=lambda role:f'espacio-u-{role}@{p}.iam.gserviceaccount.com'
    commands=[]
    def add(stage,*args): commands.append({'stage':stage,'argv':['gcloud',*args]})
    add('provision-after-approval','services','enable','run.googleapis.com','sqladmin.googleapis.com','artifactregistry.googleapis.com','secretmanager.googleapis.com','cloudscheduler.googleapis.com','cloudbuild.googleapis.com',*scope)
    add('provision-after-approval','artifacts','repositories','create',c['repository'],'--repository-format=docker','--immutable-tags','--location',r,*scope)
    for role in ['web','jobs','migrate','scheduler','build']:
        add('provision-after-approval','iam','service-accounts','create','espacio-u-'+role,*scope)
    add('provision-after-approval','sql','instances','create',c['sql_instance'],'--database-version=MYSQL_8_4','--edition=ENTERPRISE','--tier=db-custom-1-3840','--region',r,'--availability-type=ZONAL','--storage-size=20GB','--storage-type=SSD','--storage-auto-increase','--storage-auto-increase-limit=100','--backup-start-time=07:00','--retained-backups-count=7','--enable-bin-log','--retained-transaction-log-days=7','--deletion-protection','--retain-backups-on-delete','--connector-enforcement=REQUIRED','--database-flags=max_connections=200',*scope)
    add('provision-after-approval','sql','databases','create',c['database'],'--instance',c['sql_instance'],'--charset=utf8mb4','--collation=utf8mb4_unicode_ci',*scope)
    for role in ['web','jobs','migrate']:
        add('iam-after-approval','projects','add-iam-policy-binding',p,'--member=serviceAccount:'+sa(role),'--role=roles/cloudsql.client')
    refs={'web':['app_key','database_password','iot_master_secret'],'jobs':['app_key','database_password'],'migrate':['app_key','migration_password']}
    for ref in c['secrets'].values():
        add('secrets-after-approval','secrets','create',ref['name'],'--replication-policy=user-managed','--locations',r,*scope)
    for role,keys in refs.items():
        for key in keys:
            add('iam-after-approval','secrets','add-iam-policy-binding',c['secrets'][key]['name'],'--member=serviceAccount:'+sa(role),'--role=roles/secretmanager.secretAccessor',*scope)
    add('iam-after-approval','artifacts','repositories','add-iam-policy-binding',c['repository'],'--location',r,'--member=serviceAccount:'+sa('build'),'--role=roles/artifactregistry.writer',*scope)
    add('optional-cloud-build-iam','projects','add-iam-policy-binding',p,'--member=serviceAccount:'+sa('build'),'--role=roles/logging.logWriter')
    add('pre-deploy-backup','sql','backups','create','--instance',c['sql_instance'],*scope)
    add('verify-backup','sql','backups','list','--instance',c['sql_instance'],'--limit=3',*scope)
    image=f"{r}-docker.pkg.dev/{p}/{c['repository']}/espacio-u:{c['git_sha']}"
    commands.append({'stage':'build-and-push-after-approval','argv':['docker','buildx','build','--platform=linux/amd64','--build-arg','GIT_SHA='+c['git_sha'],'--tag',image,'--push','.']})
    add('resolve-pushed-digest','artifacts','docker','images','describe',image,*scope)
    for role in ['migrate','scheduler','queue']:
        add('prepare-jobs','run','jobs','replace',str(directory/(role+'-job.json')),*region)
    add('migration-before-traffic','run','jobs','execute','espacio-u-migrate','--wait',*region)
    add('candidate-private-or-zero-traffic','run','services','replace',str(directory/'service.json'),*region)
    add('traffic-only-after-approved-smoke','run','services','update-traffic','espacio-u-web','--to-tags=candidate=100',*region)
    for job in ['scheduler','queue']:
        add('scheduler-job-iam','run','jobs','add-iam-policy-binding','espacio-u-'+job,'--member=serviceAccount:'+sa('scheduler'),'--role=roles/run.invoker',*region)
        add('after-smoke-and-traffic-only','scheduler','jobs','create','http','espacio-u-'+job,'--location',r,'--schedule=* * * * *','--time-zone=America/La_Paz','--uri',f'https://run.googleapis.com/v2/projects/{p}/locations/{r}/jobs/espacio-u-{job}:run','--http-method=POST','--oauth-service-account-email',sa('scheduler'),'--oauth-token-scope=https://www.googleapis.com/auth/cloud-platform','--message-body={}','--max-retry-attempts=0',*scope)
    if c['previous_revision']:
        add('manual-rollback-traffic','run','services','update-traffic','espacio-u-web','--to-revisions='+c['previous_revision']+'=100',*region)
    return commands


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--config',type=Path,default=Path(__file__).with_name('config.example.json'))
    parser.add_argument('--manifests',type=Path,default=ROOT/'.local/deploy/gcp')
    parser.add_argument('--allow-placeholders',action='store_true')
    parser.add_argument('--dry-run',action='store_true',default=True)
    args=parser.parse_args()
    c=json.loads(args.config.read_text(encoding='utf-8'));validate(c,args.allow_placeholders)
    print(json.dumps({'dry_run':True,'cloud_changes':False,'commands':plan(c,args.manifests)},indent=2))


if __name__=='__main__': main()
