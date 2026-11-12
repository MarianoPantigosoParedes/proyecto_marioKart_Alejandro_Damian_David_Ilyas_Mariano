<?php

require_once __DIR__ . '/../config/sesion.php';

iniciarSesion();

if (
    !isset($_SESSION['usuario']['id_gamemaster'], $_SESSION['usuario']['rol'])
    || $_SESSION['usuario']['rol'] !== 'admin'
) {
    header('Location: ../index/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index/gestion_gamemasters.php');

    exit;
}

$id = filter_var($_POST['id'] ?? '0', FILTER_VALIDATE_INT);
$usuarioRecibido = $_POST['username'] ?? '';
$nombreRecibido = $_POST['nombre'] ?? '';
$apellidoRecibido = $_POST['apellido'] ?? '';
$contrasenaRecibida = $_POST['password'] ?? '';
$usuario = is_string($usuarioRecibido) ? trim($usuarioRecibido) : '';
$nombre = is_string($nombreRecibido) ? trim($nombreRecibido) : '';
$apellido = is_string($apellidoRecibido) ? trim($apellidoRecibido) : '';
$contrasena = is_string($contrasenaRecibida) ? $contrasenaRecibida : '';
$volver = '../index/gestion_gamemasters.php';

$nombreValido = $nombre !== ''
    && preg_match('//u', $nombre) === 1
    && mb_strlen($nombre, 'UTF-8') <= 50
    && preg_match('/[\x00-\x1F\x7F]/u', $nombre) === 0;
$apellidoValido = $apellido !== ''
    && preg_match('//u', $apellido) === 1
    && mb_strlen($apellido, 'UTF-8') <= 80
    && preg_match('/[\x00-\x1F\x7F]/u', $apellido) === 0;
$datosValidos = preg_match('/^[A-Za-z0-9._-]{3,30}$/', $usuario) === 1
    && $nombreValido
    && $apellidoValido;
$contrasenaValida = $contrasena === ''
    ? $id > 0
    : strlen($contrasena) >= 12 && strlen($contrasena) <= 72;

if (
    $id === false
    || $id < 0
    || !$datosValidos
    || !is_string($contrasenaRecibida)
    || !$contrasenaValida
) {
    header('Location: ' . $volver . '?resultado=datos_invalidos');
    exit;
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/usuario_activo.php';

if (!usuarioSesionActivo($conexion)) {
    cerrarSesionPorCuentaInactiva();
}

try {
    $conexion->beginTransaction();

    $consultaRol = $conexion->prepare("SELECT id_rol FROM roles WHERE nombre = 'gamemaster'");
    $consultaRol->execute();
    $idRol = $consultaRol->fetchColumn();
    if ($idRol === false) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=rol_no_disponible');
        exit;
    }

    if ($id > 0) {
        $consultaCuenta = $conexion->prepare(
            'SELECT id_gamemaster FROM gamemasters
             WHERE id_gamemaster = :id AND id_rol = :id_rol
             FOR UPDATE'
        );
        $consultaCuenta->execute(['id' => $id, 'id_rol' => $idRol]);
        if (!$consultaCuenta->fetch()) {
            $conexion->rollBack();
            header('Location: ' . $volver . '?resultado=no_encontrado');
            exit;
        }

        $sql = 'UPDATE gamemasters
                SET username = :username, nombre = :nombre, apellido = :apellido';
        $parametros = [
            'username' => $usuario,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'id' => $id,
            'id_rol' => $idRol,
        ];
        if ($contrasena !== '') {
            $sql .= ', password = :password';
            $parametros['password'] = password_hash($contrasena, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE id_gamemaster = :id AND id_rol = :id_rol';
        $actualizar = $conexion->prepare($sql);
        $actualizar->execute($parametros);
    } else {
        $insertar = $conexion->prepare(
            'INSERT INTO gamemasters (username, password, nombre, apellido, id_rol)
             VALUES (:username, :password, :nombre, :apellido, :id_rol)'
        );
        $insertar->execute([
            'username' => $usuario,
            'password' => password_hash($contrasena, PASSWORD_DEFAULT),
            'nombre' => $nombre,
            'apellido' => $apellido,
            'id_rol' => $idRol,
        ]);
    }

    $conexion->commit();
    header('Location: ' . $volver . '?resultado=guardado');
    exit;
} catch (PDOException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error al guardar una cuenta gamemaster: ' . $error->getMessage());
    $resultado = $error->getCode() === '23000' ? 'duplicado' : 'error_bd';
    header('Location: ' . $volver . '?resultado=' . $resultado);
    exit;
}
