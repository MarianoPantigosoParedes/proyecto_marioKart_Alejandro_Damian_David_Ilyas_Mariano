<?php

require_once __DIR__ . '/../config/sesion.php';

iniciarSesion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#050b1b">
    <title>Iniciar sesión | Mario DAW World</title>
    <link rel="icon" type="image/png" href="../logo/logo.png">
    <link rel="stylesheet" href="../estilos/css/estilos.css">
</head>
<body class="pagina-acceso">
    <main class="disposicion-acceso">
        <section class="panel-intro" aria-labelledby="brand-title">
            <a class="marca" href="../index.php" aria-label="Mario DAW World, inicio">
                <img class="marca-imagen" src="../logo/logo.png" alt="Mario DAW World">
            </a>

            <div class="texto-intro">
                <p class="etiqueta">GESTIÓN DEL LOCAL</p>
                <h1 id="brand-title">¡Tu próxima partida empieza aquí!</h1>
                <p class="descripcion-intro">
                    Accede para consultar las salas y gestionar la disponibilidad de los módulos.
                </p>
            </div>

            <p class="pie-intro">PANEL DE CONTROL · MARIO DAW WORLD</p>
        </section>

        <section class="principal-acceso" aria-labelledby="login-title">
            <div class="tarjeta">
                <div class="encabezado-tarjeta">
                    <p class="etiqueta">ACCESO DE PERSONAL</p>
                    <h2 id="login-title">Iniciar sesión</h2>
                    <p>Introduce tus credenciales para entrar al centro de operaciones.</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <p class="aviso aviso-error" role="alert">
                        <?php if ($_GET['error'] === 'rol'): ?>
                            La cuenta no tiene un rol válido. Contacta con administración.
                        <?php elseif ($_GET['error'] === 'sesion'): ?>
                            El formulario ha caducado. Recarga la página e inténtalo de nuevo.
                        <?php elseif ($_GET['error'] === 'cuenta_inactiva'): ?>
                            La cuenta está desactivada. Contacta con administración.
                        <?php else: ?>
                            Usuario o contraseña incorrectos.
                        <?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php ?>
                <form class="formulario-apilado" action="../procesos/login.php" method="post" novalidate data-validacion onsubmit="return validarFormulario(this, event)" oninput="limpiarErrorCampo(event.target)" onchange="limpiarErrorCampo(event.target)">
                    <div class="campo">
                        <label for="username">Usuario</label>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            autocomplete="username"
                            placeholder="Tu nombre de usuario"
                            maxlength="30"
                            autofocus
                        >
                    </div>

                    <div class="campo">
                        <label for="password">Contraseña</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Tu contraseña"
                        >
                    </div>

                    <button class="boton boton-principal boton-completo" type="submit">
                        Entrar al panel
                        <span aria-hidden="true">→</span>
                    </button>
                </form>

                <p class="nota-acceso">Acceso exclusivo para el personal del Gaming Center.</p>
            </div>
        </section>
    </main>
    <script src="../estilos/js/app.js?v=20261112-filtros" defer></script>
</body>
</html>