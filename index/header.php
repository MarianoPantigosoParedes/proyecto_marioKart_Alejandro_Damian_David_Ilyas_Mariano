<?php
$rolUsuario = $_SESSION['usuario']['rol'];
$paginaActual = basename($_SERVER['SCRIPT_NAME']);
$enlacesNavegacion = $rolUsuario === 'admin'
    ? [
        'administracion.php' => 'Resumen',
        'historial.php' => 'Historial',
        'gestion_estructura_local.php' => 'Gestión del local',
        'gestion_gamemasters.php' => 'Gamemasters',
    ]
    : [
        'panel_gamemaster.php' => 'Salas',
        'historial.php' => 'Historial',
        'gestion_estructura_local.php' => 'Gestión del local',
    ];
$nombreUsuario = htmlspecialchars(
    $_SESSION['usuario']['nombre'] . ' ' . $_SESSION['usuario']['apellido'],
    ENT_QUOTES,
    'UTF-8'
);
?>
<header class="barra-superior">
    <a class="marca" href="../index.php" aria-label="Mario DAW World, inicio">
        <img class="marca-imagen" src="../logo/logo.png" alt="Mario DAW World">
    </a>
    <nav class="navegacion" aria-label="Navegación principal">
        <?php foreach ($enlacesNavegacion as $archivo => $etiqueta): ?>
            <a href="<?= htmlspecialchars($archivo, ENT_QUOTES, 'UTF-8') ?>" <?= $paginaActual === $archivo ? 'aria-current="page"' : '' ?>>
                <?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
        <span class="usuario-activo"><?= $nombreUsuario ?> · <?= $rolUsuario === 'admin' ? 'Administración' : 'Gamemaster' ?></span>
        <form action="../procesos/logout.php" method="post">
            <button class="boton boton-secundario" type="submit">Cerrar sesión</button>
        </form>
    </nav>
</header>
