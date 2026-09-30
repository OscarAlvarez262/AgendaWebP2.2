-- ---------------------------------------------------------------
-- Esquema de AgendaWeb
--
-- Este archivo se importa en el deploy script de DomCloud:
--     mariadb -u "$USERNAME" -p"$MYPASSWD" "$DATABASE" < db.sql
--
-- Por eso NO lleva CREATE DATABASE ni USE: en DomCloud la base de
-- datos la crea el propio hosting con `features: - mysql create agenda`
-- y se llama ${USERNAME}_agenda. Crear bases desde el CLI esta prohibido.
--
-- En local tu creates la base a mano una sola vez:
--     CREATE DATABASE agenda CHARACTER SET utf8mb4;
--
-- Es idempotente (IF NOT EXISTS): volver a importar no borra datos.
-- ---------------------------------------------------------------

CREATE TABLE IF NOT EXISTS eventos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    titulo      VARCHAR(120) NOT NULL,
    fecha       DATE NOT NULL,
    hora        TIME NULL,
    categoria   VARCHAR(20) NOT NULL,
    prioridad   VARCHAR(10) NOT NULL DEFAULT 'media',
    descripcion VARCHAR(500) NULL,
    creado_en   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX (fecha, hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
