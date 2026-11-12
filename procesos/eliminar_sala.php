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

$idSala = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($idSala === false || $idSala === null || $idSala < 1) {
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
    $consulta = $conexion->prepare('SELECT activo FROM salas WHERE id_sala = :id FOR UPDATE');
    $consulta->execute(['id' => $idSala]);
    $sala = $consulta->fetch();
    if (!$sala) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=no_encontrado');
        exit;
    }
    if ((int) $sala['activo'] !== 0) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=recurso_no_archivado');
        exit;
    }

    $bloquearModulos = $conexion->prepare(
        'SELECT id_modulo FROM modulos WHERE id_sala = :id_sala FOR UPDATE'
    );
    $bloquearModulos->execute(['id_sala' => $idSala]);
    $bloquearModulos->fetchAll();

    $eliminarOcupaciones = $conexion->prepare(
        'DELETE o FROM ocupaciones AS o
         INNER JOIN modulos AS m ON m.id_modulo = o.id_modulo
         WHERE m.id_sala = :id_sala'
    );
    $eliminarOcupaciones->execute(['id_sala' => $idSala]);
    $eliminarPuestos = $conexion->prepare(
        'DELETE p FROM puestos_juego AS p
         INNER JOIN modulos AS m ON m.id_modulo = p.id_modulo
         WHERE m.id_sala = :id_sala'
    );
    $eliminarPuestos->execute(['id_sala' => $idSala]);
    $eliminarModulos = $conexion->prepare('DELETE FROM modulos WHERE id_sala = :id_sala');
    $eliminarModulos->execute(['id_sala' => $idSala]);
    $eliminarSala = $conexion->prepare('DELETE FROM salas WHERE id_sala = :id_sala');
    $eliminarSala->execute(['id_sala' => $idSala]);
    $conexion->commit();

    header('Location: ' . $volver . '?resultado=sala_eliminada');
    exit;
} catch (PDOException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error al eliminar definitivamente una sala: ' . $error->getMessage());
    header('Location: ' . $volver . '?resultado=error_bd');
    exit;
}
