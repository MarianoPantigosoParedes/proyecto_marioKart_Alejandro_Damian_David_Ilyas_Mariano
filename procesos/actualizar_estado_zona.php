<?php

require_once __DIR__ . '/../config/validar_solicitud_estructura_local.php';

$solicitud = validarSolicitudEstructuraLocal('zona');
$conexion = $solicitud['conexion'];
$id = $solicitud['id'];
$volver = '../index/gestion_estructura_local.php';

try {
    $conexion->beginTransaction();

    $consulta = $conexion->prepare('SELECT activo FROM zonas WHERE id_zona = :id FOR UPDATE');
    $consulta->execute(['id' => $id]);
    $zona = $consulta->fetch();
    if (!$zona) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=no_encontrado');
        exit;
    }

    if ((int) $zona['activo'] === 0) {
        $actualizar = $conexion->prepare('UPDATE zonas SET activo = 1 WHERE id_zona = :id');
        $actualizar->execute(['id' => $id]);
    } else {
        $ocupacionAbierta = $conexion->prepare(
            'SELECT o.id_ocupacion
             FROM ocupaciones AS o
             INNER JOIN modulos AS m ON m.id_modulo = o.id_modulo
             INNER JOIN salas AS s ON s.id_sala = m.id_sala
             WHERE s.id_zona = :id_zona AND o.fecha_fin IS NULL
             LIMIT 1
             FOR UPDATE'
        );
        $ocupacionAbierta->execute(['id_zona' => $id]);
        if ($ocupacionAbierta->fetch()) {
            $conexion->rollBack();
            header('Location: ' . $volver . '?resultado=tiene_ocupaciones_abiertas');
            exit;
        }

        $actualizar = $conexion->prepare('UPDATE zonas SET activo = 0 WHERE id_zona = :id_zona');
        $actualizar->execute(['id_zona' => $id]);
        $actualizar = $conexion->prepare('UPDATE salas SET activo = 0 WHERE id_zona = :id_zona');
        $actualizar->execute(['id_zona' => $id]);
        $actualizar = $conexion->prepare(
            'UPDATE modulos AS m
             INNER JOIN salas AS s ON s.id_sala = m.id_sala
             SET m.activo = 0 WHERE s.id_zona = :id_zona'
        );
        $actualizar->execute(['id_zona' => $id]);
    }

    $conexion->commit();
    header('Location: ' . $volver . '?resultado=estado_actualizado');
    exit;
} catch (PDOException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error al actualizar el estado de una zona: ' . $error->getMessage());
    header('Location: ' . $volver . '?resultado=error_bd');
    exit;
}
