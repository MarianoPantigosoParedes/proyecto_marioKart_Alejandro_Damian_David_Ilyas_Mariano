<?php

function obtenerTiposModuloDelLocal(PDO $conexion): array
{
    return $conexion->query(
        'SELECT tm.id_tipo_modulo, tm.nombre, tm.capacidad, tm.activo,
                (SELECT COUNT(*) FROM modulos AS m WHERE m.id_tipo_modulo = tm.id_tipo_modulo) AS modulos
         FROM tipos_modulo AS tm
         ORDER BY tm.activo DESC, tm.nombre'
    )->fetchAll();
}
