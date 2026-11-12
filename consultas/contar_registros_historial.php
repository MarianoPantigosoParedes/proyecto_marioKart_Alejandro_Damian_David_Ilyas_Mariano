<?php

require_once __DIR__ . '/construir_condiciones_historial.php';

function contarRegistrosHistorial(
    PDO $conexion,
    ?int $idZona,
    ?int $idSala,
    ?int $idModulo,
    string $fechaDesde,
    string $fechaHasta,
    bool $errorFiltros
): int {
    $condiciones = construirCondicionesHistorial(
        $idZona,
        $idSala,
        $idModulo,
        $fechaDesde,
        $fechaHasta,
        $errorFiltros
    );

    $sql = 'SELECT COUNT(*)
            FROM ocupaciones AS o
            INNER JOIN gamemasters AS g ON g.id_gamemaster = o.id_gamemaster';

    $sql .= $condiciones['where'];

    $consulta = $conexion->prepare($sql);
    $consulta->execute($condiciones['parametros']);

    return (int) $consulta->fetchColumn();
}
