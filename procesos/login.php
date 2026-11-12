<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/sesion.php';

iniciarSesion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index/login.php');
    exit;
}



$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header('Location: ../index/login.php?error=credenciales');
    exit;
}

// 4. Longitud máxima
if (strlen($username) > 50 || strlen($password) > 100) {
    header('Location: ../index/login.php?error=credenciales');
    exit;
}

$consulta = $conexion->prepare('SELECT g.id_gamemaster, g.username, g.password, g.nombre, g.apellido, r.nombre AS rol FROM gamemasters AS g INNER JOIN roles AS r ON r.id_rol = g.id_rol WHERE g.username = :username AND g.activo = 1');
$consulta->execute(['username' => $username]);
$gamemaster = $consulta->fetch();

if (!$gamemaster || !password_verify($password, $gamemaster['password'])) {
    header('Location: ../index/login.php?error=credenciales');
    exit;
}

if ($gamemaster['rol'] === 'admin') {
    $ruta = 'administracion.php';
} elseif ($gamemaster['rol'] === 'gamemaster') {
    $ruta = 'panel_gamemaster.php';
} else {
    header('Location: ../index/login.php?error=rol');
    exit;
}

session_regenerate_id(true);
$_SESSION['usuario'] = [
    'id_gamemaster' => (int) $gamemaster['id_gamemaster'],
    'username' => $gamemaster['username'],
    'nombre' => $gamemaster['nombre'],
    'apellido' => $gamemaster['apellido'],
    'rol' => $gamemaster['rol'],
];

header('Location: ../index/' . $ruta);
exit;