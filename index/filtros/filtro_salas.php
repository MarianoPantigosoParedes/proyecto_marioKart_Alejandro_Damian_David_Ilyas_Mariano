<?php
$opcionesZonaFiltro = isset($opcionesZonaFiltro) && is_array($opcionesZonaFiltro) ? $opcionesZonaFiltro : [];
$mostrarEstadoSalaFiltro = isset($mostrarEstadoSalaFiltro)? (bool) $mostrarEstadoSalaFiltro : true;
?>
<div class="filtro-seccion" data-filtro-seccion="salas">
    <div class="campo">
        <label for="buscar-salas">Buscar sala</label>
        <input id="buscar-salas" type="search" maxlength="50" placeholder="Nombre de sala" oninput="filtrarSeccionesLocal()">
    </div>
    <div class="campo">
        <label for="zona-filtro-salas">Zona</label>
        <select id="zona-filtro-salas" data-filtro-zona onchange="filtrarSeccionesLocal()">
            <option value="">Todas las zonas</option>
            <?php foreach ($opcionesZonaFiltro as $idZonaFiltro => $nombreZonaFiltro): ?>
                <option value="<?= (int) $idZonaFiltro ?>"><?= htmlspecialchars((string) $nombreZonaFiltro, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($mostrarEstadoSalaFiltro): ?>
        <div class="campo">
            <label for="estado-filtro-salas">Estado</label>
            <select id="estado-filtro-salas" data-filtro-estado onchange="filtrarSeccionesLocal()">
                <option value="todos">Todos los estados</option>
                <option value="activos">Activas</option>
                <option value="archivados">Archivadas</option>
            </select>
        </div>
    <?php endif; ?>
    <span class="texto-secundario filtro-conteo" data-filtro-conteo aria-live="polite"></span>
    <button class="boton boton-limpiar" type="button" onclick="limpiarFiltroSeccion(this.parentElement)">Limpiar filtros</button>
</div>
