<?php

declare(strict_types=1);

if (!Politica::administraOperaciones($usuario) || !is_array($actividad ?? null)) {
    return;
}

$participantes = [];
foreach ($actividad['institutos'] as $institutoId) {
    if (isset($institutos[$institutoId]) && instituto_activo($institutos[$institutoId])) {
        $participantes[] = $institutos[$institutoId];
    }
}
?>
<form class="form-requerimiento" method="post" action="index.php">
    <?= campo_csrf() ?>
    <input type="hidden" name="accion" value="tarea_crear">
    <input type="hidden" name="actividad_id" value="<?= e($actividad['id']) ?>">
    <?= campos_volver('actividad', $actividad['id'], $situacionVuelta ?? null) ?>
    <div class="campo-ancho">
        <label for="tarea-desc-<?= e($actividad['id']) ?>">Tarea</label>
        <textarea id="tarea-desc-<?= e($actividad['id']) ?>" name="descripcion" required maxlength="500" rows="2"></textarea>
    </div>
    <div>
        <label for="tarea-inicio-<?= e($actividad['id']) ?>">Inicio</label>
        <input id="tarea-inicio-<?= e($actividad['id']) ?>" name="fecha_inicio" type="date" required value="<?= e($actividad['fecha_inicio']) ?>">
    </div>
    <div>
        <label for="tarea-limite-<?= e($actividad['id']) ?>">Vencimiento</label>
        <input id="tarea-limite-<?= e($actividad['id']) ?>" name="fecha_limite" type="date" required value="<?= e($actividad['fecha']) ?>">
    </div>
    <div>
        <label for="tarea-usuario-<?= e($actividad['id']) ?>">Responsable</label>
        <select id="tarea-usuario-<?= e($actividad['id']) ?>" name="usuario_id">
            <option value="">Responsable del organismo</option>
            <?php foreach ($directorio as $persona): ?>
                <?php if (!in_array($persona['instituto_id'], $actividad['institutos'], true)) { continue; } ?>
                <option value="<?= e($persona['id']) ?>"><?= e(sigla_instituto($institutos, $persona['instituto_id'])) ?> · <?= e($persona['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <fieldset class="checks">
        <legend>Organismos que reciben la tarea</legend>
        <?php foreach ($participantes as $instituto): ?>
            <label>
                <input type="checkbox" name="institutos[]" value="<?= e($instituto['id']) ?>">
                <?= e($instituto['sigla']) ?> · <?= e($instituto['nombre']) ?>
            </label>
        <?php endforeach; ?>
    </fieldset>
    <p class="nota-semaforo">Si marca más de un organismo, se crea una tarea independiente para cada uno, con el responsable de ese organismo. El responsable elegido arriba se usa solo cuando hay un único organismo.</p>
    <button type="submit">Asignar tarea</button>
</form>
