<?php
$zonas = isset($zonas) && is_array($zonas) ? $zonas : [];
$salas = isset($salas) && is_array($salas) ? $salas : [];
$modulos = isset($modulos) && is_array($modulos) ? $modulos : [];
$idZona = isset($idZona) ? (int) $idZona : null;
$idSala = isset($idSala) ? (int) $idSala : null;
$idModulo = isset($idModulo) ? (int) $idModulo : null;
$fechaDesde = isset($fechaDesde) && is_string($fechaDesde) ? $fechaDesde : '';
$fechaHasta = isset($fechaHasta) && is_string($fechaHasta) ? $fechaHasta : '';
?>
<form class="bloque filtros" action="historial.php" method="get" novalidate data-validacion data-filtros-historial onsubmit="return validarFormulario(this, event)" oninput="limpiarErrorCampo(event.target)" onchange="limpiarErrorCampo(event.target)">
    <div class="campo">
        <label for="filtro-zona-historial">Zona</label>
        <select id="filtro-zona-historial" name="zona" data-filtro-zona-historial onchange="ajustarFiltrosHistorial(this.form)">
            <option value="">Todas las zonas</option>
            <?php foreach ($zonas as $idZonaOpcion => $nombreZona): ?>
                <option value="<?= (int) $idZonaOpcion ?>" <?= $idZona === (int) $idZonaOpcion ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $nombreZona, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo">
        <label for="filtro-sala">Sala</label>
        <select id="filtro-sala" name="sala" data-filtro-sala onchange="ajustarFiltrosHistorial(this.form)">
            <option value="">Salas</option>
            <?php foreach ($salas as $sala): ?>
                <option value="<?= $sala['id_sala'] ?>"
                        data-zona="<?= $sala['id_zona'] ?>"
                        <?= $idSala == $sala['id_sala'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sala['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo">
        <label for="filtro-modulo">Módulo</label>
        <select id="filtro-modulo" name="modulo" data-filtro-modulo>
            <option value="">Modulos</option>
            <?php foreach ($modulos as $modulo): ?>
                <option value="<?= $modulo['id_modulo'] ?>"
                        data-sala="<?= $modulo['id_sala'] ?>"
                        data-zona="<?= $modulo['id_zona'] ?>"
                        <?= $idModulo == $modulo['id_modulo'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($modulo['sala'] . ' · ' . $modulo['codigo']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo">
        <label for="fecha-desde">Desde</label>
        <input id="fecha-desde" name="desde" type="date" value="<?= htmlspecialchars($fechaDesde, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="campo">
        <label for="fecha-hasta">Hasta</label>
        <input id="fecha-hasta" name="hasta" type="date" value="<?= htmlspecialchars($fechaHasta, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <button class="boton boton-filtrar" type="submit">Filtrar</button>
    <button class="boton boton-limpiar" type="button" onclick="limpiarFiltroFormulario(this.form)">Limpiar filtros</button>
</form>
