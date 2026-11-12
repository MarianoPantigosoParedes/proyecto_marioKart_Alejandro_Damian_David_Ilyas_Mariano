<?php

require_once __DIR__ . '/../config/sesion.php';

iniciarSesion();

if (
    !isset($_SESSION['usuario']['id_gamemaster'], $_SESSION['usuario']['rol'])
    || !in_array($_SESSION['usuario']['rol'], ['admin', 'gamemaster'], true)
) {
    header('Location: ../index/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index/gestion_estructura_local.php');
    exit;
}

$entidadRecibida = $_POST['entidad'] ?? '';
$entidad = is_string($entidadRecibida) ? $entidadRecibida : '';
$id = filter_var($_POST['id'] ?? '0', FILTER_VALIDATE_INT);
$nombreRecibido = $_POST['nombre'] ?? '';
$codigoRecibido = $_POST['codigo'] ?? '';
$nombre = is_string($nombreRecibido) ? trim($nombreRecibido) : '';
$codigo = is_string($codigoRecibido) ? strtoupper(trim($codigoRecibido)) : '';
$capacidad = filter_var($_POST['capacidad'] ?? null, FILTER_VALIDATE_INT);
$idZona = filter_var($_POST['id_zona'] ?? null, FILTER_VALIDATE_INT);
$idSala = filter_var($_POST['id_sala'] ?? null, FILTER_VALIDATE_INT);
$idTipoModulo = filter_var($_POST['id_tipo_modulo'] ?? null, FILTER_VALIDATE_INT);

$volver = '../index/gestion_estructura_local.php';
$nombreValido = $nombre !== ''
    && preg_match('//u', $nombre) === 1
    && mb_strlen($nombre, 'UTF-8') <= 50
    && preg_match('/[\x00-\x1F\x7F]/u', $nombre) === 0;

if (
    $id === false
    || $id < 0
    || !in_array($entidad, ['zona', 'sala', 'tipo_modulo', 'modulo'], true)
    || ($entidad === 'zona' && !$nombreValido)
    || ($entidad === 'sala' && (!$nombreValido || $idZona === false || $idZona < 1))
    || ($entidad === 'tipo_modulo' && (
        !$nombreValido
        || mb_strlen($nombre, 'UTF-8') > 40
        || $capacidad === false
        || $capacidad < 1
        || $capacidad > 255
    ))
    || ($entidad === 'modulo' && (
        $codigo === ''
        || strlen($codigo) > 10
        || !preg_match('/^[A-Z0-9][A-Z0-9._-]*$/', $codigo)
        || $idSala === false
        || $idSala < 1
        || $idTipoModulo === false
        || $idTipoModulo < 1
    ))
) {
    header('Location: ' . $volver . '?resultado=datos_invalidos');
    exit;
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/usuario_activo.php';

if (!usuarioSesionActivo($conexion)) {
    cerrarSesionPorCuentaInactiva();
}

try {
    $conexion->beginTransaction();

    if ($id > 0) {
        $destinos = [
            'zona' => ['zonas', 'id_zona'],
            'sala' => ['salas', 'id_sala'],
            'tipo_modulo' => ['tipos_modulo', 'id_tipo_modulo'],
            'modulo' => ['modulos', 'id_modulo'],
        ];
        [$tabla, $columnaId] = $destinos[$entidad];
        $consultaElemento = $conexion->prepare(
            "SELECT {$columnaId} FROM {$tabla} WHERE {$columnaId} = :id FOR UPDATE"
        );
        $consultaElemento->execute(['id' => $id]);
        if (!$consultaElemento->fetch()) {
            $conexion->rollBack();
            header('Location: ' . $volver . '?resultado=no_encontrado' );
            exit;
        }
    }

    if ($entidad === 'zona') {
        if ($id > 0) {
            $consulta = $conexion->prepare('UPDATE zonas SET nombre = :nombre WHERE id_zona = :id_zona');
            $consulta->execute(['nombre' => $nombre, 'id_zona' => $id]);
        } else {
            $consulta = $conexion->prepare('INSERT INTO zonas (nombre) VALUES (:nombre)');
            $consulta->execute(['nombre' => $nombre]);
        }
    } elseif ($entidad === 'sala') {
        $consultaZona = $conexion->prepare('SELECT id_zona FROM zonas WHERE id_zona = :id_zona AND activo = 1 FOR UPDATE');
        $consultaZona->execute(['id_zona' => $idZona]);
        if (!$consultaZona->fetch()) {
            $conexion->rollBack();
            header('Location: ' . $volver . '?resultado=zona_no_disponible' );
            exit;
        }

        if ($id > 0) {
            $consultaOcupacion = $conexion->prepare(
                'SELECT o.id_ocupacion
                 FROM ocupaciones AS o
                 INNER JOIN modulos AS m ON m.id_modulo = o.id_modulo
                 WHERE m.id_sala = :id_sala
                   AND o.fecha_fin IS NULL
                   AND m.activo = 1
                 LIMIT 1
                 FOR UPDATE'
            );
            $consultaOcupacion->execute(['id_sala' => $id]);
            if ($consultaOcupacion->fetch()) {
                $salaActual = $conexion->prepare('SELECT id_zona FROM salas WHERE id_sala = :id_sala');
                $salaActual->execute(['id_sala' => $id]);
                if ((int) $salaActual->fetchColumn() !== $idZona) {
                    $conexion->rollBack();
                    header('Location: ' . $volver . '?resultado=tiene_ocupaciones_abiertas' );
                    exit;
                }
            }
            $consulta = $conexion->prepare('UPDATE salas SET nombre = :nombre, id_zona = :id_zona WHERE id_sala = :id_sala');
            $consulta->execute(['nombre' => $nombre, 'id_zona' => $idZona, 'id_sala' => $id]);
        } else {
            $consulta = $conexion->prepare('INSERT INTO salas (nombre, id_zona) VALUES (:nombre, :id_zona)');
            $consulta->execute(['nombre' => $nombre, 'id_zona' => $idZona]);
        }
    } elseif ($entidad === 'tipo_modulo') {
        if ($id > 0) {
            $consultaTipo = $conexion->prepare(
                'SELECT id_tipo_modulo FROM tipos_modulo WHERE id_tipo_modulo = :id_tipo_modulo FOR UPDATE'
            );
            $consultaTipo->execute(['id_tipo_modulo' => $id]);
            if (!$consultaTipo->fetch()) {
                $conexion->rollBack();
                header('Location: ' . $volver . '?resultado=no_encontrado' );
                exit;
            }

            $consultaOcupacion = $conexion->prepare(
                'SELECT o.id_ocupacion
                 FROM ocupaciones AS o
                 INNER JOIN modulos AS m ON m.id_modulo = o.id_modulo
                 WHERE m.id_tipo_modulo = :id_tipo_modulo
                   AND o.fecha_fin IS NULL
                   AND o.num_jugadores > :capacidad
                 LIMIT 1
                 FOR UPDATE'
            );
            $consultaOcupacion->execute(['id_tipo_modulo' => $id, 'capacidad' => $capacidad]);
            if ($consultaOcupacion->fetch()) {
                $conexion->rollBack();
                header('Location: ' . $volver . '?resultado=ocupacion_supera_capacidad' );
                exit;
            }

            $actualizarTipo = $conexion->prepare(
                'UPDATE tipos_modulo SET nombre = :nombre, capacidad = :capacidad WHERE id_tipo_modulo = :id_tipo_modulo'
            );
            $actualizarTipo->execute(['nombre' => $nombre, 'capacidad' => $capacidad, 'id_tipo_modulo' => $id]);

            $consultaModulos = $conexion->prepare(
                'SELECT id_modulo FROM modulos WHERE id_tipo_modulo = :id_tipo_modulo ORDER BY id_modulo FOR UPDATE'
            );
            $consultaModulos->execute(['id_tipo_modulo' => $id]);
            $puestosModulo = $conexion->prepare(
                'SELECT id_puesto FROM puestos_juego WHERE id_modulo = :id_modulo ORDER BY id_puesto DESC'
            );
            $borrarPuesto = $conexion->prepare('DELETE FROM puestos_juego WHERE id_puesto = :id_puesto');
            $crearPuesto = $conexion->prepare(
                'INSERT INTO puestos_juego (codigo, id_modulo) VALUES (:codigo, :id_modulo)'
            );

            foreach ($consultaModulos->fetchAll(PDO::FETCH_COLUMN) as $idModulo) {
                $puestosModulo->execute(['id_modulo' => $idModulo]);
                $puestos = $puestosModulo->fetchAll(PDO::FETCH_COLUMN);
                $cantidadActual = count($puestos);
                if ($cantidadActual > $capacidad) {
                    foreach (array_slice($puestos, 0, $cantidadActual - $capacidad) as $idPuesto) {
                        $borrarPuesto->execute(['id_puesto' => $idPuesto]);
                    }
                } elseif ($cantidadActual < $capacidad) {
                    for ($numero = $cantidadActual + 1; $numero <= $capacidad; $numero++) {
                        $crearPuesto->execute([
                            'codigo' => sprintf('J%d-%03d', $idModulo, $numero),
                            'id_modulo' => $idModulo,
                        ]);
                    }
                }
            }
        } else {
            $consulta = $conexion->prepare(
                'INSERT INTO tipos_modulo (nombre, capacidad) VALUES (:nombre, :capacidad)'
            );
            $consulta->execute(['nombre' => $nombre, 'capacidad' => $capacidad]);
        }
    } elseif ($entidad === 'modulo') {
        $consultaDestino = $conexion->prepare(
            'SELECT m.id_tipo_modulo
             FROM salas AS s
             INNER JOIN zonas AS z ON z.id_zona = s.id_zona
             INNER JOIN tipos_modulo AS m ON m.id_tipo_modulo = :id_tipo_modulo
             WHERE s.id_sala = :id_sala AND s.activo = 1
               AND z.activo = 1 AND m.activo = 1
             FOR UPDATE'
        );
        $consultaDestino->execute(['id_sala' => $idSala, 'id_tipo_modulo' => $idTipoModulo]);
        if (!$consultaDestino->fetch()) {
            $conexion->rollBack();
            header('Location: ' . $volver . '?resultado=destino_no_disponible' );
            exit;
        }

        if ($id > 0) {
            $ocupacionAbierta = $conexion->prepare(
                'SELECT id_ocupacion
                 FROM ocupaciones
                 WHERE id_modulo = :id_modulo AND fecha_fin IS NULL
                 LIMIT 1
                 FOR UPDATE'
            );
            $ocupacionAbierta->execute(['id_modulo' => $id]);
            if ($ocupacionAbierta->fetch()) {
                $conexion->rollBack();
                header('Location: ' . $volver . '?resultado=tiene_ocupaciones_abiertas' );
                exit;
            }
            $consulta = $conexion->prepare(
                'UPDATE modulos
                 SET codigo = :codigo, id_sala = :id_sala, id_tipo_modulo = :id_tipo_modulo
                 WHERE id_modulo = :id_modulo'
            );
            $consulta->execute([
                'codigo' => $codigo,
                'id_sala' => $idSala,
                'id_tipo_modulo' => $idTipoModulo,
                'id_modulo' => $id,
            ]);
        } else {
            $consulta = $conexion->prepare(
                'INSERT INTO modulos (codigo, id_sala, id_tipo_modulo)
                 VALUES (:codigo, :id_sala, :id_tipo_modulo)'
            );
            $consulta->execute([
                'codigo' => $codigo,
                'id_sala' => $idSala,
                'id_tipo_modulo' => $idTipoModulo,
            ]);
            $id = (int) $conexion->lastInsertId();

            $insertarPuesto = $conexion->prepare(
                'INSERT INTO puestos_juego (codigo, id_modulo) VALUES (:codigo, :id_modulo)'
            );
            $consultaCapacidad = $conexion->prepare(
                'SELECT capacidad FROM tipos_modulo WHERE id_tipo_modulo = :id_tipo_modulo'
            );
            $consultaCapacidad->execute(['id_tipo_modulo' => $idTipoModulo]);
            $capacidadModulo = (int) $consultaCapacidad->fetchColumn();
            for ($numero = 1; $numero <= $capacidadModulo; $numero++) {
                $insertarPuesto->execute([
                    'codigo' => sprintf('J%d-%03d', $id, $numero),
                    'id_modulo' => $id,
                ]);
            }
        }
    } else {
        $conexion->rollBack();
        header('Location: ' . $volver . '?resultado=datos_invalidos' );
        exit;
    }

    $conexion->commit();
    header('Location: ' . $volver . '?resultado=guardado' );
    exit;
} catch (PDOException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log('Error al guardar la estructura del local: ' . $error->getMessage());
    $resultado = $error->getCode() === '23000' ? 'duplicado' : 'error_bd';
    header('Location: ' . $volver . '?resultado=' . $resultado );
    exit;
}
