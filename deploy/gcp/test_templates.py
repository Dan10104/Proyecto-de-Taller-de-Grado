import copy
import json
import unittest
from pathlib import Path
from render import manifests, validate
from plan import plan


class TemplatesTest(unittest.TestCase):
    def setUp(self):
        self.config=json.loads(Path(__file__).with_name('config.example.json').read_text())

    def test_same_immutable_image_in_every_workload_and_no_literal_secret(self):
        rendered=manifests(self.config)
        self.assertEqual(set(rendered), {'service.json','migrate-job.json','scheduler-job.json','queue-job.json'})
        images=[]
        for name,body in rendered.items():
            spec=body['spec']['template']['spec']
            if name!='service.json':spec=spec['template']['spec']
            container=spec['containers'][0]; images.append(container['image'])
            for entry in container['env']:
                if entry['name'] in ['APP_KEY','DB_PASSWORD','IOT_MASTER_SECRET']:
                    self.assertNotIn('value',entry)
                    self.assertNotEqual(entry['valueFrom']['secretKeyRef']['key'],'latest')
        self.assertEqual(len(set(images)),1)
        self.assertIn('@sha256:', images[0])

    def test_service_limits_probes_and_old_revision_traffic_are_explicit(self):
        service=manifests(self.config)['service.json'];template=service['spec']['template'];spec=template['spec']
        self.assertEqual(spec['containerConcurrency'],20)
        self.assertEqual(spec['timeoutSeconds'],60)
        self.assertEqual(template['metadata']['annotations']['autoscaling.knative.dev/maxScale'],'3')
        self.assertEqual(template['metadata']['annotations']['run.googleapis.com/cpu-throttling'],'false')
        self.assertEqual(spec['containers'][0]['startupProbe']['httpGet']['path'],'/up')
        self.assertEqual(service['spec']['traffic'][0]['percent'],100)
        self.assertEqual(service['spec']['traffic'][1]['percent'],0)

    def test_jobs_are_bounded_and_migration_has_separate_identity(self):
        values=manifests(self.config)
        for name in ['migrate','scheduler','queue']:
            spec=values[name+'-job.json']['spec']['template']['spec']
            self.assertEqual(spec['taskCount'],1);self.assertEqual(spec['parallelism'],1)
            task=spec['template']['spec'];self.assertEqual(task['maxRetries'],0)
            self.assertLessEqual(task['timeoutSeconds'],600)
        migration=values['migrate-job.json']['spec']['template']['spec']['template']['spec']
        self.assertIn('espacio-u-migrate@',migration['serviceAccountName'])
        self.assertEqual(migration['containers'][0]['args'],['php','artisan','migrate','--force','--no-interaction'])

    def test_placeholder_configuration_cannot_pass_strict_validation(self):
        with self.assertRaises(ValueError):validate(self.config)
        validate(self.config,True)

    def test_plan_cannot_publish_public_access_or_use_unsafe_database_operations(self):
        steps=plan(self.config,Path('templates'))
        text=json.dumps(steps)
        for forbidden in ['allUsers','0.0.0.0/0','migrate:fresh','migrate:rollback','service-account-key','--root-password']:
            self.assertNotIn(forbidden,text)
        positions={step['stage']:i for i,step in enumerate(steps)}
        self.assertLess(positions['migration-before-traffic'],positions['candidate-private-or-zero-traffic'])
        self.assertLess(positions['traffic-only-after-approved-smoke'],positions['after-smoke-and-traffic-only'])
        self.assertIn('--oauth-service-account-email',text)
        self.assertIn('--retained-backups-count=7',text)
        self.assertIn('--enable-bin-log',text)

    def test_committed_templates_match_renderer(self):
        for name,value in manifests(self.config).items():
            self.assertEqual(value,json.loads(Path(__file__).with_name('templates').joinpath(name).read_text()))


if __name__=='__main__':unittest.main()
