"""Restore a synthetic Compose backup into a separate local schema; no dump is saved."""
import json,subprocess,time
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2]
BASE=['docker','compose','--env-file','.env.docker','-f','compose.production-like.yml','exec','-T','db','sh','-c']
GUARD='test "$MYSQL_DATABASE" = espacio_u_container || exit 64; export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"; '
def sql(statement):
    return subprocess.run(BASE+[GUARD+'mysql -uroot --batch --skip-column-names'],input=statement.encode(),capture_output=True,cwd=ROOT,check=True).stdout.decode().strip()
start=time.perf_counter()
# CREATE deliberately fails if that schema already exists; never overwrite someone else's data.
sql('CREATE DATABASE espacio_u_restore_check CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;')
try:
    backup=subprocess.Popen(BASE+[GUARD+'mysqldump -uroot --single-transaction --no-tablespaces --set-gtid-purged=OFF espacio_u_container'],stdout=subprocess.PIPE,stderr=subprocess.PIPE,cwd=ROOT)
    restore=subprocess.run(BASE+[GUARD+'mysql -uroot espacio_u_restore_check'],stdin=backup.stdout,stdout=subprocess.PIPE,stderr=subprocess.PIPE,cwd=ROOT)
    backup.stdout.close()
    if backup.wait()!=0 or restore.returncode!=0:raise RuntimeError('Synthetic restore failed; diagnostics deliberately not printed.')
    tables=int(sql("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='espacio_u_restore_check';"))
    foreign=int(sql("SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA='espacio_u_restore_check';"))
    assert tables==36 and foreign==63
    for table in ['usuarios','reservas','prestamos','registros_actividad','evidencias_trazabilidad','notificaciones']:
        counts=sql(f'SELECT (SELECT COUNT(*) FROM espacio_u_container.{table}), (SELECT COUNT(*) FROM espacio_u_restore_check.{table});').split()
        assert counts[0]==counts[1], table
    # Recovered auth payloads must never revive a historical session.
    sql('DELETE FROM espacio_u_restore_check.sessions; UPDATE espacio_u_restore_check.sesiones_acceso SET revocada_en=NOW() WHERE revocada_en IS NULL;')
    result={'ok':True,'tables':tables,'foreign_keys':foreign,'seconds':round(time.perf_counter()-start,3),'sessions_invalidated':True,'cloud_restore':False,'dump_persisted':False}
    (ROOT/'.local/qa/phase14/recovery-local.json').write_text(json.dumps(result,indent=2),encoding='utf-8')
    print(json.dumps(result))
finally:
    sql('DROP DATABASE espacio_u_restore_check;')
