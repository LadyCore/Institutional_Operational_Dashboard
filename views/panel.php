<?php

declare(strict_types=1);

$grupos = [
    'rojo' => [],
    'amarillo' => [],
    'verde' => [],
    'finalizada' => [],
];
foreach ($actividades as $actividadPanel) {
    $tareasActividad = tareas_operativas($tareas_por_actividad[$actividadPanel['id']] ?? []);
    $actividadPanel['situacion'] = situacion_desde_tareas($tareasActividad);
    $grupos[$actividadPanel['situacion']['clave']][] = $actividadPanel;
}
$porNombre = static function (array $a, array $b): int {
    return strcmp($a['titulo'], $b['titulo']);
};
foreach ($grupos as &$grupo) {
    usort($grupo, $porNombre);
}
unset($grupo);

$criticas = count($grupos['rojo']);
$seguimiento = count($grupos['amarillo']);
$normales = count($grupos['verde']);
$finalizadas = count($grupos['finalizada']);
$hayAlertas = $criticas + $seguimiento > 0;
$visibles = array_merge($grupos['rojo'], $grupos['amarillo']);
if (!$hayAlertas) {
    $visibles = array_slice($grupos['verde'], 0, 6);
}

$requerimientosAbiertos = 0;
foreach ($requerimientos as $requerimientoPanel) {
    if ($requerimientoPanel['estado'] !== 'Resuelto') {
        $requerimientosAbiertos++;
    }
}
$tiposAlerta = ($criticas > 0 ? 1 : 0) + ($seguimiento > 0 ? 1 : 0) + ($requerimientosAbiertos > 0 ? 1 : 0);

$proximos = [];
foreach ($tareas as $tareaPanel) {
    if (registro_baja($tareaPanel) || $tareaPanel['estado'] === 'Finalizada' || $tareaPanel['fecha_limite'] < $hoy) {
        continue;
    }
    $actividadDelPlazo = $actividades_por_id[$tareaPanel['actividad_id']] ?? null;
    if (!is_array($actividadDelPlazo) || registro_baja($actividadDelPlazo)) {
        continue;
    }
    $proximos[] = $tareaPanel;
}
usort($proximos, static function (array $a, array $b): int {
    $fecha = strcmp($a['fecha_limite'], $b['fecha_limite']);
    if ($fecha !== 0) {
        return $fecha;
    }

    return strcmp($a['descripcion'], $b['descripcion']);
});
$proximos = array_slice($proximos, 0, 4);
?>
<section class="panel-operativo">
    <section class="resumen-operativo" aria-label="Resumen operativo">
        <a class="resumen-item resumen-rojo" href="<?= e(url_de('actividades', ['situacion' => 'rojo'])) ?>">
            <span class="resumen-icono" aria-hidden="true"><?= icono('alerta') ?></span>
            <div>
                <h2>Situación crítica</h2>
                <p class="resumen-cifra"><?= e($criticas) ?></p>
                <p>Actividades</p>
            </div>
        </a>
        <a class="resumen-item resumen-amarillo" href="<?= e(url_de('actividades', ['situacion' => 'amarillo'])) ?>">
            <span class="resumen-icono" aria-hidden="true"><?= icono('reloj') ?></span>
            <div>
                <h2>Seguimiento necesario</h2>
                <p class="resumen-cifra"><?= e($seguimiento) ?></p>
                <p>Actividades</p>
            </div>
        </a>
        <a class="resumen-item resumen-verde" href="<?= e(url_de('actividades', ['situacion' => 'verde'])) ?>">
            <span class="resumen-icono" aria-hidden="true"><?= icono('ok') ?></span>
            <div>
                <h2>Situación normal</h2>
                <p class="resumen-cifra"><?= e($normales) ?></p>
                <p>Actividades</p>
            </div>
        </a>
        <a class="resumen-item resumen-finalizada" href="<?= e(url_de('actividades', ['situacion' => 'finalizada'])) ?>">
            <span class="resumen-icono" aria-hidden="true"><?= icono('bandera') ?></span>
            <div>
                <h2>Finalizadas</h2>
                <p class="resumen-cifra"><?= e($finalizadas) ?></p>
                <p>Actividades</p>
            </div>
        </a>
    </section>

    <div class="panel-cuerpo">
        <section class="tarjeta panel-sector">
            <header class="sector-encabezado">
                <div>
                    <h2><?= icono('capas') ?> Estado de las actividades</h2>
                    <p><?= $hayAlertas ? 'Primero las situaciones críticas, luego las que requieren seguimiento.' : 'No hay alertas operativas pendientes.' ?></p>
                </div>
                <a class="enlace-sector" href="<?= e(url_de('actividades')) ?>">Ver todas <?= icono('flecha') ?></a>
            </header>
            <?php if ($visibles === []): ?>
                <p>No hay actividades abiertas en este ámbito.</p>
            <?php else: ?>
                <div class="barras-operativas">
                    <?php foreach ($visibles as $actividadVisible): ?>
                        <?php $claveVisible = $actividadVisible['situacion']['clave']; ?>
                        <a class="fila-operativa" href="<?= e(url_de('actividad', ['id' => $actividadVisible['id'], 'situacion' => $claveVisible])) ?>">
                            <span class="marca-estado marca-<?= e($claveVisible) ?>" aria-hidden="true"></span>
                            <span class="fila-nombre"><?= e($actividadVisible['titulo']) ?></span>
                            <span class="pista pista-<?= e($claveVisible) ?><?= (int) $actividadVisible['avance'] === 0 ? ' pista-senal' : '' ?>">
                                <span style="width: <?= e((int) $actividadVisible['avance']) ?>%"></span>
                            </span>
                            <span class="fila-dato"><?= e($actividadVisible['avance']) ?> %</span>
                            <span class="sr-only"><?= e($actividadVisible['situacion']['etiqueta']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="panel-lateral">
            <section class="tarjeta panel-sector">
                <header class="sector-encabezado">
                    <h2><?= icono('campana') ?> Alertas operativas</h2>
                    <?php if ($tiposAlerta > 0): ?>
                        <span class="cuenta-alerta"><?= e($tiposAlerta) ?></span>
                    <?php endif; ?>
                </header>
                <ul class="alertas">
                    <li>
                        <a href="<?= e(url_de('actividades', ['situacion' => 'rojo'])) ?>">
                            <span class="alerta-icono alerta-roja" aria-hidden="true"><?= icono('alerta') ?></span>
                            <span>
                                <strong><?= e($criticas) ?> <?= $criticas === 1 ? 'actividad con tareas en rojo' : 'actividades con tareas en rojo' ?></strong>
                                <small>Requieren intervención inmediata.</small>
                            </span>
                            <?= icono('flecha') ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?= e(url_de('actividades', ['situacion' => 'amarillo'])) ?>">
                            <span class="alerta-icono alerta-amarilla" aria-hidden="true"><?= icono('reloj') ?></span>
                            <span>
                                <strong><?= e($seguimiento) ?> <?= $seguimiento === 1 ? 'actividad en seguimiento' : 'actividades en seguimiento' ?></strong>
                                <small>Con tareas pendientes en amarillo y ninguna en rojo.</small>
                            </span>
                            <?= icono('flecha') ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?= e(url_de('requerimientos', ['estado' => 'sin_resolver'])) ?>">
                            <span class="alerta-icono alerta-info" aria-hidden="true"><?= icono('info') ?></span>
                            <span>
                                <strong><?= e($requerimientosAbiertos) ?> <?= $requerimientosAbiertos === 1 ? 'requerimiento pendiente' : 'requerimientos pendientes' ?></strong>
                                <small>Todavía sin resolver.</small>
                            </span>
                            <?= icono('flecha') ?>
                        </a>
                    </li>
                </ul>
            </section>

            <section class="tarjeta panel-sector">
                <header class="sector-encabezado">
                    <h2><?= icono('calendario') ?> Próximos vencimientos</h2>
                    <a class="enlace-sector" href="<?= e(url_de('actividades')) ?>">Ver todos <?= icono('flecha') ?></a>
                </header>
                <?php if ($proximos === []): ?>
                    <p>No hay vencimientos de tareas por delante de la fecha de referencia.</p>
                <?php else: ?>
                    <ul class="proximos">
                        <?php foreach ($proximos as $proximo): ?>
                            <?php $cuando = fecha_dia_mes($proximo['fecha_limite']); ?>
                            <li>
                                <a href="<?= e(url_de('actividad', ['id' => $proximo['actividad_id']])) ?>">
                                    <span class="vence-fecha"><strong><?= e($cuando['dia']) ?></strong><small><?= e($cuando['mes']) ?></small></span>
                                    <span>
                                        <strong><?= e($proximo['descripcion']) ?></strong>
                                        <small><?= e(sigla_instituto($institutos, $proximo['instituto_id'])) ?> · <?= e(etiqueta_exigible($proximo)) ?></small>
                                    </span>
                                    <?= icono('flecha') ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
    </div>
</section>
