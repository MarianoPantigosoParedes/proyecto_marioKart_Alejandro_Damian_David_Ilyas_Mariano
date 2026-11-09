<?php
require_once 'includes/auth.php';
requiere_login();

function calcularTiempoTranscurrido($fechaInicio) {
    $inicio = new DateTime($fechaInicio);
    $ahora = new DateTime(); // Hora actual del servidor
    $intervalo = $inicio->diff($ahora);

    $horas = $intervalo->h + ($intervalo->days * 24);
    $minutos = $intervalo->i;

    if ($horas > 0) {
        return "Lleva {$horas}h {$minutos}m";
    } else {
        return "Lleva {$minutos}m";
    }
}

// Una sola consulta trae salas + módulos + (si existe) la ocupación ABIERTA de cada módulo.
$sql = "SELECT s.id AS sala_id, s.nombre AS sala, s.tipo AS sala_tipo,
               m.id AS modulo_id, m.nombre AS modulo, m.tipo AS modulo_tipo, m.capacidad,
               o.id AS ocupacion_id, o.gamemaster_id, o.num_jugadores, o.fecha_inicio,
               CONCAT(g.nombre, ' ', g.apellido) AS gamemaster
        FROM salas s
        JOIN modulos m ON m.sala_id = s.id
        LEFT JOIN ocupaciones o ON o.modulo_id = m.id AND o.fecha_fin IS NULL
        LEFT JOIN gamemasters g ON g.id = o.gamemaster_id
        ORDER BY s.id, m.id";
$filas = db()->query($sql)->fetchAll();

// Agrupamos en PHP: $salas[sala_id] = [datos de sala + lista de módulos]
$salas = [];
foreach ($filas as $f) {
    $id = $f['sala_id'];
    if (!isset($salas[$id])) {
        $salas[$id] = [
            'nombre' => $f['sala'],
            'tipo' => $f['sala_tipo'],
            'capacidad' => 0,
            'libres' => 0,
            'modulos' => []
        ];
    }
    $salas[$id]['capacidad'] += $f['capacidad'];
    if ($f['ocupacion_id'] === null) {
        $salas[$id]['libres']++;
    }
    $salas[$id]['modulos'][] = $f;
}

$total = count($filas);
$libres = array_sum(array_column($salas, 'libres'));
$ocupados = $total - $libres;
$pct = $total > 0 ? round($ocupados / $total * 100) : 0;

$nombres_tipo = ['PC' => 'PC estándar', 'SIMULADOR_CONSOLA' => 'Simuladores / Consolas', 'VIP' => 'VIP'];

$titulo = 'Salas';
require 'includes/header.php';
?>
<h1>Estado de las salas</h1>

<!-- Resumen global -->
<section class="card">
    <h2>Resumen global</h2>
    <span class="badge">Módulos: <?= $total ?></span>
    <span class="badge">Libres: <?= $libres ?></span>
    <span class="badge">Ocupados: <?= $ocupados ?></span>
    <span class="badge">Ocupación: <?= $pct ?>%</span>
</section>

<!-- Controles de filtrado dinámico (Pieza HTML para JS) -->
<section class="card filtros-container">
    <h2>Filtrar salas</h2>
    <div class="filtros-grid">
        <select id="filtro-tipo-sala">
            <option value="">Todas las salas</option>
            <option value="PC">PC estándar</option>
            <option value="SIMULADOR_CONSOLA">Simuladores / Consolas</option>
            <option value="VIP">VIP</option>
        </select>

        <select id="filtro-tipo-modulo">
            <option value="">Todos los módulos</option>
            <option value="SOLO">Solo</option>
            <option value="DUO">Dúo</option>
            <option value="SQUAD">Squad</option>
            <option value="PARTY">Party</option>
        </select>

        <input type="number" id="buscador-capacidad" min="1" step="1" placeholder="¿Cuántos jugadores?">

        <label class="filtro-check">
            <input type="checkbox" id="check-solo-libres"> Solo libres
        </label>
    </div>
</section>

<!-- Listado de Salas y Módulos -->
<?php foreach ($salas as $sala): ?>
    <section class="card sala sala-<?= e(strtolower($sala['tipo'])) ?>" data-tipo-sala="<?= e($sala['tipo']) ?>">
        <div class="sala-head">
            <h2><?= e($sala['nombre']) ?> <small><?= e($nombres_tipo[$sala['tipo']]) ?></small></h2>
            <span class="badge">Capacidad total: <?= $sala['capacidad'] ?> · Libres: <?= $sala['libres'] ?>/<?= count($sala['modulos']) ?></span>
        </div>

        <div class="modulos">
        <?php foreach ($sala['modulos'] as $m): ?>
            <?php $ocupado = $m['ocupacion_id'] !== null; ?>
            <article class="modulo <?= $ocupado ? 'ocupado' : 'libre' ?>"
                     data-tipo="<?= e($m['modulo_tipo']) ?>"
                     data-capacidad="<?= (int)$m['capacidad'] ?>"
                     data-estado="<?= $ocupado ? 'ocupado' : 'libre' ?>">
                
                <h3><?= e($m['modulo']) ?></h3>
                <p>Capacidad: <strong><?= (int)$m['capacidad'] ?></strong></p>

                <?php if (!$ocupado): ?>
                    <p class="estado">🟢 Libre</p>
                    <form method="post" action="ocupar.php" class="form-ocupar">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="modulo_id" value="<?= (int)$m['modulo_id'] ?>">
                        <input type="number" name="num_jugadores" min="1" max="<?= (int)$m['capacidad'] ?>"
                               step="1" value="1" required aria-label="Número de jugadores">
                        <button class="btn btn-small" type="submit">Ocupar</button>
                    </form>
                <?php else: ?>
                <p class="estado"> Ocupado · <?= (int)$m['num_jugadores'] ?> jugador(es)</p>
                <p class="detalle">
                    Desde <?= e(date('d/m H:i', strtotime($m['fecha_inicio']))) ?><br>
                    <strong><?= calcularTiempoTranscurrido($m['fecha_inicio']) ?></strong><br>
                    por <?= e($m['gamemaster']) ?>
                </p>
                
                <?php if ((int)$m['gamemaster_id'] === $_SESSION['gm_id']): ?>
                    <!-- Botón liberar para el propietario -->
                    <form method="post" action="liberar.php" class="form-liberar">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="ocupacion_id" value="<?= (int)$m['ocupacion_id'] ?>">
                        <button class="btn btn-small btn-danger" type="submit">Liberar</button>
                    </form>
                <?php else: ?>
                    <p class="detalle">Solo puede liberarlo quien lo ocupó.</p>
                <?php endif; ?>
<?php endif; ?>
            </article>
        <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>

<?php require 'includes/footer.php'; ?>