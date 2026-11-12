<?php

function construirCondicionesHistorial(
    ?int $idZonaFiltro,
    ?int $idSalaFiltro,
    ?int $idModuloFiltro,
    string $fechaInicio,
    string $fechaFin,
    bool $hayErroresEnFiltros
): array {
    if ($hayErroresEnFiltros) {
        return [
            'where' => '',
            'parametros' => [],
        ];
    }

    $condiciones = [];
    $parametros = [];

    if ($idZonaFiltro !== null) {
        $condiciones[] = 'o.id_zona_snapshot = :id_zona';
        $parametros['id_zona'] = $idZonaFiltro;
    }

    if ($idSalaFiltro !== null) {
        $condiciones[] = 'o.id_sala_snapshot = :id_sala';
        $parametros['id_sala'] = $idSalaFiltro;
    }

    if ($idModuloFiltro !== null) {
        $condiciones[] = 'o.id_modulo = :id_modulo';
        $parametros['id_modulo'] = $idModuloFiltro;
    }

    if ($fechaInicio !== '') {
        $condiciones[] = 'o.fecha_inicio >= :fecha_inicio';
        $parametros['fecha_inicio'] = $fechaInicio . ' 00:00:00';
    }

    if ($fechaFin !== '') {
        $condiciones[] = 'o.fecha_inicio < DATE_ADD(:fecha_fin, INTERVAL 1 DAY)';
        $parametros['fecha_fin'] = $fechaFin;
    }

    $where = '';
    if (count($condiciones) > 0) {
        $where = ' WHERE ' . implode(' AND ', $condiciones);
    }

    return [
        'where' => $where,
        'parametros' => $parametros,
    ];
}
