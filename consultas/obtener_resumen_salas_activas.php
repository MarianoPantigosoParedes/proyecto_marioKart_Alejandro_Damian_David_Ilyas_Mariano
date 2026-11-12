<?php

function obtenerResumenSalasActivas(PDO $conexion): array
{
    return $conexion->query(
        'SELECT s.id_sala, s.id_zona, s.nombre,
                z.nombre AS nombre_zona, z.nombre AS zona,
                COUNT(m.id_modulo) AS modulos_totales,
                COALESCE(SUM(tm.capacidad), 0) AS capacidad_total,
                SUM(CASE
                    WHEN m.id_modulo IS NOT NULL AND abiertas.id_ocupacion IS NULL THEN 1
                    ELSE 0
                END) AS modulos_libres,
                SUM(CASE
                    WHEN m.id_modulo IS NOT NULL AND abiertas.id_ocupacion IS NOT NULL THEN 1
                    ELSE 0
                END) AS modulos_ocupados
         FROM salas AS s
         INNER JOIN zonas AS z ON z.id_zona = s.id_zona AND z.activo = 1
         LEFT JOIN modulos AS m ON m.id_sala = s.id_sala AND m.activo = 1
         LEFT JOIN tipos_modulo AS tm ON tm.id_tipo_modulo = m.id_tipo_modulo AND tm.activo = 1
         LEFT JOIN (
             SELECT id_modulo, MIN(id_ocupacion) AS id_ocupacion
             FROM ocupaciones
             WHERE fecha_fin IS NULL
             GROUP BY id_modulo
         ) AS abiertas ON abiertas.id_modulo = m.id_modulo
         WHERE s.activo = 1
         GROUP BY s.id_sala, s.id_zona, s.nombre, z.nombre
         ORDER BY s.id_sala'
    )->fetchAll();
}
