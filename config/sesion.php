<?php

function iniciarSesion(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function usuarioTieneSesion(): bool
{
    return isset($_SESSION['usuario']['id_gamemaster'], $_SESSION['usuario']['rol']);
}

function usuarioTieneRol(array $roles): bool
{
    return usuarioTieneSesion()
        && in_array($_SESSION['usuario']['rol'], $roles, true);
}

function redirigirLogin(): void
{
    header('Location: ../index/login.php');
    exit;
}

function cerrarSesion(): void
{
    $_SESSION = [];
    session_unset();
    session_destroy();
}

function cerrarSesionPorCuentaInactiva(): void
{
    cerrarSesion();
    header('Location: ../index/login.php?error=cuenta_inactiva');
    exit;
}
