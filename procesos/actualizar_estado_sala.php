<?php

require_once __DIR__ . '/../config/validar_solicitud_estructura_local.php';

$solicitud = validarSolicitudEstructuraLocal('sala');
$conexion = $solicitud['conexion'];
$id = $solicitud['id'];
$volver = '../index/gestion_estructura_local.php';

try {
    $conexion->beginTransaction();

    $consulta = $conexion->prepare('SELECT activo FROM salas WHERE id_sala = :id FOR UPDATE');
    $consulta->execute(['id' => $id]);
    $sala = $consulta->fetch();
    if (!$sala) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=no_encontrado');
        exit;
    }

    if ((int) $sala['activo'] === 0) {
        $actualizar = $conexion->prepare(
            'UPDATE salas AS s
             INNER JOIN zonas AS z ON z.id_zona = s.id_zona
             SET s.activo = 1
             WHERE s.id_sala = :id AND z.activo = 1'
        );
        $actualizar->execute(['id' => $id]);
        if ($actualizar->rowCount() !== 1) {
            $conexion->rollBack();
            header('Location: ' . $volver . '?resultado=zona_no_disponible');
            exit;
        }
    } else {
        $ocupacionAbierta = $conexion->prepare(
            'SELECT id_ocupacion FROM ocupaciones
             WHERE id_modulo IN (SELECT id_modulo FROM modulos WHERE id_sala = :id_sala)
               AND fecha_fin IS NULL
             LIMIT 1 FOR UPDATE'
        );
        $ocupacionAbierta->execute(['id_sala' => $id]);
        if ($ocupacionAbierta->fetch()) {
            $conexion->rollBack();
            header('Location: ' . $volver . '?resultado=tiene_ocupaciones_abiertas');
            exit;
        }

        $actualizar = $conexion->prepare('UPDATE salas SET activo = 0 WHERE id_sala = :id_sala');
        $actualizar->execute(['id_sala' => $id]);
        $actualizar = $conexion->prepare('UPDATE modulos SET activo = 0 WHERE id_sala = :id_sala');
        $actualizar->execute(['id_sala' => $id]);
    }

    $conexion->commit();
    header('Location: ' . $volver . '?resultado=estado_actualizado');
    exit;
} catch (PDOException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error al actualizar el estado de una sala: ' . $error->getMessage());
    header('Location: ' . $volver . '?resultado=error_bd');
    exit;
}
