<?php

declare(strict_types=1);

$partesPlazo = [
    ['etiqueta' => 'Vencidas', 'cantidad' => $kpis['plazo']['rojo'], 'color' => '#9d2c2c'],
    ['etiqueta' => 'Por vencer', 'cantidad' => $kpis['plazo']['amarillo'], 'color' => '#8a5a00'],
    ['etiqueta' => 'En plazo', 'cantidad' => $kpis['plazo']['verde'], 'color' => '#1d4e89'],
    ['etiqueta' => 'Finalizadas', 'cantidad' => $kpis['plazo']['cumplida'], 'color' => '#1e7a46'],
];
$totalPlazo = max(1, array_sum(array_column($partesPlazo, 'cantidad')));
$giro = 0.0;
$tramos = [];
foreach ($partesPlazo as $parte) {
    $inicio = $giro;
    $giro += ($parte['cantidad'] / $totalPlazo) * 100;
    $tramos[] = $parte['color'] . ' ' . round($inicio, 2) . '% ' . round($giro, 2) . '%';
}
$conteoInstituto = [];
foreach ($institutos_dependientes as $instituto) {
    $conteoInstituto[$instituto['id']] = 0;
}
foreach ($actividades as $actividadPanel) {
    foreach ($actividadPanel['institutos'] as $institutoId) {
        if (isset($conteoInstituto[$institutoId])) {
            $conteoInstituto[$institutoId]++;
        }
    }
}
arsort($conteoInstituto);
$maximoInstituto = max(1, ...array_values($conteoInstituto));
$porAvance = $actividades;
usort($porAvance, static function (array $a, array $b): int {
    if ($a['avance'] !== $b['avance']) {
        return $a['avance'] <=> $b['avance'];
    }

    return strcmp($a['fecha'], $b['fecha']);
});
?>
<section class="kpis" aria-label="Indicadores">
    <article class="kpi">
        <h2>Actividades</h2>
        <p class="cifra"><?= e($kpis['total']) ?></p>
        <p>Registradas en su ámbito.</p>
    </article>
    <article class="kpi kpi-ambar">
        <h2>Próximas a vencer</h2>
        <p class="cifra"><?= e($kpis['proximas']) ?></p>
        <p>Fecha de realización entre hoy y los próximos 7 días.</p>
    </article>
    <article class="kpi kpi-rojo">
        <h2>Vencidas</h2>
        <p class="cifra"><?= e($kpis['vencidas']) ?></p>
        <p>Fecha pasada y actividad no finalizada.</p>
    </article>
    <article class="kpi kpi-verde">
        <h2>Finalizadas</h2>
        <p class="cifra"><?= e($kpis['finalizadas']) ?></p>
        <p>Todas sus tareas están cerradas.</p>
    </article>
    <article class="kpi">
        <h2>Con requerimientos</h2>
        <p class="cifra"><?= e($kpis['con_requerimientos']) ?></p>
        <p>Tienen al menos un requerimiento sin resolver.</p>
    </article>
</section>

<section class="graficos">
    <article class="tarjeta">
        <h2>Plazo de las actividades</h2>
        <div class="plazo-grafico">
            <div class="donut" style="background: conic-gradient(<?= e(implode(', ', $tramos)) ?>);">
                <div class="donut-centro">
                    <strong><?= e($kpis['total']) ?></strong>
                    <span>actividades</span>
                </div>
            </div>
            <ul class="leyenda-grafico">
                <?php foreach ($partesPlazo as $parte): ?>
                    <li>
                        <i style="background: <?= e($parte['color']) ?>"></i>
                        <?= e($parte['etiqueta']) ?>
                        <strong><?= e($parte['cantidad']) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </article>
    <article class="tarjeta">
        <h2>Distribución por instituto</h2>
        <p class="subida">Cantidad de actividades en las que participa cada instituto.</p>
        <div class="barras">
            <?php foreach ($conteoInstituto as $institutoId => $cantidad): ?>
                <div class="barra">
                    <span class="barra-nombre"><?= e(sigla_instituto($institutos, $institutoId)) ?></span>
                    <span class="barra-pista" role="img" aria-label="<?= e(sigla_instituto($institutos, $institutoId)) ?>: <?= e($cantidad) ?> actividades">
                        <span style="width: <?= e((int) round($cantidad / $maximoInstituto * 100)) ?>%"></span>
                    </span>
                    <span class="barra-valor"><?= e($cantidad) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="tarjeta">
    <header class="tarjeta-encabezado">
        <h2>Porcentaje de avance</h2>
        <p>Promedio del ámbito: <strong><?= e($kpis['avance_promedio']) ?> %</strong>. Cada barra es el promedio de las tareas de esa actividad.</p>
    </header>
    <div class="avances">
        <?php foreach ($porAvance as $actividadAvance): ?>
            <a class="avance-fila" href="<?= e(url_de('actividad', ['id' => $actividadAvance['id']])) ?>">
                <span class="avance-titulo"><?= e($actividadAvance['titulo']) ?></span>
                <span class="avance avance-largo"><span style="width: <?= e($actividadAvance['avance']) ?>%"></span></span>
                <span class="avance-numero"><?= e($actividadAvance['avance']) ?> %</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="tarjeta">
    <header class="tarjeta-encabezado">
        <h2>Tabla general</h2>
        <p>Primero las actividades vencidas. Después, las restantes por fecha de realización, de la más antigua a la más lejana.</p>
    </header>
    <?php require ROOT . '/views/partials/leyenda.php'; ?>
    <?php
    $listaActividades = $actividades;
    require ROOT . '/views/partials/tabla_actividades.php';
    ?>
</section>
