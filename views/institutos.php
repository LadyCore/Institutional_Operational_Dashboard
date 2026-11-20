<?php

declare(strict_types=1);

$autoridad = null;
foreach ($institutos as $institutoLista) {
    if ($institutoLista['tipo'] === 'autoridad') {
        $autoridad = $institutoLista;
        break;
    }
}
?>
<section class="tarjeta">
    <h2><?= e($autoridad['nombre'] ?? 'Autoridad central') ?></h2>
    <p><?= e($autoridad['descripcion'] ?? '') ?></p>
    <ul class="lista-personas">
        <?php foreach ($directorio as $persona): ?>
            <?php if ($persona['instituto_id'] !== 'DIGEDUC') { continue; } ?>
            <li><?= e($persona['nombre']) ?> · <?= e(etiqueta_perfil($persona['perfil'])) ?></li>
        <?php endforeach; ?>
    </ul>
    <p class="nota-semaforo">Las cifras de cada instituto respetan el ámbito del perfil activo. Un responsable no ve actividades ajenas a su instituto.</p>
</section>

<?php if (Politica::administraOperaciones($usuario)): ?>
<section class="tarjeta">
    <header class="tarjeta-encabezado">
        <h2>Nuevo organismo</h2>
        <p>Queda disponible en los formularios de actividades y tareas. El alta vive en esta sesión.</p>
    </header>
    <?php
    $organismoForm = null;
    $responsableForm = '';
    require ROOT . '/views/partials/form_organismo.php';
    ?>
</section>
<?php endif; ?>

<section class="grilla-institutos">
    <?php foreach ($institutos_dependientes as $instituto): ?>
        <?php
        $actividadesInstituto = array_values(array_filter(
            $actividades,
            static fn (array $actividadInstituto): bool => in_array($instituto['id'], $actividadInstituto['institutos'], true)
        ));
        $tareasInstituto = array_values(array_filter(
            $tareas,
            static fn (array $tareaInstituto): bool => $tareaInstituto['instituto_id'] === $instituto['id']
        ));
        $abiertas = 0;
        $vencidasInstituto = 0;
        foreach ($tareasInstituto as $tareaInstituto) {
            if ($tareaInstituto['estado'] === 'Finalizada') {
                continue;
            }
            $abiertas++;
            if (semaforo_tarea($tareaInstituto)['clave'] === 'rojo') {
                $vencidasInstituto++;
            }
        }
        $responsable = null;
        foreach ($directorio as $persona) {
            if ($persona['instituto_id'] === $instituto['id'] && $persona['perfil'] === 'responsable') {
                $responsable = $persona;
                break;
            }
        }
        ?>
        <article class="tarjeta instituto-card">
            <p class="sigla sigla-grande"><?= e($instituto['sigla']) ?></p>
            <h2><?= e($instituto['nombre']) ?></h2>
            <p><?= e($instituto['descripcion']) ?></p>
            <p>Responsable de referencia: <?= e($responsable['nombre'] ?? 'Sin asignar') ?>.</p>
            <p><?= instituto_activo($instituto) ? 'Activo para nuevas asignaciones.' : 'Desactivado. Conserva sus actividades y tareas.' ?> Identificador interno: <?= e($instituto['id']) ?>.</p>
            <div class="acciones-instituto">
                <?php if (Politica::administraOperaciones($usuario)): ?>
                    <details class="bloque-admin">
                        <summary class="boton-secundario">Editar organismo</summary>
                        <div class="panel-edicion">
                            <?php
                            $organismoForm = $instituto;
                            $responsableForm = $responsable['nombre'] ?? '';
                            require ROOT . '/views/partials/form_organismo.php';
                            ?>
                        </div>
                    </details>
                <?php endif; ?>
                <a class="enlace-accion" href="<?= e(url_de('actividades', ['instituto' => $instituto['id']])) ?>">Ver sus actividades</a>
            </div>
            <dl class="cifras-instituto">
                <div>
                    <dt>Actividades</dt>
                    <dd><?= e(count($actividadesInstituto)) ?></dd>
                </div>
                <div>
                    <dt>Tareas abiertas</dt>
                    <dd><?= e($abiertas) ?></dd>
                </div>
                <div>
                    <dt>Tareas vencidas</dt>
                    <dd><?= e($vencidasInstituto) ?></dd>
                </div>
            </dl>
        </article>
    <?php endforeach; ?>
</section>
