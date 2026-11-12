<?php

require_once __DIR__ . '/../config/sesion.php';

iniciarSesion();

if (
    !isset($_SESSION['usuario']['id_gamemaster'], $_SESSION['usuario']['rol'])
    || !in_array($_SESSION['usuario']['rol'], ['admin', 'gamemaster'], true)
) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/usuario_activo.php';
require_once __DIR__ . '/../consultas/obtener_zonas_del_local.php';
require_once __DIR__ . '/../consultas/obtener_salas_del_local.php';
require_once __DIR__ . '/../consultas/obtener_tipos_modulo_del_local.php';
require_once __DIR__ . '/../consultas/obtener_modulos_del_local.php';

if (!usuarioSesionActivo($conexion)) {
    cerrarSesionPorCuentaInactiva();
}

$zonas = obtenerZonasDelLocal($conexion);
$salas = obtenerSalasDelLocal($conexion);
$tiposModulo = obtenerTiposModuloDelLocal($conexion);
$modulos = obtenerModulosDelLocal($conexion);

$mensajes = [
    'guardado' => ['correcto', 'Los cambios de la estructura del local se guardaron.'],
    'estado_actualizado' => ['correcto', 'El estado del elemento se actualizó.'],
    'datos_invalidos' => ['error', 'Revisa los datos introducidos.'],
    'zona_no_disponible' => ['error', 'La zona está archivada. Restáurala antes de asignar una sala.'],
    'destino_no_disponible' => ['error', 'La sala, su zona o el tipo de módulo no está activo.'],
    'tiene_ocupaciones_abiertas' => ['error', 'No se puede archivar un recurso mientras tenga una ocupación abierta.'],
    'ocupacion_supera_capacidad' => ['error', 'La capacidad nueva es inferior a los jugadores de una ocupación abierta.'],
    'duplicado' => ['error', 'Ya existe un elemento con ese nombre o código.'],
    'no_encontrado' => ['error', 'No se encontró el elemento solicitado.'],
    'recurso_no_archivado' => ['error', 'Solo se pueden eliminar definitivamente elementos archivados.'],
    'zona_eliminada' => ['correcto', 'La zona y todos sus registros relacionados se eliminaron definitivamente.'],
    'sala_eliminada' => ['correcto', 'La sala y todos sus registros relacionados se eliminaron definitivamente.'],
    'modulo_eliminado' => ['correcto', 'El módulo y todos sus registros relacionados se eliminaron definitivamente.'],
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
    <title>Gestión del local | Mario DAW World</title>
    <link rel="icon" type="image/png" href="../logo/logo.png">
    <link rel="stylesheet" href="../estilos/css/estilos.css?v=20261110-filtros">
</head>
<body class="pagina-aplicacion">
    <?php require __DIR__ . '/header.php'; ?>

    <main class="contenedor">
        <header class="encabezado-pagina">
            <div>
                <p class="etiqueta">CONFIGURACIÓN DEL GAMING CENTER</p>
                <h1>Gestión del local</h1>
                <p class="texto-secundario">Administra zonas, salas, tipos de módulo y módulos.</p>
            </div>
        </header>

        <?php if ($mensajeActual !== null): ?>
            <p class="aviso aviso-<?= $mensajeActual[0] ?>" role="status"><?= htmlspecialchars($mensajeActual[1], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <section class="bloque">
            <div class="titulo-seccion">
                <div><p class="etiqueta">DISTRIBUCIÓN</p><h2>Zonas</h2></div>
                <span class="texto-secundario"><?= count(array_filter($zonas, static fn ($zona) => (int) $zona['activo'] === 1)) ?> activas</span>
            </div>
            <?php $mostrarEstadoZonaFiltro = true; require __DIR__ . '/filtros/filtro_zonas.php'; ?>
            <form class="formulario-gestion formulario-creacion" action="../procesos/guardar_estructura_local.php" method="post" novalidate data-validacion onsubmit="return validarFormulario(this, event)" oninput="limpiarErrorCampo(event.target)" onchange="limpiarErrorCampo(event.target)">
                <input type="hidden" name="entidad" value="zona">
                <div class="campo">
                    <label for="nueva-zona">Nombre de zona</label>
                    <input id="nueva-zona" name="nombre" maxlength="50">
                </div>
                <button class="boton boton-principal" type="submit">Crear zona</button>
            </form>
            <div class="tabla-desplazable">
                <table class="tabla-datos tabla-gestion">
                    <thead><tr><th>Zona</th><th>Estado</th><th>Editar</th><th>Archivar</th><th>Eliminar</th></tr></thead>
                    <tbody>
                        <?php foreach ($zonas as $zona): ?>
                            <tr data-filtro-item="zonas" data-filtro-texto="<?= htmlspecialchars($zona['nombre'], ENT_QUOTES, 'UTF-8') ?>" data-filtro-estado="<?= (int) $zona['activo'] === 1 ? 'activo' : 'archivado' ?>">
                                <td>
                                    <form class="formulario-gestion" id="editar-zona-<?= (int) $zona['id_zona'] ?>" action="../procesos/guardar_estructura_local.php" method="post" novalidate data-validacion onsubmit="return validarFormulario(this, event)" oninput="limpiarErrorCampo(event.target)" onchange="limpiarErrorCampo(event.target)">
                                        <input type="hidden" name="entidad" value="zona">
                                        <input type="hidden" name="id" value="<?= (int) $zona['id_zona'] ?>">
                                        <label class="solo-lectura" for="nombre-zona-<?= (int) $zona['id_zona'] ?>">Nombre de la zona</label>
                                        <input id="nombre-zona-<?= (int) $zona['id_zona'] ?>" name="nombre" value="<?= htmlspecialchars($zona['nombre'], ENT_QUOTES, 'UTF-8') ?>" maxlength="50">
                                    </form>
                                </td>
                                <td><?= (int) $zona['activo'] === 1 ? 'Activa' : 'Archivada' ?></td>
                                <td><button class="boton boton-secundario" form="editar-zona-<?= (int) $zona['id_zona'] ?>" type="submit">Guardar nombre</button></td>
                                <td class="acciones-estructura-local">
                                    <form class="formulario-estado" action="../procesos/actualizar_estado_zona.php" method="post" data-confirmacion="<?= (int) $zona['activo'] === 1 ? '¿Archivar esta zona y sus salas y módulos?' : '¿Restaurar esta zona? Las salas y módulos se restauran por separado.' ?>" onsubmit="return confirmarEnvio(this, event)">
                                        <input type="hidden" name="entidad" value="zona">
                                        <input type="hidden" name="id" value="<?= (int) $zona['id_zona'] ?>">
                                        <button class="boton <?= (int) $zona['activo'] === 1 ? 'boton-peligro' : 'boton-secundario' ?>" type="submit"><?= (int) $zona['activo'] === 1 ? 'Archivar' : 'Restaurar' ?></button>
                                    </form>
                                </td>
                                <td class="acciones-estructura-local">
                                    <?php if ((int) $zona['activo'] === 0): ?>
                                        <form class="formulario-estado" action="../procesos/eliminar_zona.php" method="post" data-confirmacion="Esta acción borrará definitivamente la zona, sus salas, módulos, puestos y todo su historial, incluidas las ocupaciones abiertas. No se puede deshacer." onsubmit="return confirmarEnvio(this, event)">
                                            <input type="hidden" name="id" value="<?= (int) $zona['id_zona'] ?>">
                                            <button class="boton boton-peligro" type="submit">Eliminar definitivamente</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="texto-secundario">Archiva para habilitar</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bloque">
            <div class="titulo-seccion"><div><p class="etiqueta">ESPACIOS</p><h2>Salas</h2></div></div>
            <?php
            $opcionesZonaFiltro = [];
            foreach ($zonas as $zona) {
                $opcionesZonaFiltro[(int) $zona['id_zona']] = $zona['nombre'];
            }
            require __DIR__ . '/filtros/filtro_salas.php';
            ?>
            <form class="formulario-gestion formulario-creacion" action="../procesos/guardar_estructura_local.php" method="post" novalidate data-validacion onsubmit="return validarFormulario(this, event)" oninput="limpiarErrorCampo(event.target)" onchange="limpiarErrorCampo(event.target)">
                <input type="hidden" name="entidad" value="sala">
                <div class="campo">
                    <label for="nueva-sala">Nombre de sala</label>
                    <input id="nueva-sala" name="nombre" maxlength="50">
                </div>
                <div class="campo">
                    <label for="zona-nueva-sala">Zona</label>
                    <select id="zona-nueva-sala" name="id_zona">
                        <option value="">Selecciona una zona activa</option>
                        <?php foreach ($zonas as $zona): ?>
                            <?php if ((int) $zona['activo'] === 1): ?>
                                <option value="<?= (int) $zona['id_zona'] ?>"><?= htmlspecialchars($zona['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="boton boton-principal" type="submit">Crear sala</button>
            </form>
            <div class="tabla-desplazable">
                <table class="tabla-datos tabla-gestion">
                    <thead><tr><th>Sala</th><th>Zona</th><th>Módulos</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($salas as $sala): ?>
                            <tr data-filtro-item="salas" data-filtro-texto="<?= htmlspecialchars($sala['nombre'] . ' ' . $sala['zona'], ENT_QUOTES, 'UTF-8') ?>" data-filtro-zona="<?= (int) $sala['id_zona'] ?>" data-filtro-estado="<?= (int) $sala['activo'] === 1 ? 'activo' : 'archivado' ?>">
                                <td>
                                    <form class="formulario-gestion" id="editar-sala-<?= (int) $sala['id_sala'] ?>" action="../procesos/guardar_estructura_local.php" method="post" novalidate data-validacion onsubmit="return validarFormulario(this, event)" oninput="limpiarErrorCampo(event.target)" onchange="limpiarErrorCampo(event.target)">
                                        <input type="hidden" name="entidad" value="sala">
                                        <input type="hidden" name="id" value="<?= (int) $sala['id_sala'] ?>">
                                        <label class="solo-lectura" for="nombre-sala-<?= (int) $sala['id_sala'] ?>">Nombre de sala</label>
                                        <input id="nombre-sala-<?= (int) $sala['id_sala'] ?>" name="nombre" value="<?= htmlspecialchars($sala['nombre'], ENT_QUOTES, 'UTF-8') ?>" maxlength="50">
                                        <label class="solo-lectura" for="zona-sala-<?= (int) $sala['id_sala'] ?>">Zona de sala</label>
                                        <select id="zona-sala-<?= (int) $sala['id_sala'] ?>" name="id_zona">
                                            <?php foreach ($zonas as $zona): ?>
                                                <?php if ((int) $zona['activo'] === 1 || (int) $zona['id_zona'] === (int) $sala['id_zona']): ?>
                                                    <option value="<?= (int) $zona['id_zona'] ?>" <?= (int) $zona['id_zona'] === (int) $sala['id_zona'] ? 'selected' : '' ?> <?= (int) $zona['activo'] === 0 ? 'disabled' : '' ?>>
                                                        <?= htmlspecialchars($zona['nombre'], ENT_QUOTES, 'UTF-8') ?><?= (int) $zona['activo'] === 0 ? ' (archivada)' : '' ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td><?= htmlspecialchars($sala['zona'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $sala['modulos'] ?></td>
                                <td><?= (int) $sala['activo'] === 1 ? 'Activa' : 'Archivada' ?></td>
                                <td class="acciones-estructura-local">
                                    <button class="boton boton-secundario" form="editar-sala-<?= (int) $sala['id_sala'] ?>" type="submit">Guardar</button>
                                    <form class="formulario-estado" action="../procesos/actualizar_estado_sala.php" method="post" data-confirmacion="<?= (int) $sala['activo'] === 1 ? '¿Archivar esta sala? Las ocupaciones históricas se conservarán.' : '¿Restaurar esta sala? Sus módulos se restauran por separado.' ?>" onsubmit="return confirmarEnvio(this, event)">
                                        <input type="hidden" name="entidad" value="sala">
                                        <input type="hidden" name="id" value="<?= (int) $sala['id_sala'] ?>">
                                        <button class="boton <?= (int) $sala['activo'] === 1 ? 'boton-peligro' : 'boton-secundario' ?>" type="submit"><?= (int) $sala['activo'] === 1 ? 'Archivar' : 'Restaurar' ?></button>
                                    </form>
                                    <?php if ((int) $sala['activo'] === 0): ?>
                                        <form class="formulario-estado" action="../procesos/eliminar_sala.php" method="post" data-confirmacion="Esta acción borrará definitivamente la sala, sus módulos, puestos y todo su historial, incluidas las ocupaciones abiertas. No se puede deshacer." onsubmit="return confirmarEnvio(this, event)">
                                            <input type="hidden" name="id" value="<?= (int) $sala['id_sala'] ?>">
                                            <button class="boton boton-peligro" type="submit">Eliminar definitivamente</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bloque">
            <div class="titulo-seccion"><div><p class="etiqueta">CAPACIDAD Y PUESTOS</p><h2>Tipos de módulo</h2></div></div>
            <?php require __DIR__ . '/filtros/filtro_tipos_modulo.php'; ?>
            <form class="formulario-gestion formulario-creacion" action="../procesos/guardar_estructura_local.php" method="post" novalidate data-validacion onsubmit="return validarFormulario(this, event)" oninput="limpiarErrorCampo(event.target)" onchange="limpiarErrorCampo(event.target)">
                <input type="hidden" name="entidad" value="tipo_modulo">
                <div class="campo"><label for="nuevo-tipo">Nombre del tipo</label><input id="nuevo-tipo" name="nombre" maxlength="40"></div>
                <div class="campo"><label for="capacidad-tipo">Puestos por módulo</label><input id="capacidad-tipo" name="capacidad" type="number" min="1" max="255"></div>
                <button class="boton boton-principal" type="submit">Crear tipo</button>
            </form>
            <div class="tabla-desplazable">
                <table class="tabla-datos tabla-gestion">
                    <thead><tr><th>Tipo</th><th>Capacidad</th><th>Módulos</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($tiposModulo as $tipo): ?>
                            <tr data-filtro-item="tipos-modulo" data-filtro-texto="<?= htmlspecialchars($tipo['nombre'] . ' ' . $tipo['capacidad'], ENT_QUOTES, 'UTF-8') ?>" data-filtro-estado="<?= (int) $tipo['activo'] === 1 ? 'activo' : 'archivado' ?>">
                                <td>
                                    <form class="formulario-gestion" id="editar-tipo-<?= (int) $tipo['id_tipo_modulo'] ?>" action="../procesos/guardar_estructura_local.php" method="post" novalidate data-validacion onsubmit="return validarFormulario(this, event)" oninput="limpiarErrorCampo(event.target)" onchange="limpiarErrorCampo(event.target)">
                                        <input type="hidden" name="entidad" value="tipo_modulo">
                                        <input type="hidden" name="id" value="<?= (int) $tipo['id_tipo_modulo'] ?>">
                                        <label class="solo-lectura" for="nombre-tipo-<?= (int) $tipo['id_tipo_modulo'] ?>">Nombre del tipo</label>
                                        <input id="nombre-tipo-<?= (int) $tipo['id_tipo_modulo'] ?>" name="nombre" value="<?= htmlspecialchars($tipo['nombre'], ENT_QUOTES, 'UTF-8') ?>" maxlength="40">
                                        <label class="solo-lectura" for="capacidad-tipo-<?= (int) $tipo['id_tipo_modulo'] ?>">Capacidad</label>
                                        <input id="capacidad-tipo-<?= (int) $tipo['id_tipo_modulo'] ?>" name="capacidad" type="number" min="1" max="255" value="<?= (int) $tipo['capacidad'] ?>">
                                    </form>
                                </td>
                                <td><?= (int) $tipo['capacidad'] ?> puestos</td>
                                <td><?= (int) $tipo['modulos'] ?></td>
                                <td><?= (int) $tipo['activo'] === 1 ? 'Activo' : 'Archivado' ?></td>
                                <td class="acciones-estructura-local">
                                    <button class="boton boton-secundario" form="editar-tipo-<?= (int) $tipo['id_tipo_modulo'] ?>" type="submit">Guardar</button>
                                    <form class="formulario-estado" action="../procesos/actualizar_estado_tipo_modulo.php" method="post" data-confirmacion="<?= (int) $tipo['activo'] === 1 ? '¿Archivar este tipo y retirar sus módulos de la vista operativa?' : '¿Restaurar este tipo? Los módulos se restauran por separado.' ?>" onsubmit="return confirmarEnvio(this, event)">
                                        <input type="hidden" name="entidad" value="tipo_modulo">
                                        <input type="hidden" name="id" value="<?= (int) $tipo['id_tipo_modulo'] ?>">
                                        <button class="boton <?= (int) $tipo['activo'] === 1 ? 'boton-peligro' : 'boton-secundario' ?>" type="submit"><?= (int) $tipo['activo'] === 1 ? 'Archivar' : 'Restaurar' ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        </section>

        <section class="bloque">
            <div class="titulo-seccion"><div><p class="etiqueta">INVENTARIO</p><h2>Módulos</h2></div></div>
            <?php require __DIR__ . '/filtros/filtro_modulos_local.php'; ?>
            <form class="formulario-gestion formulario-creacion" action="../procesos/guardar_estructura_local.php" method="post" novalidate data-validacion onsubmit="return validarFormulario(this, event)" oninput="limpiarErrorCampo(event.target)" onchange="limpiarErrorCampo(event.target)">
                <input type="hidden" name="entidad" value="modulo">
                <div class="campo"><label for="codigo-nuevo-modulo">Código</label><input id="codigo-nuevo-modulo" name="codigo" maxlength="10" pattern="[A-Za-z0-9][A-Za-z0-9._-]*"></div>
                <div class="campo">
                    <label for="sala-nuevo-modulo">Sala activa</label>
                    <select id="sala-nuevo-modulo" name="id_sala">
                        <option value="">Selecciona una sala</option>
                        <?php foreach ($salas as $sala): ?>
                            <?php if ((int) $sala['activo'] === 1): ?>
                                <option value="<?= (int) $sala['id_sala'] ?>"><?= htmlspecialchars($sala['zona'] . ' · ' . $sala['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="tipo-nuevo-modulo">Tipo activo</label>
                    <select id="tipo-nuevo-modulo" name="id_tipo_modulo">
                        <option value="">Selecciona un tipo</option>
                        <?php foreach ($tiposModulo as $tipo): ?>
                            <?php if ((int) $tipo['activo'] === 1): ?>
                                <option value="<?= (int) $tipo['id_tipo_modulo'] ?>"><?= htmlspecialchars($tipo['nombre'], ENT_QUOTES, 'UTF-8') ?> · <?= (int) $tipo['capacidad'] ?> puestos</option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="boton boton-principal" type="submit">Crear módulo</button>
            </form>
            <div class="tabla-desplazable">
                <table class="tabla-datos tabla-gestion">
                    <thead><tr><th>Módulo</th><th>Sala y zona</th><th>Tipo</th><th>Puestos</th><th>Historial</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($modulos as $modulo): ?>
                            <tr data-filtro-item="modulos-local" data-filtro-texto="<?= htmlspecialchars($modulo['codigo'] . ' ' . $modulo['sala'] . ' ' . $modulo['zona'] . ' ' . $modulo['tipo_modulo'], ENT_QUOTES, 'UTF-8') ?>" data-filtro-relacion="<?= (int) $modulo['id_sala'] ?>" data-filtro-estado="<?= (int) $modulo['activo'] === 1 ? 'activo' : 'archivado' ?>">
                                <td>
                                    <form class="formulario-gestion" id="editar-modulo-<?= (int) $modulo['id_modulo'] ?>" action="../procesos/guardar_estructura_local.php" method="post" novalidate data-validacion onsubmit="return validarFormulario(this, event)" oninput="limpiarErrorCampo(event.target)" onchange="limpiarErrorCampo(event.target)">
                                        <input type="hidden" name="entidad" value="modulo">
                                        <input type="hidden" name="id" value="<?= (int) $modulo['id_modulo'] ?>">
                                        <label class="solo-lectura" for="codigo-modulo-<?= (int) $modulo['id_modulo'] ?>">Código de módulo</label>
                                        <input id="codigo-modulo-<?= (int) $modulo['id_modulo'] ?>" name="codigo" value="<?= htmlspecialchars($modulo['codigo'], ENT_QUOTES, 'UTF-8') ?>" maxlength="10" pattern="[A-Za-z0-9][A-Za-z0-9._-]*">
                                        <label class="solo-lectura" for="sala-modulo-<?= (int) $modulo['id_modulo'] ?>">Sala</label>
                                        <select id="sala-modulo-<?= (int) $modulo['id_modulo'] ?>" name="id_sala">
                                            <?php foreach ($salas as $sala): ?>
                                                <?php if ((int) $sala['activo'] === 1 || (int) $sala['id_sala'] === (int) $modulo['id_sala']): ?>
                                                    <option value="<?= (int) $sala['id_sala'] ?>" <?= (int) $sala['id_sala'] === (int) $modulo['id_sala'] ? 'selected' : '' ?> <?= (int) $sala['activo'] === 0 ? 'disabled' : '' ?>>
                                                        <?= htmlspecialchars($sala['zona'] . ' · ' . $sala['nombre'], ENT_QUOTES, 'UTF-8') ?><?= (int) $sala['activo'] === 0 ? ' (archivada)' : '' ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                        <label class="solo-lectura" for="tipo-modulo-<?= (int) $modulo['id_modulo'] ?>">Tipo de módulo</label>
                                        <select id="tipo-modulo-<?= (int) $modulo['id_modulo'] ?>" name="id_tipo_modulo">
                                            <?php foreach ($tiposModulo as $tipo): ?>
                                                <?php if ((int) $tipo['activo'] === 1 || (int) $tipo['id_tipo_modulo'] === (int) $modulo['id_tipo_modulo']): ?>
                                                    <option value="<?= (int) $tipo['id_tipo_modulo'] ?>" <?= (int) $tipo['id_tipo_modulo'] === (int) $modulo['id_tipo_modulo'] ? 'selected' : '' ?> <?= (int) $tipo['activo'] === 0 ? 'disabled' : '' ?>>
                                                        <?= htmlspecialchars($tipo['nombre'], ENT_QUOTES, 'UTF-8') ?> · <?= (int) $tipo['capacidad'] ?><?= (int) $tipo['activo'] === 0 ? ' (archivado)' : '' ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td><?= htmlspecialchars($modulo['zona'] . ' · ' . $modulo['sala'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($modulo['tipo_modulo'], ENT_QUOTES, 'UTF-8') ?> · <?= (int) $modulo['capacidad'] ?></td>
                                <td><?= (int) $modulo['puestos'] ?></td>
                                <td><?= (int) $modulo['usos'] ?> uso(s)</td>
                                <td><?= (int) $modulo['activo'] === 1 ? 'Activo' : 'Archivado' ?></td>
                                <td class="acciones-estructura-local">
                                    <button class="boton boton-secundario" form="editar-modulo-<?= (int) $modulo['id_modulo'] ?>" type="submit">Guardar</button>
                                    <form class="formulario-estado" action="../procesos/actualizar_estado_modulo.php" method="post" data-confirmacion="<?= (int) $modulo['activo'] === 1 ? '¿Archivar este módulo? El historial asociado se conservará.' : '¿Restaurar este módulo?' ?>" onsubmit="return confirmarEnvio(this, event)">
                                        <input type="hidden" name="entidad" value="modulo">
                                        <input type="hidden" name="id" value="<?= (int) $modulo['id_modulo'] ?>">
                                        <button class="boton <?= (int) $modulo['activo'] === 1 ? 'boton-peligro' : 'boton-secundario' ?>" type="submit"><?= (int) $modulo['activo'] === 1 ? 'Archivar' : 'Restaurar' ?></button>
                                    </form>
                                    <?php if ((int) $modulo['activo'] === 0): ?>
                                        <form class="formulario-estado" action="../procesos/eliminar_modulo.php" method="post" data-confirmacion="Esta acción borrará definitivamente el módulo, sus puestos y todo su historial, incluidas las ocupaciones abiertas. No se puede deshacer." onsubmit="return confirmarEnvio(this, event)">
                                            <input type="hidden" name="id" value="<?= (int) $modulo['id_modulo'] ?>">
                                            <button class="boton boton-peligro" type="submit">Eliminar definitivamente</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
                </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="../estilos/js/app.js?v=20261110-filtros" defer></script>
    </body>
    </html>
