<?php

require_once __DIR__ . '/../config/validar_solicitud_estructura_local.php';

$solicitud = validarSolicitudEstructuraLocal('tipo_modulo');
$conexion = $solicitud['conexion'];
$id = $solicitud['id'];
$volver = '../index/gestion_estructura_local.php';

try {
    $conexion->beginTransaction();

    $consulta = $conexion->prepare('SELECT activo FROM tipos_modulo WHERE id_tipo_modulo = :id FOR UPDATE');
    $consulta->execute(['id' => $id]);
    $tipoModulo = $consulta->fetch();
    if (!$tipoModulo) {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=no_encontrado');
        exit;
    }

    if ((int) $tipoModulo['activo'] === 0) {
        $actualizar = $conexion->prepare('UPDATE tipos_modulo SET activo = 1 WHERE id_tipo_modulo = :id');
        $actualizar->execute(['id' => $id]);
    } else {
        $ocupacionAbierta = $conexion->prepare(
            'SELECT o.id_ocupacion
             FROM ocupaciones AS o
             INNER JOIN modulos AS m ON m.id_modulo = o.id_modulo
             WHERE m.id_tipo_modulo = :id_tipo_modulo AND o.fecha_fin IS NULL
             LIMIT 1 FOR UPDATE'
        );
        $ocupacionAbierta->execute(['id_tipo_modulo' => $id]);
        if ($ocupacionAbierta->fetch()) {
            $conexion->rollBack();
            header('Location: ' . $volver . '?resultado=tiene_ocupaciones_abiertas');
            exit;
        }

        $tieneHistorial = $conexion->prepare(
            'SELECT o.id_ocupacion
             FROM ocupaciones AS o
             INNER JOIN modulos AS m ON m.id_modulo = o.id_modulo
             WHERE m.id_tipo_modulo = :id_tipo_modulo
             LIMIT 1'
        );
        $tieneHistorial->execute(['id_tipo_modulo' => $id]);
        if ($tieneHistorial->fetch()) {
            $actualizar = $conexion->prepare('UPDATE tipos_modulo SET activo = 0 WHERE id_tipo_modulo = :id');
            $actualizar->execute(['id' => $id]);
            $actualizar = $conexion->prepare(
                'UPDATE modulos SET activo = 0 WHERE id_tipo_modulo = :id_tipo_modulo'
            );
            $actualizar->execute(['id_tipo_modulo' => $id]);
        } else {
            $borrar = $conexion->prepare(
                'DELETE p FROM puestos_juego AS p
                 INNER JOIN modulos AS m ON m.id_modulo = p.id_modulo
                 WHERE m.id_tipo_modulo = :id_tipo_modulo'
            );
            $borrar->execute(['id_tipo_modulo' => $id]);
            $borrar = $conexion->prepare('DELETE FROM modulos WHERE id_tipo_modulo = :id_tipo_modulo');
            $borrar->execute(['id_tipo_modulo' => $id]);
            $borrar = $conexion->prepare('DELETE FROM tipos_modulo WHERE id_tipo_modulo = :id');
            $borrar->execute(['id' => $id]);
        }
    }

    $conexion->commit();
    header('Location: ' . $volver . '?resultado=estado_actualizado');
    exit;
} catch (PDOException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error al actualizar el estado de un tipo de módulo: ' . $error->getMessage());
    header('Location: ' . $volver . '?resultado=error_bd');
    exit;
}
