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
    header('Location: ../index/gestion_gamemasters.php' );
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    header('Location: ../index/gestion_gamemasters.php?resultado=datos_invalidos' );
    exit;
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/usuario_activo.php';

if (!usuarioSesionActivo($conexion)) {
    cerrarSesionPorCuentaInactiva();
}

$volver = '../index/gestion_gamemasters.php';

try {
    $conexion->beginTransaction();

    $consulta = $conexion->prepare(
        "SELECT g.activo
         FROM gamemasters AS g
         INNER JOIN roles AS r ON r.id_rol = g.id_rol AND r.nombre = 'gamemaster'
         WHERE g.id_gamemaster = :id
         FOR UPDATE"
    );
    $consulta->execute(['id' => $id]);
    $cuenta = $consulta->fetch();
    if (!$cuenta) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=no_encontrado' );
        exit;
    }

    if ((int) $cuenta['activo'] === 0) {
        $activar = $conexion->prepare(
            "UPDATE gamemasters AS g
             INNER JOIN roles AS r ON r.id_rol = g.id_rol AND r.nombre = 'gamemaster'
             SET g.activo = 1
             WHERE g.id_gamemaster = :id"
        );
        $activar->execute(['id' => $id]);
        $resultado = 'estado_actualizado';
    } else {
        $consultaAbiertas = $conexion->prepare(
            'SELECT id_ocupacion FROM ocupaciones
             WHERE id_gamemaster = :id AND fecha_fin IS NULL
             LIMIT 1 FOR UPDATE'
        );
        $consultaAbiertas->execute(['id' => $id]);
        if ($consultaAbiertas->fetch()) {
            $conexion->rollBack();
            header('Location: ' . $volver . '?resultado=ocupaciones_abiertas' );
            exit;
        }

        $desactivar = $conexion->prepare(
            'UPDATE gamemasters SET activo = 0 WHERE id_gamemaster = :id'
        );
        $desactivar->execute(['id' => $id]);
        $resultado = 'cuenta_desactivada';
    }

    $conexion->commit();
    header('Location: ' . $volver . '?resultado=' . $resultado );
    exit;
} catch (PDOException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error al eliminar o desactivar una cuenta gamemaster: ' . $error->getMessage());
    header('Location: ' . $volver . '?resultado=error_bd' );
    exit;
}
