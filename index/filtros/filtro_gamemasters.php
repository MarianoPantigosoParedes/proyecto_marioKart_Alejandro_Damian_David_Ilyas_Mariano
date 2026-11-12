<?php
$busquedaGamemaster = isset($busquedaGamemaster) && is_string($busquedaGamemaster)
    ? $busquedaGamemaster
    : '';
$estadoGamemaster = isset($estadoGamemaster) && is_string($estadoGamemaster)
    && in_array($estadoGamemaster, ['todos', 'activos', 'inactivos'], true)
    ? $estadoGamemaster
    : 'todos';
?>
<form class="bloque filtros filtro-gamemasters" action="gestion_gamemasters.php" method="get">
    <div class="campo">
        <label for="buscar-gamemaster">Buscar cuenta</label>
        <input
            id="buscar-gamemaster"
            name="buscar"
            type="search"
            maxlength="80"
            value="<?= htmlspecialchars($busquedaGamemaster, ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Usuario, nombre o apellido"
        >
    </div>
    <div class="campo">
        <label for="estado-gamemaster">Estado</label>
        <select id="estado-gamemaster" name="estado">
            <option value="todos" <?= $estadoGamemaster === 'todos' ? 'selected' : '' ?>>Todas</option>
            <option value="activos" <?= $estadoGamemaster === 'activos' ? 'selected' : '' ?>>Solo activas</option>
            <option value="inactivos" <?= $estadoGamemaster === 'inactivos' ? 'selected' : '' ?>>Solo desactivadas</option>
        </select>
    </div>
    <button class="boton boton-filtrar" type="submit">Filtrar</button>
    <button class="boton boton-limpiar" type="button" onclick="limpiarFiltroFormulario(this.form)">Limpiar filtros</button>
</form>
