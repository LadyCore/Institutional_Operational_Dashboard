<?php

declare(strict_types=1);

$actividadesConsulta = [];
foreach ($actividades as $actividadConsulta) {
    $tareasOperativas = tareas_operativas($tareas_por_actividad[$actividadConsulta['id']] ?? []);
    $actividadConsulta['situacion'] = situacion_desde_tareas($tareasOperativas);
    $actividadConsulta['tareas_operativas'] = $tareasOperativas;
    $actividadesConsulta[] = $actividadConsulta;
}
$filtradas = filtrar_actividades($actividadesConsulta, $filtro_actividades);
$situacionActiva = $filtro_actividades['situacion'];
$etiquetasSituacion = [
    'rojo' => 'Situación crítica',
    'amarillo' => 'Seguimiento necesario',
    'verde' => 'Situación normal',
    'finalizada' => 'Finalizadas',
];
$cantidad = count($filtradas);
?>
<?php if ($situacionActiva !== ''): ?>
<section class="tarjeta">
    <header class="encabezado-consulta">
        <div>
            <h2><?= e($etiquetasSituacion[$situacionActiva]) ?> — <?= e($cantidad) ?> <?= $cantidad === 1 ? 'actividad' : 'actividades' ?></h2>
            <p>Resultado de la consulta seleccionada en el panel. La clasificación es la misma que la de los indicadores.</p>
        </div>
        <a class="enlace-accion" href="<?= e(url_de('actividades')) ?>">Quitar filtro</a>
    </header>
    <?php if ($filtradas === []): ?>
        <p>Ninguna actividad coincide con esta consulta.</p>
    <?php endif; ?>
    <div class="consulta-lista">
        <?php foreach ($filtradas as $actividadFiltrada): ?>
            <?php
            $destacadas = array_values(array_filter(
                $actividadFiltrada['tareas_operativas'],
                static function (array $tareaFiltrada) use ($situacionActiva): bool {
                    $claveTarea = semaforo_tarea($tareaFiltrada)['clave'];
                    if ($situacionActiva === 'rojo') {
                        return $claveTarea === 'rojo';
                    }
                    if ($situacionActiva === 'amarillo') {
                        return $claveTarea === 'amarillo';
                    }
                    if ($situacionActiva === 'finalizada') {
                        return $tareaFiltrada['estado'] === 'Finalizada';
                    }

                    return $claveTarea === 'verde' && $tareaFiltrada['estado'] !== 'Finalizada';
                }
            ));
            $fichaFiltrada = url_de('actividad', ['id' => $actividadFiltrada['id'], 'situacion' => $situacionActiva]);
            ?>
            <article class="consulta-item">
                <div class="consulta-resumen">
                    <div>
                        <h3><a href="<?= e($fichaFiltrada) ?>"><?= e($actividadFiltrada['titulo']) ?></a></h3>
                        <p>
                            <?php foreach (dependencias_visibles($actividadFiltrada['institutos'], $institutos) as $dependencia): ?>
                                <span class="sigla"><?= e($dependencia['sigla']) ?></span>
                            <?php endforeach; ?>
                        </p>
                        <?php if ($destacadas === []): ?>
                            <p>Sin tareas pendientes en esta situación.</p>
                        <?php else: ?>
                            <ul class="consulta-tareas">
                                <?php foreach (array_slice($destacadas, 0, 3) as $tareaDestacada): ?>
                                    <li>
                                        <?= e($tareaDestacada['descripcion']) ?>
                                        <span><?= e(sigla_instituto($institutos, $tareaDestacada['instituto_id'])) ?> · <?= e(etiqueta_exigible($tareaDestacada)) ?> <?= e(fecha_es($tareaDestacada['fecha_limite'])) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (count($destacadas) > 3): ?>
                                <p><?= e(count($destacadas) - 3) ?> tareas más en el detalle.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <p class="consulta-avance">
                        <strong><?= e($actividadFiltrada['avance']) ?> %</strong>
                        <span class="avance"><span style="width: <?= e((int) $actividadFiltrada['avance']) ?>%"></span></span>
                    </p>
                </div>
                <details>
                    <summary>Ver detalle</summary>
                    <p><?= e($actividadFiltrada['descripcion']) ?></p>
                    <ul class="consulta-tareas">
                        <?php foreach ($actividadFiltrada['tareas_operativas'] as $tareaDetalle): ?>
                            <li>
                                <?= e($tareaDetalle['descripcion']) ?>
                                <span><?= e(sigla_instituto($institutos, $tareaDetalle['instituto_id'])) ?> · <?= e($tareaDetalle['estado']) ?> · <?= e($tareaDetalle['avance']) ?>% · <?= e(etiqueta_exigible($tareaDetalle)) ?> <?= e(fecha_es($tareaDetalle['fecha_limite'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= e($fichaFiltrada) ?>">Abrir la ficha</a>
                </details>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php if (Politica::administraOperaciones($usuario)): ?>
<details class="alta-actividad">
    <summary class="enlace-accion">Crear actividad</summary>
    <section class="tarjeta">
        <header class="tarjeta-encabezado">
            <h2>Nueva actividad</h2>
            <p>El alta queda en esta sesión. Después puede asignar las tareas de cada organismo desde la ficha.</p>
        </header>
        <?php
        $actividadForm = null;
        require ROOT . '/views/partials/form_actividad.php';
        ?>
    </section>
</details>
<?php endif; ?>
<?php if ($situacionActiva === ''): ?>
<section class="tarjeta">
    <p class="subida">La finalización prevista ordena el listado. Cada tarea conserva su inicio y su propio vencimiento.</p>
    <form class="filtros" method="get" action="index.php">
        <input type="hidden" name="r" value="actividades">
        <div>
            <label for="q">Buscar</label>
            <input id="q" name="q" type="search" value="<?= e($filtro_actividades['q']) ?>" maxlength="120">
        </div>
        <div>
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo">
                <option value="">Todos</option>
                <?php foreach (tipos_actividad() as $tipo): ?>
                    <option value="<?= e($tipo) ?>"<?= $filtro_actividades['tipo'] === $tipo ? ' selected' : '' ?>><?= e($tipo) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                <?php foreach (estados_actividad() as $estado): ?>
                    <option value="<?= e($estado) ?>"<?= $filtro_actividades['estado'] === $estado ? ' selected' : '' ?>><?= e($estado) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="instituto">Instituto</label>
            <select id="instituto" name="instituto">
                <option value="">Todos</option>
                <?php foreach ($institutos_asignables as $instituto): ?>
                    <option value="<?= e($instituto['id']) ?>"<?= $filtro_actividades['instituto'] === $instituto['id'] ? ' selected' : '' ?>><?= e($instituto['sigla']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="plazo">Plazo</label>
            <select id="plazo" name="plazo">
                <option value="">Todos</option>
                <option value="vencida"<?= $filtro_actividades['plazo'] === 'vencida' ? ' selected' : '' ?>>Vencidas</option>
                <option value="proxima"<?= $filtro_actividades['plazo'] === 'proxima' ? ' selected' : '' ?>>Por vencer</option>
                <option value="en_plazo"<?= $filtro_actividades['plazo'] === 'en_plazo' ? ' selected' : '' ?>>En plazo</option>
                <option value="finalizada"<?= $filtro_actividades['plazo'] === 'finalizada' ? ' selected' : '' ?>>Finalizadas</option>
            </select>
        </div>
        <div>
            <label for="req">Requerimientos</label>
            <select id="req" name="req">
                <option value="">Todos</option>
                <option value="pendientes"<?= $filtro_actividades['req'] === 'pendientes' ? ' selected' : '' ?>>Con pendientes</option>
            </select>
        </div>
        <div class="filtros-acciones">
            <button type="submit">Filtrar</button>
            <a href="<?= e(url_de('actividades')) ?>">Limpiar</a>
        </div>
    </form>
    <p class="resultado">Se muestran <?= e(count($filtradas)) ?> de <?= e(count($actividades)) ?> actividades.</p>
    <?php require ROOT . '/views/partials/leyenda.php'; ?>
    <?php
    $listaActividades = $filtradas;
    require ROOT . '/views/partials/tabla_actividades.php';
    ?>
</section>
<?php endif; ?>
