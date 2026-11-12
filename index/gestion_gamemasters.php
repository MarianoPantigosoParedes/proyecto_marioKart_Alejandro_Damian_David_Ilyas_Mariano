<?php

require_once __DIR__ . '/../config/sesion.php';

iniciarSesion();

if (!isset($_SESSION['usuario']['id_gamemaster'], $_SESSION['usuario']['rol'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['usuario']['rol'] !== 'admin') {
    header('Location: ' . ($_SESSION['usuario']['rol'] === 'gamemaster' ? 'panel_gamemaster.php' : 'login.php'));
    exit;
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/usuario_activo.php';
require_once __DIR__ . '/../consultas/obtener_gamemasters_gestion.php';

if (!usuarioSesionActivo($conexion)) {
    cerrarSesionPorCuentaInactiva();
}

$busquedaRecibida = $_GET['buscar'] ?? '';
$busquedaGamemaster = is_string($busquedaRecibida)
    ? trim(mb_substr($busquedaRecibida, 0, 80, 'UTF-8'))
    : '';
$estadoRecibido = $_GET['estado'] ?? 'todos';
$estadoGamemaster = is_string($estadoRecibido) && in_array($estadoRecibido, ['todos', 'activos', 'inactivos'], true)
    ? $estadoRecibido
    : 'todos';

$gamemasters = obtenerGamemastersGestion($conexion, $busquedaGamemaster, $estadoGamemaster);

$mensajes = [
    'guardado' => ['correcto', 'La cuenta gamemaster se guardó correctamente.'],
    'cuenta_desactivada' => ['correcto', 'La cuenta se desactivó; ahora puedes reactivarla o eliminarla definitivamente.'],
    'gamemaster_eliminado' => ['correcto', 'La cuenta y todo su historial se eliminaron definitivamente.'],
    'estado_actualizado' => ['correcto', 'La cuenta se reactivó.'],
    'ocupaciones_abiertas' => ['error', 'No se puede desactivar una cuenta con ocupaciones abiertas.'],
    'gamemaster_ocupaciones_abiertas' => ['error', 'No se puede eliminar definitivamente la cuenta hasta que no tenga ocupaciones abiertas.'],
    'gamemaster_activo' => ['error', 'Desactiva la cuenta antes de eliminarla definitivamente.'],
    'datos_invalidos' => ['error', 'Revisa los datos: usuario de 3 a 30 caracteres, nombre y apellidos obligatorios, y contraseña de 12 a 72 bytes.'],
    'duplicado' => ['error', 'Ese nombre de usuario ya está registrado.'],
    'no_encontrado' => ['error', 'No se encontró la cuenta gamemaster.'],
    'rol_no_disponible' => ['error', 'No está disponible el rol gamemaster en la base de datos.'],
    'solicitud_invalida' => ['error', 'La solicitud no es válida o ha caducado. Recarga la página.'],
    'error_bd' => ['error', 'No se pudo completar el cambio. El error quedó registrado para su revisión.'],
];
$mensajeActual = $mensajes[(string) ($_GET['resultado'] ?? '')] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#050b1b">
    <title>Gestión de gamemasters | Mario DAW World</title>
    <link rel="icon" type="image/png" href="../logo/logo.png">
    <link rel="stylesheet" href="../estilos/css/estilos.css?v=20261110">
</head>
<body class="pagina-aplicacion">
    <?php require __DIR__ . '/header.php'; ?>

    <main class="contenedor">
        <header class="encabezado-pagina">
            <div>
                <p class="etiqueta">CUENTAS Y ACCESOS</p>
                <h1>Gestión de gamemasters</h1>
                <p class="texto-secundario">Crea cuentas, modifica sus datos, restablece contraseñas y administra su acceso.</p>
            </div>
        </header>

        <?php if ($mensajeActual !== null): ?>
            <p class="aviso aviso-<?= $mensajeActual[0] ?>" role="status"><?= htmlspecialchars($mensajeActual[1], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <section class="bloque">
            <div class="titulo-seccion">
                <div><p class="etiqueta">NUEVO ACCESO</p><h2>Crear gamemaster</h2></div>
            </div>
            <form class="formulario-gestion formulario-creacion" action="../procesos/guardar_gamemaster.php" method="post" novalidate data-validacion onsubmit="return validarFormulario(this, event)" oninput="limpiarErrorCampo(event.target)" onchange="limpiarErrorCampo(event.target)">
                <div class="campo">
                    <label for="nuevo-username">Usuario</label>
                    <input id="nuevo-username" name="username" minlength="3" maxlength="30" pattern="[A-Za-z0-9._\-]{3,30}" autocomplete="off">
                </div>
                <div class="campo">
                    <label for="nuevo-nombre">Nombre</label>
                    <input id="nuevo-nombre" name="nombre" maxlength="50">
                </div>
                <div class="campo">
                    <label for="nuevo-apellido">Apellidos</label>
                    <input id="nuevo-apellido" name="apellido" maxlength="80">
                </div>
                <div class="campo">
                    <label for="nueva-contrasena">Contraseña inicial</label>
                    <input id="nueva-contrasena" name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password">
                </div>
                <button class="boton boton-principal" type="submit">Crear cuenta</button>
            </form>
            <p class="texto-secundario">El usuario admite letras, números, punto, guion y guion bajo.</p>
        </section>

        <section class="bloque">
            <div class="titulo-seccion">
                <div><p class="etiqueta">CUENTAS REGISTRADAS</p><h2>Gamemasters</h2></div>
                <span class="texto-secundario"><?= count($gamemasters) ?> resultado(s)</span>
            </div>
            <?php require __DIR__ . '/filtros/filtro_gamemasters.php'; ?>
            <?php if (!$gamemasters): ?>
                <p class="sin-resultados"><?= $busquedaGamemaster !== '' || $estadoGamemaster !== 'todos' ? 'No hay cuentas que coincidan con el filtro.' : 'Todavía no hay cuentas gamemaster.' ?></p>
            <?php else: ?>
                <div class="tabla-desplazable">
                    <table class="tabla-datos tabla-gestion tabla-gamemasters">
                        <thead>
                            <tr>
                                <th scope="col">Usuario</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Apellidos</th>
                                <th scope="col">Nueva contraseña</th>
                                <th scope="col">Uso / estado</th>
                                <th scope="col">Guardar</th>
                                <th scope="col">Acceso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gamemasters as $gamemaster): ?>
                                <?php $idGamemaster = (int) $gamemaster['id_gamemaster']; ?>
                                <tr>
                                    <td data-label="Usuario">
                                        <form id="editar-gamemaster-<?= $idGamemaster ?>" action="../procesos/guardar_gamemaster.php" method="post" novalidate data-validacion data-validacion-gestion onsubmit="return validarFormulario(this, event)" oninput="limpiarErrorCampo(event.target)" onchange="limpiarErrorCampo(event.target)">
                                            <input type="hidden" name="id" value="<?= $idGamemaster ?>">
                                        </form>
                                        <label class="solo-lectura" for="username-<?= $idGamemaster ?>">Usuario</label>
                                        <input form="editar-gamemaster-<?= $idGamemaster ?>" id="username-<?= $idGamemaster ?>" name="username" value="<?= htmlspecialchars($gamemaster['username'], ENT_QUOTES, 'UTF-8') ?>" minlength="3" maxlength="30" pattern="[A-Za-z0-9._\-]{3,30}" autocomplete="off" oninput="limpiarErrorCampo(this)" onchange="limpiarErrorCampo(this)">
                                    </td>
                                    <td data-label="Nombre">
                                        <label class="solo-lectura" for="nombre-<?= $idGamemaster ?>">Nombre</label>
                                        <input form="editar-gamemaster-<?= $idGamemaster ?>" id="nombre-<?= $idGamemaster ?>" name="nombre" value="<?= htmlspecialchars($gamemaster['nombre'], ENT_QUOTES, 'UTF-8') ?>" maxlength="50" oninput="limpiarErrorCampo(this)" onchange="limpiarErrorCampo(this)">
                                    </td>
                                    <td data-label="Apellidos">
                                        <label class="solo-lectura" for="apellido-<?= $idGamemaster ?>">Apellidos</label>
                                        <input form="editar-gamemaster-<?= $idGamemaster ?>" id="apellido-<?= $idGamemaster ?>" name="apellido" value="<?= htmlspecialchars($gamemaster['apellido'], ENT_QUOTES, 'UTF-8') ?>" maxlength="80" oninput="limpiarErrorCampo(this)" onchange="limpiarErrorCampo(this)">
                                    </td>
                                    <td data-label="Nueva contraseña">
                                        <label class="solo-lectura" for="password-<?= $idGamemaster ?>">Nueva contraseña (opcional)</label>
                                        <input form="editar-gamemaster-<?= $idGamemaster ?>" id="password-<?= $idGamemaster ?>" name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password" placeholder="Sin cambios" oninput="limpiarErrorCampo(this)" onchange="limpiarErrorCampo(this)">
                                    </td>
                                    <td data-label="Uso y estado">
                                        <?= (int) $gamemaster['activo'] === 1 ? 'Activa' : 'Desactivada' ?>
                                        · <?= (int) $gamemaster['ocupaciones'] ?> ocupación(es) 
                                    </td>
                                    <td data-label="Guardar"><button class="boton boton-secundario" form="editar-gamemaster-<?= $idGamemaster ?>" type="submit">Guardar</button></td>
                                    <td data-label="Acceso">
                                        <form class="formulario-estado" action="../procesos/eliminar_gamemaster.php" method="post" data-confirmacion="<?= (int) $gamemaster['activo'] === 1 ? '¿Desactivar esta cuenta? Podrás eliminarla definitivamente después.' : '¿Reactivar esta cuenta gamemaster?' ?>" onsubmit="return confirmarEnvio(this, event)">
                                            <input type="hidden" name="id" value="<?= $idGamemaster ?>">
                                            <button class="boton <?= (int) $gamemaster['activo'] === 1 ? 'boton-peligro' : 'boton-secundario' ?>" type="submit">
                                                <?= (int) $gamemaster['activo'] === 1 ? 'Desactivar' : 'Reactivar' ?>
                                            </button>
                                        </form>
                                        <?php if ((int) $gamemaster['activo'] === 0): ?>
                                            <form class="formulario-estado" action="../procesos/eliminar_gamemaster_definitivo.php" method="post" data-confirmacion="Esta acción eliminará definitivamente la cuenta y todo su historial de ocupaciones. No se puede deshacer." onsubmit="return confirmarEnvio(this, event)">
                                                <input type="hidden" name="id" value="<?= $idGamemaster ?>">
                                                <button class="boton boton-peligro" type="submit">Eliminar definitivamente</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="../estilos/js/app.js?v=20261112-filtros" defer></script>
</body>
</html>
