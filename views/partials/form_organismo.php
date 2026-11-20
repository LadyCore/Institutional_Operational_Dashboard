<?php

declare(strict_types=1);

if (!Politica::administraOperaciones($usuario)) {
    return;
}

$organismoForm = $organismoForm ?? null;
$esEdicion = is_array($organismoForm);
$prefijo = $esEdicion ? (string) $organismoForm['id'] : 'nuevo';
$responsableForm = $responsableForm ?? '';
?>
<form class="form-requerimiento" method="post" action="index.php">
    <?= campo_csrf() ?>
    <input type="hidden" name="accion" value="<?= $esEdicion ? 'organismo_editar' : 'organismo_crear' ?>">
    <?= campos_volver('institutos') ?>
    <?php if ($esEdicion): ?>
        <input type="hidden" name="organismo_id" value="<?= e($organismoForm['id']) ?>">
    <?php endif; ?>
    <div>
        <label for="sigla-<?= e($prefijo) ?>">Sigla</label>
        <input id="sigla-<?= e($prefijo) ?>" name="sigla" required maxlength="8" pattern="[A-Za-z]{3,8}" value="<?= e($esEdicion ? (string) $organismoForm['sigla'] : '') ?>">
    </div>
    <div class="campo-ancho">
        <label for="nombre-org-<?= e($prefijo) ?>">Nombre</label>
        <input id="nombre-org-<?= e($prefijo) ?>" name="nombre" required maxlength="160" value="<?= e($esEdicion ? (string) $organismoForm['nombre'] : '') ?>">
    </div>
    <div>
        <label for="responsable-org-<?= e($prefijo) ?>">Responsable</label>
        <input id="responsable-org-<?= e($prefijo) ?>" name="responsable" required maxlength="120" value="<?= e($responsableForm) ?>">
    </div>
    <div class="campo-ancho">
        <label for="descripcion-org-<?= e($prefijo) ?>">Descripción</label>
        <textarea id="descripcion-org-<?= e($prefijo) ?>" name="descripcion" maxlength="500" rows="2"><?= e($esEdicion ? (string) $organismoForm['descripcion'] : '') ?></textarea>
    </div>
    <?php if ($esEdicion): ?>
        <label class="checks">
            <input type="checkbox" name="activo" value="1"<?= instituto_activo($organismoForm) ? ' checked' : '' ?>>
            Activo para nuevas actividades y tareas
        </label>
    <?php endif; ?>
    <button type="submit"><?= $esEdicion ? 'Guardar organismo' : 'Crear organismo' ?></button>
</form>
<?php if ($esEdicion && !registro_baja($organismoForm)): ?>
<form class="form-requerimiento form-baja" method="post" action="index.php">
    <?= campo_csrf() ?>
    <input type="hidden" name="accion" value="organismo_baja">
    <input type="hidden" name="organismo_id" value="<?= e($organismoForm['id']) ?>">
    <?= campos_volver('institutos') ?>
    <p class="aviso-baja">Dar de baja es una acción sensible. El organismo deja de ofrecerse para nuevas asignaciones y conserva su antecedente en esta sesión.</p>
    <div class="campo-ancho">
        <label for="baja-org-<?= e($prefijo) ?>">Motivo de la baja</label>
        <input id="baja-org-<?= e($prefijo) ?>" name="motivo" required minlength="8" maxlength="500">
    </div>
    <button class="accion-sensible" type="submit">Dar de baja</button>
</form>
<?php endif; ?>
