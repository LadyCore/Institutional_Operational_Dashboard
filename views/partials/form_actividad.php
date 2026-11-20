<?php

declare(strict_types=1);

if (!Politica::administraOperaciones($usuario)) {
    return;
}

$actividadForm = $actividadForm ?? null;
$esEdicion = is_array($actividadForm);
$prefijo = $esEdicion ? (string) $actividadForm['id'] : 'nueva';
$valor = static function (string $clave, string $defecto = '') use ($actividadForm, $esEdicion): string {
    if (!$esEdicion) {
        return $defecto;
    }

    return (string) ($actividadForm[$clave] ?? $defecto);
};
$institutosMarcados = $esEdicion && is_array($actividadForm['institutos'] ?? null) ? $actividadForm['institutos'] : [];
?>
<form class="form-requerimiento" method="post" action="index.php">
    <?= campo_csrf() ?>
    <input type="hidden" name="accion" value="<?= $esEdicion ? 'actividad_editar' : 'actividad_crear' ?>">
    <?php if ($esEdicion): ?>
        <input type="hidden" name="actividad_id" value="<?= e($actividadForm['id']) ?>">
        <?= campos_volver('actividad', $actividadForm['id'], $situacionVuelta ?? null) ?>
    <?php else: ?>
        <?= campos_volver('actividades') ?>
    <?php endif; ?>
    <div class="campo-ancho">
        <label for="titulo-<?= e($prefijo) ?>">Nombre</label>
        <input id="titulo-<?= e($prefijo) ?>" name="titulo" required maxlength="180" value="<?= e($valor('titulo')) ?>">
    </div>
    <div>
        <label for="tipo-<?= e($prefijo) ?>">Categoría</label>
        <select id="tipo-<?= e($prefijo) ?>" name="tipo" required>
            <?php foreach (tipos_actividad() as $tipo): ?>
                <option value="<?= e($tipo) ?>"<?= $valor('tipo') === $tipo ? ' selected' : '' ?>><?= e($tipo) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="responsable-<?= e($prefijo) ?>">Responsable</label>
        <select id="responsable-<?= e($prefijo) ?>" name="responsable_id" required>
            <?php foreach ($directorio as $persona): ?>
                <option value="<?= e($persona['id']) ?>"<?= $valor('responsable_id', $usuario['id']) === $persona['id'] ? ' selected' : '' ?>><?= e($persona['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="inicio-<?= e($prefijo) ?>">Inicio</label>
        <input id="inicio-<?= e($prefijo) ?>" name="fecha_inicio" type="date" required value="<?= e($valor('fecha_inicio', $hoy)) ?>">
    </div>
    <div>
        <label for="fin-<?= e($prefijo) ?>">Finalización prevista</label>
        <input id="fin-<?= e($prefijo) ?>" name="fecha" type="date" required value="<?= e($valor('fecha', $hoy)) ?>">
    </div>
    <fieldset class="checks">
        <legend>Organismos participantes</legend>
        <?php foreach ($institutos as $instituto): ?>
            <?php
            if (($instituto['tipo'] ?? '') !== 'instituto') {
                continue;
            }
            if (!instituto_activo($instituto) || registro_baja($instituto)) {
                continue;
            }
            $marcado = in_array($instituto['id'], $institutosMarcados, true);
            ?>
            <label>
                <input type="checkbox" name="institutos[]" value="<?= e($instituto['id']) ?>"<?= $marcado ? ' checked' : '' ?>>
                <?= e($instituto['sigla']) ?> · <?= e($instituto['nombre']) ?>
            </label>
        <?php endforeach; ?>
    </fieldset>
    <div class="campo-ancho">
        <label for="descripcion-<?= e($prefijo) ?>">Descripción</label>
        <textarea id="descripcion-<?= e($prefijo) ?>" name="descripcion" maxlength="1000" rows="3"><?= e($valor('descripcion')) ?></textarea>
    </div>
    <div class="campo-ancho">
        <label for="observaciones-<?= e($prefijo) ?>">Observaciones</label>
        <textarea id="observaciones-<?= e($prefijo) ?>" name="observaciones" maxlength="1000" rows="2"><?= e($valor('observaciones')) ?></textarea>
    </div>
    <button type="submit"><?= $esEdicion ? 'Guardar actividad' : 'Crear actividad' ?></button>
</form>
