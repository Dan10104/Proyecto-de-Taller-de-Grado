-- Plantilla para revisión humana tras crear los dos usuarios con contraseñas
-- generadas en un canal privado y registradas en Secret Manager. Nunca usar
-- contraseñas literales en este archivo ni ejecutarlo durante Fase 14.
-- Ejecutar con una identidad administrativa SQL temporal; retirar después.
GRANT SELECT, INSERT, UPDATE, DELETE ON espacio_u.* TO 'espacio_u_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES
    ON espacio_u.* TO 'espacio_u_migrate'@'%';
-- Comprobar SHOW GRANTS de ambas cuentas; retirar grants amplios que la consola
-- pudiera haber asignado al crearlas. Sin GRANT OPTION ni privilegios globales.
