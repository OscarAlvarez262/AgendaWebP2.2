<?php

/*
 |--------------------------------------------------------------
 | Migración automática P7 → P8.
 |
 | Se ejecuta una vez por request desde partes/base_datos.php, así
 | el deploy no necesita pasos manuales en phpMyAdmin:
 |
 |   1. Crea usuarios si no existe.
 |   2. Añade eventos.usuario_id (con respaldo) si no existe.
 |   3. Añade la FK (los NULL no la violan).
 |   4. Cuando ya no quedan eventos sin dueño, pone NOT NULL.
 |
 | Es idempotente: si todo está listo, la comprobación cuesta una
 | sola consulta a information_schema.
 |--------------------------------------------------------------
 */

/**
 * Ejecuta SQL ignorando errores de "ya existe" (carreras entre
 * dos requests que intentan migrar a la vez).
 */
function agendaweb_ejecutar(mysqli $mysqli, string $sql, array $ignorar = []): void
{
    try {
        $mysqli->query($sql);
    } catch (mysqli_sql_exception $e) {
        if (!in_array($e->getCode(), $ignorar, true)) {
            throw $e;
        }
    }
}

function asegurarEsquemaP8(mysqli $mysqli): void
{
    // Foto del esquema en una sola consulta.
    $estado = $mysqli->query(
        "SELECT
            (SELECT COUNT(*) FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'eventos') AS hay_eventos,
            (SELECT COUNT(*) FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'usuarios') AS hay_usuarios,
            (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'eventos'
                AND COLUMN_NAME = 'usuario_id') AS columna_existe,
            (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'eventos'
                AND COLUMN_NAME = 'usuario_id'
                AND IS_NULLABLE = 'NO') AS columna_lista,
            (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
              WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = 'eventos'
                AND CONSTRAINT_NAME = 'fk_eventos_usuario') AS fk_lista"
    )->fetch_assoc();

    // Sin tabla eventos no hay nada que migrar (db.sql aún no corrió).
    if ((int) $estado['hay_eventos'] === 0) {
        return;
    }

    if ((int) $estado['hay_usuarios'] === 0) {
        $mysqli->query(
            'CREATE TABLE IF NOT EXISTS usuarios (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                nombre     VARCHAR(80)  NOT NULL,
                correo     VARCHAR(120) NOT NULL UNIQUE,
                clave_hash VARCHAR(255) NOT NULL,
                creado_en  DATETIME     DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    // Caso normal cuando ya se migró: una sola consulta y salimos.
    if (
        (int) $estado['columna_existe'] === 1
        && (int) $estado['columna_lista'] === 1
        && (int) $estado['fk_lista'] === 1
    ) {
        return;
    }

    // 1 · Columna usuario_id, con respaldo antes de tocar la tabla.
    if ((int) $estado['columna_existe'] === 0) {
        agendaweb_ejecutar(
            $mysqli,
            'CREATE TABLE IF NOT EXISTS eventos_respaldo_p8 AS SELECT * FROM eventos'
        );

        agendaweb_ejecutar(
            $mysqli,
            'ALTER TABLE eventos ADD COLUMN usuario_id INT NULL AFTER id',
            [1060] // Duplicate column name: otro request se adelantó
        );
    }

    // 2 · Llave foránea. Se puede poner ya: los NULL no la violan.
    if ((int) $estado['fk_lista'] === 0) {
        agendaweb_ejecutar(
            $mysqli,
            'ALTER TABLE eventos
               ADD CONSTRAINT fk_eventos_usuario
                   FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
                   ON DELETE CASCADE',
            [1826, 1022] // Nombre de FK duplicado: otro request se adelantó
        );
    }

    // 3 · Si todavía hay eventos de P7 sin dueño, no se puede poner
    //     NOT NULL. El primer usuario los reclama al entrar.
    $huerfanos = $mysqli->query(
        'SELECT COUNT(*) AS total FROM eventos WHERE usuario_id IS NULL'
    )->fetch_assoc();

    if ((int) $huerfanos['total'] > 0) {
        return;
    }

    // 4 · Columna obligatoria.
    if ((int) $estado['columna_lista'] === 0) {
        $mysqli->query('ALTER TABLE eventos MODIFY usuario_id INT NOT NULL');
    }
}

/**
 * Los eventos que venían de P7 (usuario_id NULL) pertenecen al primer
 * usuario de la app. Se los asignamos cuando ese usuario se registra
 * o inicia sesión, sin tocar phpMyAdmin.
 */
function reclamarEventosHuerfanos(mysqli $mysqli, int $usuarioId): void
{
    if ($usuarioId <= 0) {
        return;
    }

    $primero = $mysqli->query('SELECT MIN(id) AS id FROM usuarios')->fetch_assoc();

    if ((int) ($primero['id'] ?? 0) !== $usuarioId) {
        return; // no es el primer usuario: no reclama nada
    }

    $stmt = $mysqli->prepare(
        'UPDATE eventos SET usuario_id = ? WHERE usuario_id IS NULL'
    );
    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    $stmt->close();
}
