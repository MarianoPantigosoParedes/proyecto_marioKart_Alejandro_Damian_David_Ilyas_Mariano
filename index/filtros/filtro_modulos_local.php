<?php
$salasFiltroModulos = isset($salas) && is_array($salas) ? $salas : [];
?>
<div class="filtro-seccion" data-filtro-seccion="modulos-local">
    <div class="campo">
        <label for="buscar-modulos-local">Buscar módulo</label>
        <input id="buscar-modulos-local" type="search" maxlength="80" placeholder="Código, sala o tipo" oninput="filtrarSeccionesLocal()">
    </div>
    <div class="campo">
        <label for="sala-filtro-modulos">Sala</label>
        <select id="sala-filtro-modulos" data-filtro-relacion onchange="filtrarSeccionesLocal()">
            <option value="">Todas las salas</option>
            <?php foreach ($salasFiltroModulos as $sala): ?>
                <?php if (!is_array($sala)) { continue; } ?>
                <option value="<?= (int) ($sala['id_sala'] ?? 0) ?>">
                    <?= htmlspecialchars((string) ($sala['zona'] ?? '') . ' · ' . (string) ($sala['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo">
        <label for="estado-filtro-modulos">Estado</label>
        <select id="estado-filtro-modulos" data-filtro-estado onchange="filtrarSeccionesLocal()">
            <option value="todos">Todos los estados</option>
            <option value="activos">Activos</option>
            <option value="archivados">Archivados</option>
        </select>
    </div>
    <span class="texto-secundario filtro-conteo" data-filtro-conteo aria-live="polite"></span>
    <button class="boton boton-limpiar" type="button" onclick="limpiarFiltroSeccion(this.parentElement)">Limpiar filtros</button>
</div>
