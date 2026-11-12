<?php

function obtenerZonasDelLocal(PDO $conexion): array
{
    return $conexion->query(
        'SELECT id_zona, nombre, activo
         FROM zonas
         ORDER BY activo DESC, nombre'
    )->fetchAll();
}
