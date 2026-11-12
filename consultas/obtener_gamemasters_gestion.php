<?php

function obtenerGamemastersGestion(PDO $conexion, string $busqueda, string $estado): array
{
    $condiciones = [];
    $parametros = [];
    if ($busqueda !== '') {
        $condiciones[] = '(g.username LIKE :buscar_usuario OR g.nombre LIKE :buscar_nombre OR g.apellido LIKE :buscar_apellido)';
        $busquedaSQL = '%' . $busqueda . '%';
        $parametros['buscar_usuario'] = $busquedaSQL;
        $parametros['buscar_nombre'] = $busquedaSQL;
        $parametros['buscar_apellido'] = $busquedaSQL;
    }
    if ($estado === 'activos') {
        $condiciones[] = 'g.activo = 1';
    } elseif ($estado === 'inactivos') {
        $condiciones[] = 'g.activo = 0';
    }
    $where = $condiciones ? ' AND ' . implode(' AND ', $condiciones) : '';

    $consulta = $conexion->prepare(
        "SELECT g.id_gamemaster, g.username, g.nombre, g.apellido, g.activo,
                COUNT(o.id_ocupacion) AS ocupaciones,
                SUM(CASE
                    WHEN o.id_ocupacion IS NOT NULL AND o.fecha_fin IS NULL THEN 1
                    ELSE 0
                END) AS abiertas
         FROM gamemasters AS g
         INNER JOIN roles AS r ON r.id_rol = g.id_rol AND r.nombre = 'gamemaster'
         LEFT JOIN ocupaciones AS o ON o.id_gamemaster = g.id_gamemaster
         WHERE 1 = 1 {$where}
         GROUP BY g.id_gamemaster, g.username, g.nombre, g.apellido, g.activo
         ORDER BY g.activo DESC, g.username"
    );
    $consulta->execute($parametros);
    return $consulta->fetchAll();
}
