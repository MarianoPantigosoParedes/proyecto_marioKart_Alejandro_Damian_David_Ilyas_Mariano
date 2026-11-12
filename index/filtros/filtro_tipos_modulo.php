<div class="filtro-seccion" data-filtro-seccion="tipos-modulo">
    <div class="campo">
        <label for="buscar-tipos-modulo">Buscar tipo</label>
        <input id="buscar-tipos-modulo" type="search" maxlength="40" placeholder="Nombre del tipo" oninput="filtrarSeccionesLocal()">
    </div>
    <div class="campo">
        <label for="estado-tipos-modulo">Estado</label>
        <select id="estado-tipos-modulo" data-filtro-estado onchange="filtrarSeccionesLocal()">
            <option value="todos">Todos los estados</option>
            <option value="activos">Activos</option>
            <option value="archivados">Archivados</option>
        </select>
    </div>
    <span class="texto-secundario filtro-conteo" data-filtro-conteo aria-live="polite"></span>
    <button class="boton boton-limpiar" type="button" onclick="limpiarFiltroSeccion(this.parentElement)">Limpiar filtros</button>
</div>
