<?php

function obtenerSalasParaHistorial(PDO $conexion): array
{
    return $conexion->query(
        'SELECT s.id_sala, s.nombre, s.id_zona, z.nombre AS zona
         FROM salas AS s
         INNER JOIN zonas AS z ON z.id_zona = s.id_zona
         ORDER BY z.nombre, s.nombre'
    )->fetchAll();
}
