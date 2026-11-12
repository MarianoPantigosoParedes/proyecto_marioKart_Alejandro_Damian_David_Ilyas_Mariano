<?php

require_once __DIR__ . '/sesion.php';

function usuarioSesionActivo(PDO $conexion): bool
{
    if (!usuarioTieneSesion()) {
        return false;
    }

    $consulta = $conexion->prepare('SELECT activo FROM gamemasters WHERE id_gamemaster = ?');
    $consulta->execute([$_SESSION['usuario']['id_gamemaster']]);

    return (int) $consulta->fetchColumn() === 1;
}