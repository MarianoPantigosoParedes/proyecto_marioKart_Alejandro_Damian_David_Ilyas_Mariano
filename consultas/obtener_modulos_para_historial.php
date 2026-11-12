<?php

function obtenerModulosParaHistorial(PDO $conexion): array
{
    return $conexion->query(
        'SELECT m.id_modulo, m.codigo, m.id_sala, s.id_zona, s.nombre AS sala
         FROM modulos AS m
         INNER JOIN salas AS s ON s.id_sala = m.id_sala
         ORDER BY s.id_sala, m.codigo'
    )->fetchAll();
}
