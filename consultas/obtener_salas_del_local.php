<?php

function obtenerSalasDelLocal(PDO $conexion): array
{
    return $conexion->query(
        'SELECT s.id_sala, s.nombre, s.id_zona, s.activo, z.nombre AS zona,
                (SELECT COUNT(*) FROM modulos AS m WHERE m.id_sala = s.id_sala) AS modulos
         FROM salas AS s
         INNER JOIN zonas AS z ON z.id_zona = s.id_zona
         ORDER BY s.activo DESC, z.nombre, s.nombre'
    )->fetchAll();
}
