<?php

declare(strict_types=1);

$tareasDeActividad = $tareas_por_actividad[$actividad['id']] ?? [];
$reqsActividad = $reqs_por_actividad[$actividad['id']] ?? [];
$editarTareas = $usuario['perfil'] !== 'consulta';
$volverRuta = 'actividad';
$volverId = $actividad['id'];
$actividadFija = $actividad;
$situacionVuelta = en_lista((string) ($_GET['situacion'] ?? ''), ['rojo', 'amarillo', 'verde', 'finalizada']);
$volverLista = $situacionVuelta !== ''
    ? url_de('actividades', ['situacion' => $situacionVuelta])
    : url_de('actividades');
?>
<p class="volver"><a href="<?= e($volverLista) ?>">Volver al listado</a></p>
<?php if (registro_baja($actividad)): ?>
    <p class="flash flash-info" role="status">Esta actividad está dada de baja. No aparece en el tablero ni admite cambios. Sus tareas quedan como antecedente de la sesión.</p>
<?php endif; ?>
<section class="tarjeta ficha">
    <p class="id-ficha"><?= e($actividad['id']) ?></p>
    <div class="ficha-grid">
        <div class="dato">
            <span>Tipo</span>
            <strong><?= e($actividad['tipo']) ?></strong>
        </div>
        <div class="dato">
            <span>Inicio</span>
            <strong><?= e(fecha_es($actividad['fecha_inicio'])) ?></strong>
        </div>
        <div class="dato">
            <span>Finalización prevista</span>
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
            <span>Dependencias participantes</span>
            <strong>
                <?php foreach (dependencias_visibles($actividad['institutos'], $institutos) as $dependencia): ?>
                    <span class="sigla"><?= e($dependencia['sigla']) ?></span>
                <?php endforeach; ?>
            </strong>
        </div>
    </div>
    <h2>Descripción</h2>
    <p><?= e($actividad['descripcion']) ?></p>
    <h2>Observaciones</h2>
    <p><?= $actividad['observaciones'] !== '' ? e($actividad['observaciones']) : 'Sin observaciones.' ?></p>
    <p class="nota-semaforo">La finalización prevista de la actividad es independiente del vencimiento de cada tarea. El indicador de esta ficha corresponde a esa fecha general. Las prórrogas no la modifican.</p>
    <?php if (Politica::administraOperaciones($usuario) && !registro_baja($actividad)): ?>
        <h2>Modificar actividad</h2>
        <?php
        $actividadForm = $actividad;
        require ROOT . '/views/partials/form_actividad.php';
        ?>
        <form class="form-requerimiento" method="post" action="index.php">
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="actividad_baja">
            <input type="hidden" name="actividad_id" value="<?= e($actividad['id']) ?>">
            <?= campos_volver('actividades', null, $situacionVuelta) ?>
            <div class="campo-ancho">
                <label for="baja-actividad">Motivo de la baja</label>
                <input id="baja-actividad" name="motivo" required minlength="8" maxlength="500">
            </div>
            <button type="submit">Dar de baja la actividad</button>
        </form>
    <?php endif; ?>
</section>

<section class="tarjeta">
    <header class="tarjeta-encabezado">
        <h2>Tareas por instituto</h2>
        <p>El plazo de cada fila es independiente de la fecha de la actividad. El estado de ejecución y el semáforo se muestran por separado.</p>
    </header>
    <?php if ($usuario['perfil'] === 'responsable'): ?>
        <p class="nota-semaforo">Puede guardar cambios en las tareas de <?= e(sigla_instituto($institutos, $usuario['instituto_id'])) ?>. Las tareas de otros institutos quedan en consulta. El cambio permanece en esta sesión.</p>
    <?php elseif ($usuario['perfil'] === 'admin'): ?>
        <p class="nota-semaforo">Puede actualizar, reasignar y reprogramar cualquier tarea. El cambio permanece en esta sesión y no se escribe en una base de datos.</p>
    <?php endif; ?>
    <?php require ROOT . '/views/partials/leyenda.php'; ?>
    <?php if (Politica::administraOperaciones($usuario) && !registro_baja($actividad)): ?>
        <h3>Asignar una tarea</h3>
        <?php require ROOT . '/views/partials/form_tarea_nueva.php'; ?>
    <?php endif; ?>
    <?php if ($tareasDeActividad === []): ?>
        <p>Esta actividad no tiene tareas cargadas.</p>
    <?php else: ?>
        <?php
        $participantes = $actividad['institutos'];
        $mostrarHistorial = true;
        require ROOT . '/views/partials/tabla_tareas.php';
        ?>
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
