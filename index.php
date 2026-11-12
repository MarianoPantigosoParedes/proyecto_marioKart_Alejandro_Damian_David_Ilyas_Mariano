<?php

require_once __DIR__ . '/config/sesion.php';

iniciarSesion();

$rol = usuarioTieneSesion() ? $_SESSION['usuario']['rol'] : '';

if ($rol === 'admin') {
    header('Location: index/administracion.php');
    exit;
}

if ($rol === 'gamemaster') {
    header('Location: index/panel_gamemaster.php');
    exit;
}

header('Location: index/login.php');
exit;