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

$idZona = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($idZona === false || $idZona === null || $idZona < 1) {
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
    $consulta = $conexion->prepare('SELECT activo FROM zonas WHERE id_zona = :id FOR UPDATE');
    $consulta->execute(['id' => $idZona]);
    $zona = $consulta->fetch();
    if (!$zona) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=no_encontrado');
        exit;
    }
    if ((int) $zona['activo'] !== 0) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=recurso_no_archivado');
        exit;
    }

    $bloquearSalas = $conexion->prepare(
        'SELECT id_sala FROM salas WHERE id_zona = :id_zona FOR UPDATE'
    );
    $bloquearSalas->execute(['id_zona' => $idZona]);
    $bloquearSalas->fetchAll();
    $bloquearModulos = $conexion->prepare(
        'SELECT m.id_modulo
         FROM modulos AS m
         INNER JOIN salas AS s ON s.id_sala = m.id_sala
         WHERE s.id_zona = :id_zona
         FOR UPDATE'
    );
    $bloquearModulos->execute(['id_zona' => $idZona]);
    $bloquearModulos->fetchAll();

    $eliminarOcupaciones = $conexion->prepare(
        'DELETE o FROM ocupaciones AS o
         INNER JOIN modulos AS m ON m.id_modulo = o.id_modulo
         INNER JOIN salas AS s ON s.id_sala = m.id_sala
         WHERE s.id_zona = :id_zona'
    );
    $eliminarOcupaciones->execute(['id_zona' => $idZona]);
    $eliminarPuestos = $conexion->prepare(
        'DELETE p FROM puestos_juego AS p
         INNER JOIN modulos AS m ON m.id_modulo = p.id_modulo
         INNER JOIN salas AS s ON s.id_sala = m.id_sala
         WHERE s.id_zona = :id_zona'
    );
    $eliminarPuestos->execute(['id_zona' => $idZona]);
    $eliminarModulos = $conexion->prepare(
        'DELETE m FROM modulos AS m
         INNER JOIN salas AS s ON s.id_sala = m.id_sala
         WHERE s.id_zona = :id_zona'
    );
    $eliminarModulos->execute(['id_zona' => $idZona]);
    $eliminarSalas = $conexion->prepare('DELETE FROM salas WHERE id_zona = :id_zona');
    $eliminarSalas->execute(['id_zona' => $idZona]);
    $eliminarZona = $conexion->prepare('DELETE FROM zonas WHERE id_zona = :id_zona');
    $eliminarZona->execute(['id_zona' => $idZona]);
    $conexion->commit();

    header('Location: ' . $volver . '?resultado=zona_eliminada');
    exit;
} catch (PDOException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error al eliminar definitivamente una zona: ' . $error->getMessage());
    header('Location: ' . $volver . '?resultado=error_bd');
    exit;
}
