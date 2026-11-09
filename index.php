<?php
require_once 'includes/auth.php';
requiere_login();

// Una sola consulta trae salas + módulos + (si existe) la ocupación ABIERTA de cada módulo.
// LEFT JOIN: los módulos libres salen igualmente, con las columnas de "o" a NULL.
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
        $salas[$id] = ['nombre' => $f['sala'], 'tipo' => $f['sala_tipo'],
                       'capacidad' => 0, 'libres' => 0, 'modulos' => []];
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

<section class="card">
    <h2>Resumen global</h2>
    <span class="badge">Módulos: <?= $total ?></span>
    <span class="badge">Libres: <?= $libres ?></span>
    <span class="badge">Ocupados: <?= $ocupados ?></span>
    <span class="badge">Ocupación: <?= $pct ?>%</span>
</section>

<?php foreach ($salas as $sala): ?>
    <section class="card sala sala-<?= e(strtolower($sala['tipo'])) ?>">
        <div class="sala-head">
            <h2><?= e($sala['nombre']) ?> <small><?= e($nombres_tipo[$sala['tipo']]) ?></small></h2>
            <span class="badge">Capacidad total: <?= $sala['capacidad'] ?> · Libres: <?= $sala['libres'] ?>/<?= count($sala['modulos']) ?></span>
        </div>

        <div class="modulos">
        <?php foreach ($sala['modulos'] as $m): ?>
            <?php $ocupado = $m['ocupacion_id'] !== null; ?>
            <article class="modulo <?= $ocupado ? 'ocupado' : 'libre' ?>">
                <h3><?= e($m['modulo']) ?></h3>
                <p>Capacidad: <strong><?= (int)$m['capacidad'] ?></strong></p>

                <?php if (!$ocupado): ?>
                    <p class="estado">🟢 Libre</p>
                    <form method="post" action="ocupar.php" class="form-ocupar">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="modulo_id" value="<?= (int)$m['modulo_id'] ?>">
                        <input type="number" name="num_jugadores" min="1" max="<?= (int)$m['capacidad'] ?>"
                               value="1" required aria-label="Número de jugadores">
                        <button class="btn btn-small" type="submit">Ocupar</button>
                    </form>
                <?php else: ?>
                    <p class="estado">🔴 Ocupado · <?= (int)$m['num_jugadores'] ?> jugador(es)</p>
                    <p class="detalle">Desde <?= e(date('d/m H:i', strtotime($m['fecha_inicio']))) ?><br>
                       por <?= e($m['gamemaster']) ?></p>
                    <?php if ((int)$m['gamemaster_id'] === $_SESSION['gm_id']): ?>
                        <!-- Solo el gamemaster que ocupó ve el botón (y ademas liberar.php lo vuelve a comprobar) -->
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
