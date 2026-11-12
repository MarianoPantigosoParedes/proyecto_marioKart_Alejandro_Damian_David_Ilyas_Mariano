<?php

require_once __DIR__ . '/../config/sesion.php';

iniciarSesion();

if (!isset($_SESSION['usuario']['id_gamemaster'], $_SESSION['usuario']['rol'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['usuario']['rol'] !== 'gamemaster') {
    header('Location: ' . ($_SESSION['usuario']['rol'] === 'admin' ? 'administracion.php' : 'login.php'));
    exit;
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/usuario_activo.php';
require_once __DIR__ . '/../consultas/obtener_resumen_disponibilidad_activa.php';
require_once __DIR__ . '/../consultas/obtener_resumen_salas_activas.php';
require_once __DIR__ . '/../consultas/obtener_modulos_operativos.php';

if (!usuarioSesionActivo($conexion)) {
    cerrarSesionPorCuentaInactiva();
}

$idGamemaster = (int) $_SESSION['usuario']['id_gamemaster'];
$idSalaFiltro = filter_input(INPUT_GET, 'sala', FILTER_VALIDATE_INT);
$idZonaFiltro = filter_input(INPUT_GET, 'zona', FILTER_VALIDATE_INT);
$estadoFiltro = (string) ($_GET['estado'] ?? 'todos');
if (!in_array($estadoFiltro, ['todos', 'libres', 'ocupados'], true)) {
    $estadoFiltro = 'todos';
}

$salas = obtenerResumenSalasActivas($conexion);

$zonas = [];
$resumenZonas = [];
foreach ($salas as $sala) {
    $idZona = (int) $sala['id_zona'];
    $zonas[$idZona] = $sala['zona'];
    if (!isset($resumenZonas[$idZona])) {
        $resumenZonas[$idZona] = [
            'nombre' => $sala['zona'],
            'salas' => 0,
            'modulos_totales' => 0,
            'modulos_libres' => 0,
            'modulos_ocupados' => 0,
            'capacidad_total' => 0,
        ];
    }
    $resumenZonas[$idZona]['salas']++;
    $resumenZonas[$idZona]['modulos_totales'] += (int) $sala['modulos_totales'];
    $resumenZonas[$idZona]['modulos_libres'] += (int) $sala['modulos_libres'];
    $resumenZonas[$idZona]['modulos_ocupados'] += (int) $sala['modulos_ocupados'];
    $resumenZonas[$idZona]['capacidad_total'] += (int) $sala['capacidad_total'];
}

$resumenDisponibilidad = obtenerResumenDisponibilidadActiva($conexion);
$modulosTotales = (int) $resumenDisponibilidad['modulos_totales'];
$modulosOcupados = (int) $resumenDisponibilidad['modulos_ocupados'];
$modulosLibres = $modulosTotales - $modulosOcupados;
$idSalaConsulta = $idSalaFiltro !== false && $idSalaFiltro !== null && $idSalaFiltro > 0
    ? $idSalaFiltro
    : null;
$idZonaConsulta = $idZonaFiltro !== false && $idZonaFiltro !== null && $idZonaFiltro > 0
    ? $idZonaFiltro
    : null;
$modulos = obtenerModulosOperativos($conexion, $idSalaConsulta, $idZonaConsulta, $estadoFiltro);

$modulosPorZona = [];
foreach ($salas as $sala) {
    $idZona = (int) $sala['id_zona'];
    $nombreSala = $sala['nombre'];
    $modulosPorZona[$idZona]['nombre'] = $sala['zona'];
    $modulosPorZona[$idZona]['salas'][$nombreSala] = [];
}
foreach ($modulos as $modulo) {
    $idZona = (int) $modulo['id_zona'];
    $modulosPorZona[$idZona]['nombre'] = $modulo['zona'];
    $modulosPorZona[$idZona]['salas'][$modulo['sala']][] = $modulo;
}

$mensajes = [
    'ocupacion_ok' => ['correcto', 'Módulo ocupado correctamente.'],
    'liberacion_ok' => ['correcto', 'Módulo liberado correctamente.'],
    'capacidad' => ['error', 'El número de jugadores debe estar entre 1 y la capacidad del módulo.'],
    'modulo_ocupado' => ['error', 'Ese módulo ya está ocupado. Actualiza el panel para ver su estado.'],
    'ocupacion_no_encontrada' => ['error', 'No se encontró el módulo seleccionado.'],
    'no_es_responsable' => ['error', 'Solo el gamemaster que inició la ocupación puede liberar el módulo.'],
    'ocupacion_cerrada' => ['error', 'La ocupación ya se había liberado. Actualiza el panel.'],
    'solicitud_invalida' => ['error', 'La solicitud no es válida o ha caducado. Recarga la página e inténtalo de nuevo.'],
    'error_bd' => ['error', 'No se pudo completar la operación. El error quedó registrado para su revisión.'],
];
$mensajeActual = $mensajes[(string) ($_GET['resultado'] ?? '')] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#050b1b">
    <title>Salas y ocupaciones | Mario DAW World</title>
    <link rel="icon" type="image/png" href="../logo/logo.png">
    <link rel="stylesheet" href="../estilos/css/estilos.css">
</head>
<body class="pagina-aplicacion">
    <?php require __DIR__ . '/header.php'; ?>

    <main class="contenedor">
        <header class="encabezado-pagina">
            <div>
                <p class="etiqueta">ESTADO EN TIEMPO REAL</p>
                <h1>Salas y módulos</h1>
                <p class="texto-secundario">Ocupa un módulo cuando llegue el grupo y libéralo cuando se marche.</p>
            </div>
        </header>

        <?php if ($mensajeActual !== null): ?>
            <p class="aviso aviso-<?= $mensajeActual[0] ?>" role="status"><?= htmlspecialchars($mensajeActual[1], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <section class="resumen" aria-label="Resumen de disponibilidad">
            <div class="dato-resumen"><span>Módulos en total</span><strong><?= $modulosTotales ?></strong></div>
            <div class="dato-resumen"><span>Libres</span><strong><?= $modulosLibres ?></strong></div>
            <div class="dato-resumen"><span>Ocupados</span><strong><?= $modulosOcupados ?></strong></div>
        </section>

        <?php require __DIR__ . '/filtros/filtro_modulos.php'; ?>
        <p class="texto-secundario" data-conteo-modulos aria-live="polite"></p>

        <?php if (!$modulosPorZona): ?>
            <p class="bloque sin-resultados">No hay módulos que coincidan con estos filtros.</p>
        <?php else: ?>
            <div class="zonas-distribucion">
                <?php foreach ($modulosPorZona as $idZona => $datosZona): ?>
                    <?php $resumenZona = $resumenZonas[$idZona]; ?>
                    <section class="zona-distribucion" data-zona-distribucion="<?= (int) $idZona ?>">
                        <header class="encabezado-zona">
                            <div>
                                <p class="etiqueta">DISTRIBUCIÓN DEL LOCAL</p>
                                <h2>Zona <?= htmlspecialchars($datosZona['nombre'], ENT_QUOTES, 'UTF-8') ?></h2>
                            </div>
                            <div class="resumen-zona">
                                <span><?= (int) $resumenZona['salas'] ?> salas</span>
                                <span><?= (int) $resumenZona['modulos_totales'] ?> módulos</span>
                                <span><?= (int) $resumenZona['modulos_libres'] ?> libres</span>
                                <span><?= (int) $resumenZona['modulos_ocupados'] ?> ocupados</span>
                            </div>
                        </header>
                        <div class="rejilla-salas">
                            <?php foreach ($datosZona['salas'] as $nombreSala => $listaModulos): ?>
                                <?php
                                $datosSala = null;
                                foreach ($salas as $sala) {
                                    if ($sala['nombre'] === $nombreSala) {
                                        $datosSala = $sala;
                                        break;
                                    }
                                }
                                ?>
                                <section class="tarjeta-sala" aria-label="<?= htmlspecialchars($nombreSala, ENT_QUOTES, 'UTF-8') ?>" data-tarjeta-sala data-zona-sala="<?= (int) $idZona ?>">
                                    <header class="encabezado-sala">
                                        <h3><?= htmlspecialchars($nombreSala, ENT_QUOTES, 'UTF-8') ?></h3>
                                        <?php if ($datosSala !== null): ?>
                                            <span class="texto-secundario">
                                                <?= (int) $datosSala['modulos_libres'] ?> libres ·
                                                <?= (int) $datosSala['modulos_ocupados'] ?> ocupados ·
                                                capacidad <?= (int) $datosSala['capacidad_total'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </header>
                                    <div class="rejilla-modulos">
                                        <?php if (!$listaModulos): ?>
                                            <p class="texto-secundario">Esta sala todavía no tiene módulos activos.</p>
                                        <?php endif; ?>
                                        <?php foreach ($listaModulos as $modulo): ?>
                                <?php $ocupado = $modulo['id_ocupacion'] !== null; ?>
                                <article class="tarjeta-modulo" data-modulo-sala="<?= (int) $modulo['id_sala'] ?>" data-modulo-zona="<?= (int) $idZona ?>" data-modulo-estado="<?= $ocupado ? 'ocupado' : 'libre' ?>">
                                    <div class="encabezado-sala">
                                        <h3>Módulo <?= htmlspecialchars($modulo['codigo'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <span class="estado estado-<?= $ocupado ? 'ocupado' : 'libre' ?>">
                                            <?= $ocupado ? 'Ocupado' : 'Libre' ?>
                                        </span>
                                    </div>
                                    <p class="texto-secundario">
                                        <?= htmlspecialchars($modulo['tipo_modulo'], ENT_QUOTES, 'UTF-8') ?>
                                        · <?= (int) $modulo['capacidad'] ?> plazas
                                    </p>

                                    <?php if ($ocupado): ?>
                                        <p>
                                            <?= (int) $modulo['num_jugadores'] ?> jugador(es)
                                            · desde <?= date('d/m/Y H:i', strtotime($modulo['fecha_inicio'])) ?>
                                        </p>
                                        <p class="texto-secundario">
                                            Responsable:
                                            <?= htmlspecialchars($modulo['responsable_nombre'] . ' ' . $modulo['responsable_apellido'], ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                        <?php if ((int) $modulo['responsable_id'] === $idGamemaster): ?>
                                            <form class="formulario-salida" action="../procesos/liberar.php" method="post" data-confirmacion="¿Confirmas que el grupo ha salido y quieres liberar este módulo?" onsubmit="return confirmarEnvio(this, event)">
                                                <input type="hidden" name="id_ocupacion" value="<?= (int) $modulo['id_ocupacion'] ?>">
                                                <button class="boton boton-peligro" type="submit">Liberar módulo</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <form class="formulario-ocupacion" action="../procesos/ocupar.php" method="post" novalidate data-validacion data-formulario-ocupacion data-confirmacion="¿Confirmas que el grupo ya está en el módulo?" onsubmit="return validarFormulario(this, event) && confirmarEnvio(this, event)" oninput="limpiarErrorCampo(event.target)" onchange="limpiarErrorCampo(event.target)">
                                            <input type="hidden" name="id_modulo" value="<?= (int) $modulo['id_modulo'] ?>">
                                            <div class="campo">
                                                <label for="jugadores-<?= (int) $modulo['id_modulo'] ?>">Jugadores</label>
                                                <input
                                                    id="jugadores-<?= (int) $modulo['id_modulo'] ?>"
                                                    name="num_jugadores"
                                                    type="number"
                                                    min="1"
                                                    max="<?= (int) $modulo['capacidad'] ?>"
                                                    value="1"
                                                >
                                            </div>
                                            <span class="texto-secundario">Máximo <?= (int) $modulo['capacidad'] ?></span>
                                            <button class="boton boton-principal" type="submit">Ocupar módulo</button>
                                        </form>
                                    <?php endif; ?>
                                </article>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="../estilos/js/app.js?v=20261112-filtros" defer></script>
</body>
</html>
