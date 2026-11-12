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

$volver = '../index/gestion_gamemasters.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $volver);
    exit;
}

$idGamemaster = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($idGamemaster === false || $idGamemaster === null || $idGamemaster < 1) {
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
    $consultaCuenta = $conexion->prepare(
        "SELECT g.activo
         FROM gamemasters AS g
         INNER JOIN roles AS r ON r.id_rol = g.id_rol AND r.nombre = 'gamemaster'
         WHERE g.id_gamemaster = :id
         FOR UPDATE"
    );
    $consultaCuenta->execute(['id' => $idGamemaster]);
    $gamemaster = $consultaCuenta->fetch();
    if (!$gamemaster) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=no_encontrado');
        exit;
    }
    if ((int) $gamemaster['activo'] !== 0) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=gamemaster_activo');
        exit;
    }

    $consultaOcupacionesAbiertas = $conexion->prepare(
        'SELECT id_ocupacion
         FROM ocupaciones
         WHERE id_gamemaster = :id AND fecha_fin IS NULL
         LIMIT 1
         FOR UPDATE'
    );
    $consultaOcupacionesAbiertas->execute(['id' => $idGamemaster]);
    if ($consultaOcupacionesAbiertas->fetch()) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=gamemaster_ocupaciones_abiertas');
        exit;
    }

    $eliminarOcupaciones = $conexion->prepare(
        'DELETE FROM ocupaciones WHERE id_gamemaster = :id'
    );
    $eliminarOcupaciones->execute(['id' => $idGamemaster]);
    $eliminarGamemaster = $conexion->prepare(
        "DELETE g FROM gamemasters AS g
         INNER JOIN roles AS r ON r.id_rol = g.id_rol AND r.nombre = 'gamemaster'
         WHERE g.id_gamemaster = :id AND g.activo = 0"
    );
    $eliminarGamemaster->execute(['id' => $idGamemaster]);
    if ($eliminarGamemaster->rowCount() !== 1) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=error_bd');
        exit;
    }

    $conexion->commit();
    header('Location: ' . $volver . '?resultado=gamemaster_eliminado');
    exit;
} catch (PDOException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error al eliminar definitivamente un gamemaster: ' . $error->getMessage());
    header('Location: ' . $volver . '?resultado=error_bd');
    exit;
}
