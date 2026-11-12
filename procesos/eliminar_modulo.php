<?php

require_once __DIR__ . '/../config/sesion.php';

iniciarSesion();

if (
    !isset($_SESSION['usuario']['id_gamemaster'], $_SESSION['usuario']['rol'])
    || !in_array($_SESSION['usuario']['rol'], ['admin', 'gamemaster'], true)
) {
    header('Location: ../index/login.php');
    exit;
}

$volver = '../index/gestion_estructura_local.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $volver);
    exit;
}

$idModulo = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($idModulo === false || $idModulo === null || $idModulo < 1) {
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
    $consulta = $conexion->prepare('SELECT activo FROM modulos WHERE id_modulo = :id FOR UPDATE');
    $consulta->execute(['id' => $idModulo]);
    $modulo = $consulta->fetch();
    if (!$modulo) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=no_encontrado');
        exit;
    }
    if ((int) $modulo['activo'] !== 0) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=recurso_no_archivado');
        exit;
    }

    $eliminarOcupaciones = $conexion->prepare('DELETE FROM ocupaciones WHERE id_modulo = :id');
    $eliminarOcupaciones->execute(['id' => $idModulo]);
    $eliminarPuestos = $conexion->prepare('DELETE FROM puestos_juego WHERE id_modulo = :id');
    $eliminarPuestos->execute(['id' => $idModulo]);
    $eliminarModulo = $conexion->prepare('DELETE FROM modulos WHERE id_modulo = :id');
    $eliminarModulo->execute(['id' => $idModulo]);
    $conexion->commit();

    header('Location: ' . $volver . '?resultado=modulo_eliminado');
    exit;
} catch (PDOException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error al eliminar definitivamente un módulo: ' . $error->getMessage());
    header('Location: ' . $volver . '?resultado=error_bd');
    exit;
}
