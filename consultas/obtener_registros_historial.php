<?php

require_once __DIR__ . '/construir_condiciones_historial.php';

function obtenerRegistrosHistorial(
    PDO $conexion,
    ?int $idZona,
    ?int $idSala,
    ?int $idModulo,
    string $fechaDesde,
    string $fechaHasta,
    bool $errorFiltros,
    int $limite,
    int $desplazamiento
): array {
    $condiciones = construirCondicionesHistorial(
        $idZona,
        $idSala,
        $idModulo,
        $fechaDesde,
        $fechaHasta,
        $errorFiltros
    );

    $sql = 'SELECT o.id_ocupacion, o.num_jugadores, o.fecha_inicio, o.fecha_fin,
                   o.codigo_modulo_snapshot AS modulo,
                   o.nombre_sala_snapshot AS sala,
                   o.nombre_zona_snapshot AS zona,
                   o.tipo_modulo_snapshot AS tipo_modulo,
                   COALESCE(o.nombre_gamemaster_snapshot, g.nombre) AS gamemaster_nombre,
                   COALESCE(o.apellido_gamemaster_snapshot, g.apellido) AS gamemaster_apellido
            FROM ocupaciones AS o
            INNER JOIN gamemasters AS g ON g.id_gamemaster = o.id_gamemaster';

    $sql .= $condiciones['where'];
    $sql .= ' ORDER BY o.fecha_inicio DESC, o.id_ocupacion DESC
              LIMIT :limite OFFSET :desplazamiento';

    $consulta = $conexion->prepare($sql);

    foreach ($condiciones['parametros'] as $nombre => $valor) {
        $consulta->bindValue(':' . $nombre, $valor);
    }

    $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
    $consulta->bindValue(':desplazamiento', $desplazamiento, PDO::PARAM_INT);
    $consulta->execute();

    return $consulta->fetchAll();
}
