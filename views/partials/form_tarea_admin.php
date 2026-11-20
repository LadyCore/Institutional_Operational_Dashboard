<?php

declare(strict_types=1);

if (!Politica::administraOperaciones($usuario)) {
    return;
}

$participantes = $participantes ?? [];
?>
<details class="bloque-admin">
    <summary>Asignación y reprogramación de <?= e($tarea['id']) ?></summary>
    <form class="form-requerimiento" method="post" action="index.php">
        <?= campo_csrf() ?>
        <input type="hidden" name="accion" value="tarea_asignacion">
        <input type="hidden" name="tarea_id" value="<?= e($tarea['id']) ?>">
        <?= campos_volver($volverRuta, $volverId, $situacionVuelta ?? null) ?>
        <div class="campo-ancho">
            <label for="asig-desc-<?= e($tarea['id']) ?>">Tarea</label>
            <textarea id="asig-desc-<?= e($tarea['id']) ?>" name="descripcion" required maxlength="500" rows="2"><?= e($tarea['descripcion']) ?></textarea>
        </div>
        <div>
            <label for="asig-inicio-<?= e($tarea['id']) ?>">Inicio</label>
            <input id="asig-inicio-<?= e($tarea['id']) ?>" name="fecha_inicio" type="date" required value="<?= e($tarea['fecha_inicio']) ?>">
        </div>
        <div>
            <label for="asig-instituto-<?= e($tarea['id']) ?>">Organismo</label>
            <select id="asig-instituto-<?= e($tarea['id']) ?>" name="instituto_id" required>
                <?php foreach ($participantes as $institutoId): ?>
                    <?php if (!isset($institutos[$institutoId])) { continue; } ?>
                    <option value="<?= e($institutoId) ?>"<?= $institutoId === $tarea['instituto_id'] ? ' selected' : '' ?>><?= e(sigla_instituto($institutos, $institutoId)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="asig-usuario-<?= e($tarea['id']) ?>">Responsable</label>
            <select id="asig-usuario-<?= e($tarea['id']) ?>" name="usuario_id" required>
                <?php foreach ($directorio as $persona): ?>
                    <?php if (!in_array($persona['instituto_id'], $participantes, true)) { continue; } ?>
                    <option value="<?= e($persona['id']) ?>"<?= $persona['id'] === $tarea['usuario_id'] ? ' selected' : '' ?>><?= e(sigla_instituto($institutos, $persona['instituto_id'])) ?> · <?= e($persona['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Guardar asignación</button>
    </form>
    <form class="form-requerimiento" method="post" action="index.php">
        <?= campo_csrf() ?>
        <input type="hidden" name="accion" value="tarea_reprogramar">
        <input type="hidden" name="tarea_id" value="<?= e($tarea['id']) ?>">
        <?= campos_volver($volverRuta, $volverId, $situacionVuelta ?? null) ?>
        <div>
            <label for="reprog-fecha-<?= e($tarea['id']) ?>">Nuevo vencimiento</label>
            <input id="reprog-fecha-<?= e($tarea['id']) ?>" name="fecha_limite" type="date" required value="<?= e($tarea['fecha_limite']) ?>">
        </div>
        <div class="campo-ancho">
            <label for="reprog-motivo-<?= e($tarea['id']) ?>">Motivo</label>
            <textarea id="reprog-motivo-<?= e($tarea['id']) ?>" name="motivo" required maxlength="500" rows="2"></textarea>
        </div>
        <p class="nota-semaforo">La prórroga queda identificada como el vencimiento siguiente. No cumplimenta la tarea ni modifica la finalización prevista de la actividad. Mientras esa fecha no se incumpla, el plazo permanece en amarillo.</p>
        <button type="submit">Reprogramar vencimiento</button>
    </form>
    <?php if (!registro_baja($tarea)): ?>
        <form class="form-requerimiento" method="post" action="index.php">
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="tarea_baja">
            <input type="hidden" name="tarea_id" value="<?= e($tarea['id']) ?>">
            <?= campos_volver($volverRuta, $volverId, $situacionVuelta ?? null) ?>
            <div class="campo-ancho">
                <label for="baja-tar-<?= e($tarea['id']) ?>">Motivo de la baja</label>
                <input id="baja-tar-<?= e($tarea['id']) ?>" name="motivo" required minlength="8" maxlength="500">
            </div>
            <button type="submit">Dar de baja la tarea</button>
        </form>
    <?php endif; ?>
</details>
