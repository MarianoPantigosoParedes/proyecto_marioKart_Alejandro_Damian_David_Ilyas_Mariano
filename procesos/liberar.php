<?php

require_once __DIR__ . '/../config/sesion.php';

iniciarSesion();

if (
    !isset($_SESSION['usuario']['id_gamemaster'], $_SESSION['usuario']['rol'])
    || $_SESSION['usuario']['rol'] !== 'gamemaster'
) {
    header('Location: ../index/login.php' );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index/panel_gamemaster.php');
    exit;
}

$idOcupacion = filter_var($_POST['id_ocupacion'] ?? null, FILTER_VALIDATE_INT);
if ($idOcupacion === false || $idOcupacion === null || $idOcupacion < 1) {
    header('Location: ../index/panel_gamemaster.php?resultado=ocupacion_cerrada');
    exit;
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/usuario_activo.php';

if (!usuarioSesionActivo($conexion)) {
    cerrarSesionPorCuentaInactiva();
}

$idGamemaster = (int) $_SESSION['usuario']['id_gamemaster'];

try {
    $conexion->beginTransaction();

    $consulta = $conexion->prepare(
        'SELECT id_ocupacion, id_gamemaster
         FROM ocupaciones
         WHERE id_ocupacion = :id_ocupacion AND fecha_fin IS NULL
         FOR UPDATE'
    );
    $consulta->execute(['id_ocupacion' => $idOcupacion]);
    $ocupacion = $consulta->fetch();

    if (!$ocupacion) {
        $conexion->rollBack();
        header('Location: ../index/panel_gamemaster.php?resultado=ocupacion_cerrada');
        exit;
    }

    if ((int) $ocupacion['id_gamemaster'] !== $idGamemaster) {
        $conexion->rollBack();
        header('Location: ../index/panel_gamemaster.php?resultado=no_es_responsable');
        exit;
    }

    $actualizar = $conexion->prepare(
        'UPDATE ocupaciones
         SET fecha_fin = NOW()
         WHERE id_ocupacion = :id_ocupacion
           AND id_gamemaster = :id_gamemaster
           AND fecha_fin IS NULL'
    );
    $actualizar->execute([
        'id_ocupacion' => $idOcupacion,
        'id_gamemaster' => $idGamemaster,
    ]);

    if ($actualizar->rowCount() !== 1) {
        $conexion->rollBack();
        header('Location: ../index/panel_gamemaster.php?resultado=ocupacion_cerrada');
        exit;
    }

    $conexion->commit();
    header('Location: ../index/panel_gamemaster.php?resultado=liberacion_ok');
    exit;
} catch (PDOException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error al liberar un módulo: ' . $error->getMessage());
    header('Location: ../index/panel_gamemaster.php?resultado=error_bd');
    exit;
}
