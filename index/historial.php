<?php

require_once __DIR__ . '/../config/sesion.php';

iniciarSesion();

if (!isset($_SESSION['usuario']['id_gamemaster'], $_SESSION['usuario']['rol'])) {
    header('Location: login.php');
    exit;
}

if (!in_array($_SESSION['usuario']['rol'], ['admin', 'gamemaster'], true)) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/usuario_activo.php';
require_once __DIR__ . '/../consultas/obtener_zonas_del_local.php';
require_once __DIR__ . '/../consultas/obtener_salas_para_historial.php';
require_once __DIR__ . '/../consultas/obtener_modulos_para_historial.php';
require_once __DIR__ . '/../consultas/contar_registros_historial.php';
require_once __DIR__ . '/../consultas/obtener_registros_historial.php';

if (!usuarioSesionActivo($conexion)) {
    cerrarSesionPorCuentaInactiva();
}

$idSala = filter_input(INPUT_GET, 'sala', FILTER_VALIDATE_INT);
$idZona = filter_input(INPUT_GET, 'zona', FILTER_VALIDATE_INT);
$idModulo = filter_input(INPUT_GET, 'modulo', FILTER_VALIDATE_INT);
$fechaDesde = trim((string) ($_GET['desde'] ?? ''));
$fechaHasta = trim((string) ($_GET['hasta'] ?? ''));
$pagina = filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT);
$pagina = $pagina !== false && $pagina !== null && $pagina > 0 ? $pagina : 1;
$errorFiltros = false;

foreach (['desde' => $fechaDesde, 'hasta' => $fechaHasta] as $nombre => $fecha) {
    if ($fecha !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !checkdate((int) substr($fecha, 5, 2), (int) substr($fecha, 8, 2), (int) substr($fecha, 0, 4)))) {
        $errorFiltros = true;
    }
}

if ($idSala === false || $idSala < 1) {
    $idSala = null;
}
if ($idZona === false || $idZona === null || $idZona < 1) {
    $idZona = null;
}
if ($idModulo === false || $idModulo < 1) {
    $idModulo = null;
}
if ($fechaDesde !== '' && $fechaHasta !== '' && $fechaDesde > $fechaHasta) {
    $errorFiltros = true;
}

$zonasArchivadas = obtenerZonasDelLocal($conexion);
$zonas = [];
foreach ($zonasArchivadas as $zona) {
    $zonas[(int) $zona['id_zona']] = $zona['nombre'];
}
$salas = obtenerSalasParaHistorial($conexion);
$modulos = obtenerModulosParaHistorial($conexion);
$totalRegistros = contarRegistrosHistorial(
    $conexion,
    $idZona,
    $idSala,
    $idModulo,
    $fechaDesde,
    $fechaHasta,
    $errorFiltros
);
$registrosPorPagina = 50;
$totalPaginas = max(1, (int) ceil($totalRegistros / $registrosPorPagina));
$pagina = min($pagina, $totalPaginas);
$desplazamiento = ($pagina - 1) * $registrosPorPagina;

$ocupaciones = obtenerRegistrosHistorial(
    $conexion,
    $idZona,
    $idSala,
    $idModulo,
    $fechaDesde,
    $fechaHasta,
    $errorFiltros,
    $registrosPorPagina,
    $desplazamiento
);

$parametrosPagina = [];
if ($idSala !== null) {
    $parametrosPagina['sala'] = $idSala;
}
if ($idZona !== null) {
    $parametrosPagina['zona'] = $idZona;
}
if ($idModulo !== null) {
    $parametrosPagina['modulo'] = $idModulo;
}
if ($fechaDesde !== '') {
    $parametrosPagina['desde'] = $fechaDesde;
}
if ($fechaHasta !== '') {
    $parametrosPagina['hasta'] = $fechaHasta;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#050b1b">
    <title>Historial de ocupaciones | Mario DAW World</title>
    <link rel="icon" type="image/png" href="../logo/logo.png">
    <link rel="stylesheet" href="../estilos/css/estilos.css">
</head>
<body class="pagina-aplicacion">
    <?php require __DIR__ . '/header.php'; ?>

    <main class="contenedor">
        <header class="encabezado-pagina">
            <div>
                <p class="etiqueta">CONSULTA DE ACTIVIDAD</p>
                <h1>Historial de ocupaciones</h1>
                <p class="texto-secundario">Filtra por sala, módulo y fechas para revisar el uso de los recursos.</p>
            </div>
        </header>

        <?php if ($errorFiltros): ?>
            <p class="aviso aviso-error" role="alert">Revisa el formato de las fechas y asegúrate de que la fecha inicial no sea posterior a la final.</p>
        <?php endif; ?>

        <?php require __DIR__ . '/filtros/filtro_historial.php'; ?>

        <p class="texto-secundario"><?= $totalRegistros ?> resultado(s). Se muestran 50 por página.</p>

        <?php if (!$ocupaciones): ?>
            <p class="bloque sin-resultados">No hay ocupaciones para los filtros seleccionados.</p>
        <?php else: ?>
            <div class="tabla-desplazable">
                <table class="tabla-datos">
                    <thead>
                        <tr>
                            <th scope="col">Fecha de entrada</th>
                            <th scope="col">Fecha de salida</th>
                            <th scope="col">Zona</th>
                            <th scope="col">Sala</th>
                            <th scope="col">Módulo</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Jugadores</th>
                            <th scope="col">Gamemaster</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ocupaciones as $ocupacion): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($ocupacion['fecha_inicio'])) ?></td>
                                <td><?= $ocupacion['fecha_fin'] === null ? 'En curso' : date('d/m/Y H:i', strtotime($ocupacion['fecha_fin'])) ?></td>
                                <td>Zona <?= htmlspecialchars($ocupacion['zona'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($ocupacion['sala'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($ocupacion['modulo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($ocupacion['tipo_modulo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $ocupacion['num_jugadores'] ?></td>
                                <td><?= htmlspecialchars($ocupacion['gamemaster_nombre'] . ' ' . $ocupacion['gamemaster_apellido'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPaginas > 1): ?>
                <nav class="navegacion" aria-label="Paginación del historial">
                    <?php if ($pagina > 1): ?>
                        <a href="?<?= htmlspecialchars(http_build_query($parametrosPagina + ['pagina' => $pagina - 1]), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
                    <?php endif; ?>
                    <span class="usuario-activo">Página <?= $pagina ?> de <?= $totalPaginas ?></span>
                    <?php if ($pagina < $totalPaginas): ?>
                        <a href="?<?= htmlspecialchars(http_build_query($parametrosPagina + ['pagina' => $pagina + 1]), ENT_QUOTES, 'UTF-8') ?>">Siguiente</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <script src="../estilos/js/app.js?v=20261112-filtros" defer></script>
</body>
</html>
