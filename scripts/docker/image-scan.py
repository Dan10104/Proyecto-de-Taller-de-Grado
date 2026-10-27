"""Inspect immutable image layers in memory. Never export a running container's secrets."""
import io,json,re,subprocess,tarfile,hashlib
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2]
secrets=[]
for path in ROOT.glob('.env*'):
    if path.name.endswith('.example'):continue
    for line in path.read_text(encoding='utf-8-sig').splitlines():
        if '=' not in line or line.startswith('#'):continue
        key,value=line.split('=',1);value=value.strip().strip('"').strip("'")
        if re.search(r'PASSWORD|SECRET|TOKEN|API_KEY|PRIVATE_KEY|APP_KEY',key) and len(value)>=16:
            secrets.append((key,value.encode()))
findings=[];files=0;layers=0;private_paths=[];names=[];public_vectors=[]
# Published GnuTLS 3.7.9 crypto-selftests-pk.c, byte-matched to the pinned PHP base.
# Recognition requires BOTH exact library content and exact vector fingerprint.
GNUTLS_LIBRARY_SHA256 = '779b25d20249988bea2c1aa6bbeb218f5ae7ea8a9d30ce4f54ea37372965cc4b'
GNUTLS_PUBLIC_VECTORS = {
    'a4d138d7ef9748464117b44fb9c0a4b5b85a1599a127d02690abaa96d03c16e6',
    'fa0b06a72461ec0a963dcfccb8d5b61bd88a6074fc7271573bff68ab86b8c1af',
    'ef237ea8db4f2ae9ee100e8ced96d29b5dceb0e6a948443e6b8a00b1791f9ec9',
    '91ea1699ff6b1a34b4a1d500a9c75a808441e47b9ea68da6fb0195e01ce1dc61',
    'd039c8119a029ab9f9c83c04d67002d887b6bc6026c4264402ab27cdf24cf138',
}
def scan(name,data):
    for key,value in secrets:
        if value in data:findings.append({'path':name,'kind':'local-secret','key':key})
    for match in re.finditer(rb'-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----[\r\n]+[A-Za-z0-9+/=\r\n]{80,}-----END (?:RSA |EC |OPENSSH )?PRIVATE KEY-----',data):
        digest = hashlib.sha256(match.group()).hexdigest()
        if (name == 'usr/lib/x86_64-linux-gnu/libgnutls.so.30.34.3'
                and hashlib.sha256(data).hexdigest() == GNUTLS_LIBRARY_SHA256
                and digest in GNUTLS_PUBLIC_VECTORS):
            public_vectors.append({'path':name,'sha256':digest,'reason':'published upstream self-test'})
        else:
            findings.append({'path':name,'kind':'private-key','sha256':digest})
p=subprocess.Popen(['docker','image','save','espacio-u:phase14'],stdout=subprocess.PIPE,stderr=subprocess.PIPE)
with tarfile.open(fileobj=p.stdout,mode='r|') as image:
    for member in image:
        if not member.isfile():continue
        data=image.extractfile(member).read()
        try:
            layer=tarfile.open(fileobj=io.BytesIO(data),mode='r:*')
        except tarfile.TarError:
            scan(member.name,data);continue
        layers+=1
        with layer:
            for item in layer:
                name=item.name.lstrip('./')
                if name.startswith('var/www/html/'):
                    relative=name[len('var/www/html/'):];names.append(relative)
                    if relative=='.env' or relative.startswith(('.git/','.local/','.tools/','tests/','node_modules/','blockchain/node_modules/hardhat/','blockchain/node_modules/zod/','blockchain/artifacts/')):
                        private_paths.append(relative)
                if item.isfile():
                    files+=1
                    scan(name,layer.extractfile(item).read())
if p.wait()!=0:raise RuntimeError('Image export failed')
result={'layers':layers,'files_scanned':files,'local_secret_values_checked':len(secrets),'findings':findings,'forbidden_paths':private_paths,'public_upstream_vectors':public_vectors}
(ROOT/'.local/qa/phase14/image-scan.json').write_text(json.dumps(result,indent=2),encoding='utf-8')
(ROOT/'.local/qa/phase14/image-files.json').write_text(json.dumps(sorted(set(names))),encoding='utf-8')
print(json.dumps(result))
assert not findings and not private_paths
