<?php
$zonas = isset($zonas) && is_array($zonas) ? $zonas : [];
$salas = isset($salas) && is_array($salas) ? $salas : [];
$idZonaFiltro = isset($idZonaFiltro) ? (int) $idZonaFiltro : 0;
$idSalaFiltro = isset($idSalaFiltro) ? (int) $idSalaFiltro : 0;
$estadoFiltro = isset($estadoFiltro) && is_string($estadoFiltro) && in_array($estadoFiltro, ['todos', 'libres', 'ocupados'], true) ? $estadoFiltro : 'todos';
?>
<form class="bloque filtros" action="panel_gamemaster.php" method="get" novalidate data-validacion data-filtros-modulos onsubmit="return validarFormulario(this, event)" oninput="limpiarErrorCampo(event.target)" onchange="limpiarErrorCampo(event.target)">
    <div class="campo">
        <label for="filtro-zona">Zona</label>
        <select id="filtro-zona" name="zona" data-filtro-zona onchange="aplicarFiltrosModulos(this.form)">
            <option value="">Todas las zonas</option>
            <?php foreach ($zonas as $idZona => $nombreZona): ?>
                <option value="<?= (int) $idZona ?>" <?= $idZonaFiltro === (int) $idZona ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $nombreZona, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo">
        <label for="filtro-sala">Sala</label>
        <select id="filtro-sala" name="sala" data-filtro-sala onchange="aplicarFiltrosModulos(this.form)">
            <option value="">Todas las salas</option>
            <?php foreach ($salas as $sala): ?>
                <?php if (!is_array($sala)) { continue; } ?>
                <option value="<?= (int) ($sala['id_sala'] ?? 0) ?>" data-zona="<?= (int) ($sala['id_zona'] ?? 0) ?>" <?= $idSalaFiltro === (int) ($sala['id_sala'] ?? 0) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) ($sala['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo">
        <label for="filtro-estado">Disponibilidad</label>
        <select id="filtro-estado" name="estado" onchange="aplicarFiltrosModulos(this.form)">
            <option value="todos" <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>>Todos los módulos</option>
            <option value="libres" <?= $estadoFiltro === 'libres' ? 'selected' : '' ?>>Solo libres</option>
            <option value="ocupados" <?= $estadoFiltro === 'ocupados' ? 'selected' : '' ?>>Solo ocupados</option>
        </select>
    </div>
    <button class="boton boton-filtrar" type="submit">Filtrar</button>
    <button class="boton boton-limpiar" type="button" onclick="limpiarFiltroFormulario(this.form)">Limpiar filtros</button>
</form>
