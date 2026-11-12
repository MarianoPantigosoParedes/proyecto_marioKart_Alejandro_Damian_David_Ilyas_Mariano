<?php

function contarGamemastersActivos(PDO $conexion): int
{
    return (int) $conexion->query(
        'SELECT COUNT(*) FROM gamemasters WHERE activo = 1'
    )->fetchColumn();
}
