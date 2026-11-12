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
require_once __DIR__ . '/../consultas/obtener_resumen_disponibilidad_activa.php';
require_once __DIR__ . '/../consultas/obtener_resumen_salas_activas.php';
require_once __DIR__ . '/../consultas/obtener_ocupaciones_abiertas.php';
require_once __DIR__ . '/../consultas/contar_gamemasters_activos.php';

if (!usuarioSesionActivo($conexion)) {
    cerrarSesionPorCuentaInactiva();
}

$resumen = obtenerResumenDisponibilidadActiva($conexion);
$usuariosActivos = contarGamemastersActivos($conexion);
$salas = obtenerResumenSalasActivas($conexion);

$zonas = [];
foreach ($salas as $sala) {
    $idZona = (int) $sala['id_zona'];
    if (!isset($zonas[$idZona])) {
        $zonas[$idZona] = [
            'nombre' => $sala['nombre_zona'],
            'salas' => [],
            'modulos_totales' => 0,
            'modulos_libres' => 0,
            'modulos_ocupados' => 0,
            'capacidad_total' => 0,
        ];
    }
    $zonas[$idZona]['salas'][] = $sala;
    $zonas[$idZona]['modulos_totales'] += (int) $sala['modulos_totales'];
    $zonas[$idZona]['modulos_libres'] += (int) $sala['modulos_libres'];
    $zonas[$idZona]['modulos_ocupados'] += (int) $sala['modulos_ocupados'];
    $zonas[$idZona]['capacidad_total'] += (int) $sala['capacidad_total'];
}
$opcionesZonaFiltro = [];
foreach ($zonas as $idZona => $zona) {
    $opcionesZonaFiltro[$idZona] = $zona['nombre'];
}

$ocupacionesAbiertas = obtenerOcupacionesAbiertas($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#050b1b">
    <title>Panel pal Admin | Mario DAW World</title>
    <link rel="icon" type="image/png" href="../logo/logo.png">
    <link rel="stylesheet" href="../estilos/css/estilos.css?v=20261110-filtros">
</head>
<body class="pagina-aplicacion">
    <?php require __DIR__ . '/header.php'; ?>

    <main class="contenedor">
        <header class="encabezado-pagina">
            <div>
                <p class="etiqueta">CENTRO DE OPERACIONES</p>
                <h1>Resumen del local</h1>
                <p class="texto-secundario">Disponibilidad de recursos y actividad actual de Mario DAW World.</p>
            </div>
            <div class="acciones-estructura-local">
                <a class="boton boton-secundario" href="gestion_estructura_local.php">Gestionar estructura del local</a>
                <a class="boton boton-principal" href="gestion_gamemasters.php">Gestionar gamemasters</a>
            </div>
        </header>

        <section class="resumen" aria-label="Resumen del centro">
            <div class="dato-resumen"><span>Módulos</span><strong><?= (int) $resumen['modulos_totales'] ?></strong></div>
            <div class="dato-resumen"><span>Libres</span><strong><?= (int) $resumen['modulos_libres'] ?></strong></div>
            <div class="dato-resumen"><span>Ocupados</span><strong><?= (int) $resumen['modulos_ocupados'] ?></strong></div>
        </section>

        <section class="bloque">
            <div class="titulo-seccion">
                <div>
                    <p class="etiqueta">DISTRIBUCIÓN</p>
                    <h2>Estado por zonas</h2>
                </div>
                <span class="texto-secundario"><?= $usuariosActivos ?> cuentas activas</span>
            </div>
            <?php
            $mostrarEstadoZonaFiltro = false;
            $mostrarEstadoSalaFiltro = false;
            require __DIR__ . '/filtros/filtro_zonas.php';
            require __DIR__ . '/filtros/filtro_salas.php';
            ?>
            <div class="zonas-administracion">
                <?php foreach ($zonas as $zona): ?>
                    <section class="zona-administracion" data-filtro-item="zonas" data-filtro-texto="<?= htmlspecialchars($zona['nombre'], ENT_QUOTES, 'UTF-8') ?>" data-filtro-estado="activo">
                        <header class="encabezado-zona">
                            <div>
                                <p class="etiqueta"><?= count($zona['salas']) ?> salas · capacidad <?= (int) $zona['capacidad_total'] ?></p>
                                <h3>Zona <?= htmlspecialchars($zona['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                            </div>
                            <div class="resumen-zona">
                                <span><?= (int) $zona['modulos_totales'] ?> módulos</span>
                                <span><?= (int) $zona['modulos_libres'] ?> libres</span>
                                <span><?= (int) $zona['modulos_ocupados'] ?> ocupados</span>
                            </div>
                        </header>
                        <div class="tabla-desplazable">
                            <table class="tabla-datos">
                                <thead>
                                    <tr>
                                        <th scope="col">Sala</th>
                                        <th scope="col">Capacidad</th>
                                        <th scope="col">Módulos</th>
                                        <th scope="col">Libres</th>
                                        <th scope="col">Ocupados</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($zona['salas'] as $sala): ?>
                                        <tr data-filtro-item="salas" data-filtro-texto="<?= htmlspecialchars($sala['nombre'] . ' ' . $zona['nombre'], ENT_QUOTES, 'UTF-8') ?>" data-filtro-zona="<?= (int) $sala['id_zona'] ?>" data-filtro-estado="activo">
                                            <td><?= htmlspecialchars($sala['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= (int) $sala['capacidad_total'] ?></td>
                                            <td><?= (int) $sala['modulos_totales'] ?></td>
                                            <td><?= (int) $sala['modulos_libres'] ?></td>
                                            <td><?= (int) $sala['modulos_ocupados'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bloque">
            <div class="titulo-seccion">
                <div>
                    <p class="etiqueta">EN DIRECTO</p>
                    <h2>Ocupacion Real Actual</h2>
                </div>
                <span class="texto-secundario"><?= count($ocupacionesAbiertas) ?> en curso !!!</span>
            </div>
            <?php if (!$ocupacionesAbiertas): ?>
                <p class="sin-resultados">No hay módulos ocupados.</p>
            <?php else: ?>
                <div class="tabla-desplazable">
                    <table class="tabla-datos">
                        <thead>
                            <tr>
                                <th scope="col">Sala</th>
                                <th scope="col">Zona</th>
                                <th scope="col">Módulo</th>
                                <th scope="col">Jugadores</th>
                                <th scope="col">Desde</th>
                                <th scope="col">Gamemaster</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ocupacionesAbiertas as $ocupacion): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ocupacion['sala'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($ocupacion['zona'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($ocupacion['modulo'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= (int) $ocupacion['num_jugadores'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($ocupacion['fecha_inicio'])) ?></td>
                                    <td><?= htmlspecialchars($ocupacion['nombre'] . ' ' . $ocupacion['apellido'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script src="../estilos/js/app.js?v=20261112-filtros" defer></script>
</body>
</html>
