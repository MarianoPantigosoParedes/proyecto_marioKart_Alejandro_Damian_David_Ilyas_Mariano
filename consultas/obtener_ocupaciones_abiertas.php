<?php

function obtenerOcupacionesAbiertas(PDO $conexion): array
{
    return $conexion->query(
        'SELECT o.nombre_zona_snapshot AS zona, o.nombre_sala_snapshot AS sala,
                o.codigo_modulo_snapshot AS modulo, o.num_jugadores, o.fecha_inicio,
                COALESCE(o.nombre_gamemaster_snapshot, g.nombre) AS nombre,
                COALESCE(o.apellido_gamemaster_snapshot, g.apellido) AS apellido
         FROM ocupaciones AS o
         INNER JOIN modulos AS m ON m.id_modulo = o.id_modulo
         INNER JOIN salas AS s ON s.id_sala = m.id_sala
         INNER JOIN gamemasters AS g ON g.id_gamemaster = o.id_gamemaster
         WHERE o.fecha_fin IS NULL
         ORDER BY o.fecha_inicio'
    )->fetchAll();
}
