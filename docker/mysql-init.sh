#!/usr/bin/env bash
set -euo pipefail
# Only runs on this Compose project's NEW private volume, never on host MySQL.
[[ "$DB_PASSWORD" =~ ^[a-f0-9]{64}$ && "$DB_MIGRATION_PASSWORD" =~ ^[a-f0-9]{64}$ ]] || exit 64
MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql --protocol=socket -uroot <<SQL
CREATE USER 'espacio_u_app'@'%' IDENTIFIED BY '${DB_PASSWORD}';
CREATE USER 'espacio_u_migrate'@'%' IDENTIFIED BY '${DB_MIGRATION_PASSWORD}';
GRANT SELECT, INSERT, UPDATE, DELETE ON espacio_u_container.* TO 'espacio_u_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES ON espacio_u_container.* TO 'espacio_u_migrate'@'%';
SQL
