<?php

require_once __DIR__ . '/../config/sesion.php';

iniciarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index/panel_gamemaster.php');
    exit;
}

cerrarSesion();

header('Location: ../index/login.php');
exit;