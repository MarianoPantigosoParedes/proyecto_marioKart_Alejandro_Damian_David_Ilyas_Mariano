<?php
$mostrarEstadoZonaFiltro = isset($mostrarEstadoZonaFiltro) ? (bool) $mostrarEstadoZonaFiltro : true;
?>
<div class="filtro-seccion" data-filtro-seccion="zonas">
    <div class="campo">
        <label for="buscar-zonas">Buscar zona</label>
        <input id="buscar-zonas" type="search" maxlength="50" placeholder="Nombre de zona" oninput="filtrarSeccionesLocal()">
    </div>
    <?php if ($mostrarEstadoZonaFiltro): ?>
        <div class="campo">
            <label for="estado-zonas">Estado</label>
            <select id="estado-zonas" data-filtro-estado onchange="filtrarSeccionesLocal()">
                <option value="todos">Todos los estados</option>
                <option value="activos">Activas</option>
                <option value="archivados">Archivadas</option>
            </select>
        </div>
    <?php endif; ?>
    <span class="texto-secundario filtro-conteo" data-filtro-conteo aria-live="polite"></span>
    <button class="boton boton-limpiar" type="button" onclick="limpiarFiltroSeccion(this.parentElement)">Limpiar filtros</button>
</div>
