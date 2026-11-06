<?php

declare(strict_types=1);

if (!Politica::creaRequerimiento($usuario)) {
    return;
}

$actividadFija = $actividadFija ?? null;
$tareasVinculables = [];
$origen = $actividadFija ? [$actividadFija] : $actividades;
foreach ($origen as $actividadOrigen) {
    foreach ($tareas_por_actividad[$actividadOrigen['id']] ?? [] as $tareaOrigen) {
        if ($usuario['perfil'] === 'responsable' && $tareaOrigen['instituto_id'] !== $usuario['instituto_id']) {
            continue;
        }
        $tareasVinculables[] = $tareaOrigen;
    }
}
$prefijo = $actividadFija['id'] ?? 'nueva';
?>
<form class="form-requerimiento" method="post" action="index.php">
    <?= campo_csrf() ?>
    <input type="hidden" name="accion" value="requerimiento_crear">
    <?= campos_volver($actividadFija ? 'actividad' : 'requerimientos', $actividadFija['id'] ?? null) ?>
    <?php if ($actividadFija): ?>
        <input type="hidden" name="actividad_id" value="<?= e($actividadFija['id']) ?>">
    <?php else: ?>
        <div>
            <label for="actividad-<?= e($prefijo) ?>">Actividad</label>
            <select id="actividad-<?= e($prefijo) ?>" name="actividad_id" data-actividad-select required>
                <option value="">Elegir</option>
                <?php foreach ($actividades as $actividadOpcion): ?>
                    <option value="<?= e($actividadOpcion['id']) ?>"><?= e($actividadOpcion['id']) ?> · <?= e($actividadOpcion['titulo']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div>
        <label for="tarea-<?= e($prefijo) ?>">Tarea</label>
        <select id="tarea-<?= e($prefijo) ?>" name="tarea_id" data-tarea-select>
            <option value="">Sin tarea específica</option>
            <?php foreach ($tareasVinculables as $tareaOpcion): ?>
                <option value="<?= e($tareaOpcion['id']) ?>" data-actividad="<?= e($tareaOpcion['actividad_id']) ?>">
                    <?= e($tareaOpcion['id']) ?> · <?= e(sigla_instituto($institutos, $tareaOpcion['instituto_id'])) ?> · <?= e($tareaOpcion['descripcion']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="tipo-req-<?= e($prefijo) ?>">Tipo</label>
        <select id="tipo-req-<?= e($prefijo) ?>" name="tipo" required>
            <?php foreach (tipos_requerimiento() as $tipoReq): ?>
                <option value="<?= e($tipoReq) ?>"><?= e($tipoReq) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if ($usuario['perfil'] === 'admin'): ?>
        <div>
            <label for="solicitante-<?= e($prefijo) ?>">Instituto solicitante</label>
            <select id="solicitante-<?= e($prefijo) ?>" name="instituto_solicitante" required>
                <?php foreach ($institutos as $institutoOpcion): ?>
                    <option value="<?= e($institutoOpcion['id']) ?>"<?= $institutoOpcion['id'] === 'IFAL' ? ' selected' : '' ?>><?= e($institutoOpcion['sigla']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div>
        <label for="destino-<?= e($prefijo) ?>">Destino que debe atenderlo</label>
        <select id="destino-<?= e($prefijo) ?>" name="instituto_destino" required>
            <?php foreach ($institutos as $institutoOpcion): ?>
                <option value="<?= e($institutoOpcion['id']) ?>"<?= $institutoOpcion['id'] === 'DIGEDUC' ? ' selected' : '' ?>><?= e($institutoOpcion['sigla']) ?> · <?= e($institutoOpcion['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo-ancho">
        <label for="desc-<?= e($prefijo) ?>">Descripción</label>
        <textarea id="desc-<?= e($prefijo) ?>" name="descripcion" required minlength="8" maxlength="500" rows="3"></textarea>
    </div>
    <button type="submit">Registrar requerimiento</button>
</form>
