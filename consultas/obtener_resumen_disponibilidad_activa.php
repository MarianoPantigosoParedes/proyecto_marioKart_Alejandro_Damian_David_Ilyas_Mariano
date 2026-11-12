<?php

function obtenerResumenDisponibilidadActiva(PDO $conexion): array
{
    return $conexion->query(
        'SELECT COUNT(*) AS modulos_totales,
                SUM(CASE WHEN abiertas.id_ocupacion IS NULL THEN 1 ELSE 0 END) AS modulos_libres,
                SUM(CASE WHEN abiertas.id_ocupacion IS NOT NULL THEN 1 ELSE 0 END) AS modulos_ocupados
         FROM modulos AS m
         INNER JOIN salas AS s ON s.id_sala = m.id_sala AND s.activo = 1
         INNER JOIN zonas AS z ON z.id_zona = s.id_zona AND z.activo = 1
         INNER JOIN tipos_modulo AS tm ON tm.id_tipo_modulo = m.id_tipo_modulo AND tm.activo = 1
         LEFT JOIN (
             SELECT id_modulo, MIN(id_ocupacion) AS id_ocupacion
             FROM ocupaciones
             WHERE fecha_fin IS NULL
             GROUP BY id_modulo
         ) AS abiertas ON abiertas.id_modulo = m.id_modulo
         WHERE m.activo = 1'
    )->fetch();
}
