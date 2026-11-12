<?php

function validarSolicitudEstructuraLocal(string $entidadEsperada): array
{
    iniciarSesion();

    $volver = '../index/gestion_estructura_local.php';
    if (!usuarioTieneRol(['admin', 'gamemaster'])) {
        header('Location: ../index/login.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . $volver);
        exit;
    }

    $entidadRecibida = $_POST['entidad'] ?? '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (
        $entidadRecibida !== $entidadEsperada
        || $id === false
        || $id === null
        || $id < 1
    ) {
        header('Location: ' . $volver . '?resultado=datos_invalidos');
        exit;
    }

    require_once __DIR__ . '/conexion.php';
    require_once __DIR__ . '/usuario_activo.php';

    if (!usuarioSesionActivo($conexion)) {
        cerrarSesionPorCuentaInactiva();
    }

    return ['conexion' => $conexion, 'id' => $id];
}
