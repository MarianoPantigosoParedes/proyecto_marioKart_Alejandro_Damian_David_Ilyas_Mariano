-- Migraciones para instalaciones existentes.
-- Haz una copia de seguridad antes de ejecutar cambios.
--
-- Ejecuta SOLO una de las secciones, según el esquema actual:
-- 1. "Esquema antiguo": la base todavía tiene tipos_sala.
-- 2. "Esquema con zonas": la base ya tiene zonas y snapshots de la estructura,
--    pero todavía no tiene snapshots del nombre del gamemaster.
--
-- No ejecutes ninguna sección en una instalación nueva: importa
-- db_pj5_gamingCenter.sql, que ya incluye el esquema actualizado.

-- ============================================================
-- Esquema antiguo: todavía existe la tabla tipos_sala
-- ============================================================

CREATE TABLE zonas (
    id_zona INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_zona),
    UNIQUE KEY uq_zonas_nombre (nombre)
) ENGINE=InnoDB;

INSERT INTO zonas (id_zona, nombre)
SELECT id_tipo_sala, nombre
FROM tipos_sala;

ALTER TABLE salas
    ADD COLUMN id_zona INT UNSIGNED NULL AFTER nombre,
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;

UPDATE salas
SET id_zona = id_tipo_sala;

ALTER TABLE salas
    MODIFY COLUMN id_zona INT UNSIGNED NOT NULL,
    ADD KEY idx_salas_zona_activo (id_zona, activo),
    ADD CONSTRAINT fk_salas_zona FOREIGN KEY (id_zona) REFERENCES zonas (id_zona)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    DROP FOREIGN KEY fk_salas_tipo,
    DROP COLUMN id_tipo_sala;

DROP TABLE tipos_sala;

ALTER TABLE tipos_modulo
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE modulos
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE puestos_juego
    MODIFY COLUMN codigo VARCHAR(20) NOT NULL;

ALTER TABLE ocupaciones
    ADD COLUMN nombre_gamemaster_snapshot VARCHAR(50) NULL,
    ADD COLUMN apellido_gamemaster_snapshot VARCHAR(80) NULL,
    ADD COLUMN id_zona_snapshot INT UNSIGNED NULL,
    ADD COLUMN id_sala_snapshot INT UNSIGNED NULL,
    ADD COLUMN nombre_zona_snapshot VARCHAR(50) NULL,
    ADD COLUMN nombre_sala_snapshot VARCHAR(50) NULL,
    ADD COLUMN codigo_modulo_snapshot VARCHAR(10) NULL,
    ADD COLUMN tipo_modulo_snapshot VARCHAR(40) NULL,
    ADD COLUMN capacidad_modulo_snapshot TINYINT UNSIGNED NULL,
    ADD KEY idx_ocupaciones_zona_hist (id_zona_snapshot, fecha_inicio),
    ADD KEY idx_ocupaciones_sala_hist (id_sala_snapshot, fecha_inicio);

UPDATE ocupaciones AS o
INNER JOIN modulos AS m ON m.id_modulo = o.id_modulo
INNER JOIN salas AS s ON s.id_sala = m.id_sala
INNER JOIN zonas AS z ON z.id_zona = s.id_zona
INNER JOIN tipos_modulo AS tm ON tm.id_tipo_modulo = m.id_tipo_modulo
SET o.nombre_zona_snapshot = z.nombre,
    o.id_zona_snapshot = z.id_zona,
    o.id_sala_snapshot = s.id_sala,
    o.nombre_sala_snapshot = s.nombre,
    o.codigo_modulo_snapshot = m.codigo,
    o.tipo_modulo_snapshot = tm.nombre,
    o.capacidad_modulo_snapshot = tm.capacidad;

UPDATE ocupaciones AS o
INNER JOIN gamemasters AS g ON g.id_gamemaster = o.id_gamemaster
SET o.nombre_gamemaster_snapshot = g.nombre,
    o.apellido_gamemaster_snapshot = g.apellido;

-- ============================================================
-- Esquema con zonas: ejecutar solo en bases ya migradas a la nueva estructura
-- ============================================================
-- Ejecuta únicamente este bloque si ya tienes zonas y snapshots de zona y
-- capacidad del módulo, pero faltan los snapshots de nombre y apellido del gamemaster.
-- No lo ejecutes después de la sección "Esquema antiguo".

ALTER TABLE ocupaciones
    ADD COLUMN nombre_gamemaster_snapshot VARCHAR(50) NULL AFTER id_gamemaster,
    ADD COLUMN apellido_gamemaster_snapshot VARCHAR(80) NULL AFTER nombre_gamemaster_snapshot;

UPDATE ocupaciones AS o
INNER JOIN gamemasters AS g ON g.id_gamemaster = o.id_gamemaster
SET o.nombre_gamemaster_snapshot = g.nombre,
    o.apellido_gamemaster_snapshot = g.apellido;
