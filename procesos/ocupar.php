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

$idModulo = filter_var($_POST['id_modulo'] ?? null, FILTER_VALIDATE_INT);
$numJugadores = filter_var($_POST['num_jugadores'] ?? null, FILTER_VALIDATE_INT);
if ($idModulo === false || $idModulo === null || $idModulo < 1 || $numJugadores === false || $numJugadores === null) {
    header('Location: ../index/panel_gamemaster.php?resultado=capacidad');
    exit;
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/usuario_activo.php';

if (!usuarioSesionActivo($conexion)) {
    cerrarSesionPorCuentaInactiva();
}

try {
    $conexion->beginTransaction();

    $consultaGamemaster = $conexion->prepare(
        'SELECT activo FROM gamemasters WHERE id_gamemaster = :id_gamemaster FOR UPDATE'
    );
    $consultaGamemaster->execute(['id_gamemaster' => (int) $_SESSION['usuario']['id_gamemaster']]);
    if ((int) $consultaGamemaster->fetchColumn() !== 1) {
        $conexion->rollBack();
        cerrarSesionPorCuentaInactiva();
    }

    $consultaModulo = $conexion->prepare(
        'SELECT m.id_modulo, m.codigo, m.id_sala, s.nombre AS nombre_sala,
                s.id_zona, z.nombre AS nombre_zona,
                tm.id_tipo_modulo, tm.nombre AS tipo_modulo, tm.capacidad
         FROM modulos AS m
         INNER JOIN salas AS s ON s.id_sala = m.id_sala AND s.activo = 1
         INNER JOIN zonas AS z ON z.id_zona = s.id_zona AND z.activo = 1
         INNER JOIN tipos_modulo AS tm ON tm.id_tipo_modulo = m.id_tipo_modulo AND tm.activo = 1
         WHERE m.id_modulo = :id_modulo AND m.activo = 1
         FOR UPDATE'
    );
    $consultaModulo->execute(['id_modulo' => $idModulo]);
    $modulo = $consultaModulo->fetch();

    if (!$modulo) {
        $conexion->rollBack();
        header('Location: ../index/panel_gamemaster.php?resultado=ocupacion_no_encontrada');
        exit;
    }

    if ($numJugadores < 1 || $numJugadores > (int) $modulo['capacidad']) {
        $conexion->rollBack();
        header('Location: ../index/panel_gamemaster.php?resultado=capacidad');
        exit;
    }

    $consultaOcupacion = $conexion->prepare(
        'SELECT id_ocupacion
         FROM ocupaciones
         WHERE id_modulo = :id_modulo AND fecha_fin IS NULL
         LIMIT 1
         FOR UPDATE'
    );
    $consultaOcupacion->execute(['id_modulo' => $idModulo]);

    if ($consultaOcupacion->fetch()) {
        $conexion->rollBack();
        header('Location: ../index/panel_gamemaster.php?resultado=modulo_ocupado');
        exit;
    }

    $insertar = $conexion->prepare(
        'INSERT INTO ocupaciones (
            id_modulo, id_gamemaster, nombre_gamemaster_snapshot,
            apellido_gamemaster_snapshot, num_jugadores, fecha_inicio,
            id_zona_snapshot, id_sala_snapshot, nombre_zona_snapshot,
            nombre_sala_snapshot, codigo_modulo_snapshot,
            tipo_modulo_snapshot, capacidad_modulo_snapshot
         )
         VALUES (
            :id_modulo, :id_gamemaster, :nombre_gamemaster,
            :apellido_gamemaster, :num_jugadores, NOW(),
            :id_zona, :id_sala, :nombre_zona, :nombre_sala,
            :codigo_modulo, :tipo_modulo, :capacidad
         )'
    );
    $insertar->execute([
        'id_modulo' => $idModulo,
        'id_gamemaster' => (int) $_SESSION['usuario']['id_gamemaster'],
        'nombre_gamemaster' => $_SESSION['usuario']['nombre'],
        'apellido_gamemaster' => $_SESSION['usuario']['apellido'],
        'num_jugadores' => $numJugadores,
        'id_zona' => (int) $modulo['id_zona'],
        'id_sala' => (int) $modulo['id_sala'],
        'nombre_zona' => $modulo['nombre_zona'],
        'nombre_sala' => $modulo['nombre_sala'],
        'codigo_modulo' => $modulo['codigo'],
        'tipo_modulo' => $modulo['tipo_modulo'],
        'capacidad' => (int) $modulo['capacidad'],
    ]);

    $conexion->commit();
    header('Location: ../index/panel_gamemaster.php?resultado=ocupacion_ok');
    exit;
} catch (PDOException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error al ocupar un módulo: ' . $error->getMessage());
    header('Location: ../index/panel_gamemaster.php?resultado=error_bd');
    exit;
}
