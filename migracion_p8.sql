-- ---------------------------------------------------------------
-- P8 · Usuarios y dueños de los eventos
--
-- La app aplica esta migración AUTOMÁTICAMENTE en el primer request
-- (partes/esquema.php). Este archivo queda como documentación y como
-- vía manual, y es seguro ejecutarlo aunque la app ya haya migrado.
--
-- Local (Workbench): selecciona el esquema `agenda` antes de ejecutar.
-- DomCloud (phpMyAdmin): selecciona tu base, normalmente USUARIO_agenda.
-- No se usa USE a propósito: el nombre de la base cambia entre local
-- y producción.
--
-- Si lo ejecutas a mano, hazlo DESPUÉS de registrarte y cambia el 1
-- del UPDATE por tu id de usuarios. En el flujo automático no hace
-- falta tocar nada.
-- ---------------------------------------------------------------

-- 1 · Respaldo por si algo sale mal. IF NOT EXISTS: si ya lo
--     habías creado en un intento anterior, no se pisa.
CREATE TABLE IF NOT EXISTS eventos_respaldo_p8 AS SELECT * FROM eventos;

-- 2 · Cuentas. UNIQUE impide dos cuentas con el mismo correo.
--     clave_hash mide 255 porque los hashes son largos y pueden
--     cambiar de tamaño en versiones futuras de PHP.
CREATE TABLE IF NOT EXISTS usuarios (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(80)  NOT NULL,
    correo     VARCHAR(120) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    creado_en  DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3 · Columna nueva: primero opcional. Solo se añade si falta,
--     para poder volver a ejecutar el archivo sin error.
SET @hay_columna := (
    SELECT COUNT(*)
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'eventos'
       AND COLUMN_NAME = 'usuario_id'
);

SET @sql := IF(
    @hay_columna = 0,
    'ALTER TABLE eventos ADD COLUMN usuario_id INT NULL AFTER id',
    'SELECT ''usuario_id ya existe'' AS aviso'
);

PREPARE migrar FROM @sql;
EXECUTE migrar;
DEALLOCATE PREPARE migrar;

-- 4 · ...y llenarla. Cambia 1 por TU id de usuarios.
--     El IS NULL evita pisar eventos que ya tienen dueño.
UPDATE eventos
   SET usuario_id = 1
 WHERE id > 0
   AND usuario_id IS NULL;

-- 5 · ...y volverla obligatoria con llave foránea.
--     CASCADE: si se borra el usuario, sus eventos ya no le sirven
--     a nadie y se borran con él. Solo se añade si falta.
SET @hay_fk := (
    SELECT COUNT(*)
      FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME = 'eventos'
       AND CONSTRAINT_NAME = 'fk_eventos_usuario'
);

SET @sql := IF(
    @hay_fk = 0,
    'ALTER TABLE eventos
        MODIFY usuario_id INT NOT NULL,
        ADD CONSTRAINT fk_eventos_usuario
            FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
            ON DELETE CASCADE',
    'SELECT ''fk_eventos_usuario ya existe'' AS aviso'
);

PREPARE migrar FROM @sql;
EXECUTE migrar;
DEALLOCATE PREPARE migrar;

DESCRIBE eventos;
