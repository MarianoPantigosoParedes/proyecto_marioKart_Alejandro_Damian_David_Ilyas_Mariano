<?php

function obtenerModulosDelLocal(PDO $conexion): array
{
    return $conexion->query(
        'SELECT m.id_modulo, m.codigo, m.id_sala, m.id_tipo_modulo, m.activo,
                s.nombre AS sala, z.nombre AS zona, z.activo AS zona_activa, s.activo AS sala_activa,
                tm.nombre AS tipo_modulo, tm.capacidad, tm.activo AS tipo_activo,
                (SELECT COUNT(*) FROM puestos_juego AS p WHERE p.id_modulo = m.id_modulo) AS puestos,
                (SELECT COUNT(*) FROM ocupaciones AS o WHERE o.id_modulo = m.id_modulo) AS usos,
                (SELECT COUNT(*) FROM ocupaciones AS o
                 WHERE o.id_modulo = m.id_modulo AND o.fecha_fin IS NULL) AS ocupaciones_abiertas
         FROM modulos AS m
         INNER JOIN salas AS s ON s.id_sala = m.id_sala
         INNER JOIN zonas AS z ON z.id_zona = s.id_zona
         INNER JOIN tipos_modulo AS tm ON tm.id_tipo_modulo = m.id_tipo_modulo
         ORDER BY m.activo DESC, z.nombre, s.nombre, m.codigo'
    )->fetchAll();
}
