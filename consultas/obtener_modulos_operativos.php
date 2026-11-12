<?php

function obtenerModulosOperativos(PDO $conexion, ?int $idSala, ?int $idZona, string $estado): array
{
    $sql = '
        SELECT s.id_sala, s.nombre AS sala, s.id_zona, z.nombre AS zona,
               m.id_modulo, m.codigo,
               tm.nombre AS tipo_modulo, tm.capacidad,
               o.id_ocupacion, o.num_jugadores, o.fecha_inicio,
               gm.id_gamemaster AS responsable_id,
               COALESCE(o.nombre_gamemaster_snapshot, gm.nombre) AS responsable_nombre,
               COALESCE(o.apellido_gamemaster_snapshot, gm.apellido) AS responsable_apellido
        FROM modulos AS m
        INNER JOIN salas AS s ON s.id_sala = m.id_sala AND s.activo = 1
        INNER JOIN zonas AS z ON z.id_zona = s.id_zona AND z.activo = 1
        INNER JOIN tipos_modulo AS tm ON tm.id_tipo_modulo = m.id_tipo_modulo
        LEFT JOIN ocupaciones AS o ON o.id_modulo = m.id_modulo AND o.fecha_fin IS NULL
        LEFT JOIN gamemasters AS gm ON gm.id_gamemaster = o.id_gamemaster
        WHERE m.activo = 1';
    $condiciones = [];
    $parametros = [];

    if ($idSala !== null) {
        $condiciones[] = 's.id_sala = :id_sala';
        $parametros['id_sala'] = $idSala;
    }
    if ($idZona !== null) {
        $condiciones[] = 's.id_zona = :id_zona';
        $parametros['id_zona'] = $idZona;
    }
    if ($estado === 'libres') {
        $condiciones[] = 'o.id_ocupacion IS NULL';
    } elseif ($estado === 'ocupados') {
        $condiciones[] = 'o.id_ocupacion IS NOT NULL';
    }
    if ($condiciones) {
        $sql .= ' AND ' . implode(' AND ', $condiciones);
    }
    $sql .= ' ORDER BY s.id_sala, m.codigo';

    $consulta = $conexion->prepare($sql);
    $consulta->execute($parametros);
    return $consulta->fetchAll();
}
