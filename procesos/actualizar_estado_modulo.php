<?php

require_once __DIR__ . '/../config/validar_solicitud_estructura_local.php';

$solicitud = validarSolicitudEstructuraLocal('modulo');
$conexion = $solicitud['conexion'];
$id = $solicitud['id'];
$volver = '../index/gestion_estructura_local.php';

try {
    $conexion->beginTransaction();

    $consulta = $conexion->prepare('SELECT activo FROM modulos WHERE id_modulo = :id FOR UPDATE');
    $consulta->execute(['id' => $id]);
    $modulo = $consulta->fetch();
    if (!$modulo) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=no_encontrado');
        exit;
    }

    if ((int) $modulo['activo'] === 0) {
        $actualizar = $conexion->prepare(
            'UPDATE modulos AS m
             INNER JOIN salas AS s ON s.id_sala = m.id_sala
             INNER JOIN zonas AS z ON z.id_zona = s.id_zona
             INNER JOIN tipos_modulo AS tm ON tm.id_tipo_modulo = m.id_tipo_modulo
             SET m.activo = 1
             WHERE m.id_modulo = :id
               AND s.activo = 1 AND z.activo = 1 AND tm.activo = 1'
        );
        $actualizar->execute(['id' => $id]);
        if ($actualizar->rowCount() !== 1) {
            $conexion->rollBack();
            header('Location: ' . $volver . '?resultado=destino_no_disponible');
            exit;
        }
    } else {
        $ocupacionAbierta = $conexion->prepare(
            'SELECT id_ocupacion FROM ocupaciones
             WHERE id_modulo = :id_modulo AND fecha_fin IS NULL
             LIMIT 1 FOR UPDATE'
        );
        $ocupacionAbierta->execute(['id_modulo' => $id]);
        if ($ocupacionAbierta->fetch()) {
            $conexion->rollBack();
            header('Location: ' . $volver . '?resultado=tiene_ocupaciones_abiertas');
            exit;
        }

        $actualizar = $conexion->prepare('UPDATE modulos SET activo = 0 WHERE id_modulo = :id');
        $actualizar->execute(['id' => $id]);
    }

    $conexion->commit();
    header('Location: ' . $volver . '?resultado=estado_actualizado');
    exit;
} catch (PDOException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error al actualizar el estado de un módulo: ' . $error->getMessage());
    header('Location: ' . $volver . '?resultado=error_bd');
    exit;
}
