<?php

declare(strict_types=1);

$tareasDeActividad = $tareas_por_actividad[$actividad['id']] ?? [];
$reqsActividad = $reqs_por_actividad[$actividad['id']] ?? [];
$editarTareas = $usuario['perfil'] !== 'consulta';
$volverRuta = 'actividad';
$volverId = $actividad['id'];
$actividadFija = $actividad;
?>
<p class="volver"><a href="<?= e(url_de('actividades')) ?>">Volver al listado</a></p>
<section class="tarjeta ficha">
    <p class="id-ficha"><?= e($actividad['id']) ?></p>
    <div class="ficha-grid">
        <div class="dato">
            <span>Tipo</span>
            <strong><?= e($actividad['tipo']) ?></strong>
        </div>
        <div class="dato">
            <span>Fecha de realización</span>
            <strong><?= e(fecha_es($actividad['fecha'])) ?></strong>
            <span class="sem sem-<?= e($actividad['semaforo']['clave']) ?>"><?= e($actividad['semaforo']['etiqueta']) ?></span>
            <small><?= e(texto_plazo($actividad['fecha'], $actividad['estado'])) ?></small>
        </div>
        <div class="dato">
            <span>Responsable general</span>
            <strong><?= e(nombre_usuario($usuarios, $actividad['responsable_id'])) ?></strong>
        </div>
        <div class="dato">
            <span>Estado general</span>
            <strong class="estado estado-<?= e(clase_estado($actividad['estado'])) ?>"><?= e($actividad['estado']) ?></strong>
        </div>
        <div class="dato">
            <span>Avance</span>
            <strong><?= e($actividad['avance']) ?> %</strong>
            <span class="avance avance-largo"><span style="width: <?= e($actividad['avance']) ?>%"></span></span>
        </div>
        <div class="dato">
            <span>Institutos participantes</span>
            <strong>
                <?php foreach ($actividad['institutos'] as $sigla): ?>
                    <span class="sigla"><?= e(sigla_instituto($institutos, $sigla)) ?></span>
                <?php endforeach; ?>
            </strong>
        </div>
    </div>
    <h2>Descripción</h2>
    <p><?= e($actividad['descripcion']) ?></p>
    <h2>Observaciones</h2>
    <p><?= $actividad['observaciones'] !== '' ? e($actividad['observaciones']) : 'Sin observaciones.' ?></p>
    <p class="nota-semaforo">El estado general y el avance se calculan a partir de las tareas. El semáforo de la actividad usa solo su fecha de realización. Cada tarea tiene su propia fecha límite.</p>
</section>

<section class="tarjeta">
    <header class="tarjeta-encabezado">
        <h2>Tareas por instituto</h2>
        <p>El plazo de cada fila es independiente de la fecha de la actividad. El estado de ejecución y el semáforo se muestran por separado.</p>
    </header>
    <?php if ($usuario['perfil'] === 'responsable'): ?>
        <p class="nota-semaforo">Puede guardar cambios en las tareas de <?= e(sigla_instituto($institutos, $usuario['instituto_id'])) ?>. Las tareas de otros institutos quedan en consulta. El cambio permanece en esta sesión.</p>
    <?php elseif ($usuario['perfil'] === 'admin'): ?>
        <p class="nota-semaforo">Puede actualizar cualquier tarea. El cambio permanece en esta sesión y no se escribe en una base de datos.</p>
    <?php endif; ?>
    <?php require ROOT . '/views/partials/leyenda.php'; ?>
    <?php if ($tareasDeActividad === []): ?>
        <p>Esta actividad no tiene tareas cargadas.</p>
    <?php else: ?>
        <?php require ROOT . '/views/partials/tabla_tareas.php'; ?>
    <?php endif; ?>
</section>

<section class="tarjeta">
    <header class="tarjeta-encabezado">
        <h2>Requerimientos vinculados</h2>
        <p>Quedan asociados a esta actividad y, cuando corresponde, a una tarea.</p>
    </header>
    <?php if ($reqsActividad === []): ?>
        <p>No hay requerimientos vinculados.</p>
    <?php else: ?>
        <ul class="lista-vinculos">
            <?php foreach ($reqsActividad as $reqVinculo): ?>
                <li id="req-<?= e($reqVinculo['id']) ?>">
                    <a href="<?= e(url_de('requerimientos')) ?>#<?= e($reqVinculo['id']) ?>"><?= e($reqVinculo['id']) ?></a>
                    <span class="estado estado-<?= e(clase_estado($reqVinculo['estado'])) ?>"><?= e($reqVinculo['estado']) ?></span>
                    <?= e($reqVinculo['tipo']) ?>.
                    <?= e($reqVinculo['descripcion']) ?>
                    <span class="sub">De <?= e(sigla_instituto($institutos, $reqVinculo['instituto_solicitante'])) ?> a <?= e(sigla_instituto($institutos, $reqVinculo['instituto_destino'])) ?><?= $reqVinculo['tarea_id'] ? ' · ' . e($reqVinculo['tarea_id']) : '' ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if (Politica::creaRequerimiento($usuario)): ?>
        <h3>Registrar un requerimiento de esta actividad</h3>
        <p class="nota-semaforo">Demostrativo: el alta se conserva en la sesión PHP. La aplicación definitiva lo guardará en la base y dejará la auditoría cerrada a edición.</p>
        <?php require ROOT . '/views/partials/form_requerimiento.php'; ?>
    <?php endif; ?>
</section>
