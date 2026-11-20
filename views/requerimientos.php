<?php

declare(strict_types=1);

$filtrados = filtrar_requerimientos($requerimientos, $filtro_requerimientos);
$consultaPendientes = $filtro_requerimientos['estado'] === 'sin_resolver';
$cantidadPendientes = count($filtrados);
?>
<?php if ($consultaPendientes): ?>
<section class="tarjeta">
    <header class="encabezado-consulta">
        <div>
            <h2>Requerimientos pendientes — <?= e($cantidadPendientes) ?> <?= $cantidadPendientes === 1 ? 'requerimiento' : 'requerimientos' ?></h2>
            <p>Se muestran los que todavía no están resueltos, la misma cifra del panel.</p>
        </div>
        <a class="enlace-accion" href="<?= e(url_de('requerimientos')) ?>">Quitar filtro</a>
    </header>
</section>
<?php endif; ?>
<section class="tarjeta">
    <p class="aviso-demo">Sección demostrativa de coordinación. En este prototipo el alta y el cambio de estado funcionan dentro de la sesión. No hay base de datos ni aviso por correo. En la aplicación definitiva el registro será automático, persistente y no editable por usuarios comunes en su historial.</p>
    <form class="filtros" method="get" action="index.php">
        <input type="hidden" name="r" value="requerimientos">
        <div>
            <label for="q">Buscar</label>
            <input id="q" name="q" type="search" value="<?= e($filtro_requerimientos['q']) ?>" maxlength="120">
        </div>
        <div>
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo">
                <option value="">Todos</option>
                <?php foreach (tipos_requerimiento() as $tipo): ?>
                    <option value="<?= e($tipo) ?>"<?= $filtro_requerimientos['tipo'] === $tipo ? ' selected' : '' ?>><?= e($tipo) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="estado">Estado de resolución</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                <option value="sin_resolver"<?= $filtro_requerimientos['estado'] === 'sin_resolver' ? ' selected' : '' ?>>Sin resolver</option>
                <?php foreach (estados_requerimiento() as $estado): ?>
                    <option value="<?= e($estado) ?>"<?= $filtro_requerimientos['estado'] === $estado ? ' selected' : '' ?>><?= e($estado) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="instituto">Instituto</label>
            <select id="instituto" name="instituto">
                <option value="">Todos</option>
                <?php foreach ($institutos as $instituto): ?>
                    <?php if (($instituto['tipo'] ?? '') === 'instituto' && (!instituto_activo($instituto) || registro_baja($instituto))) { continue; } ?>
                    <option value="<?= e($instituto['id']) ?>"<?= $filtro_requerimientos['instituto'] === $instituto['id'] ? ' selected' : '' ?>><?= e($instituto['sigla']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filtros-acciones">
            <button type="submit">Filtrar</button>
            <a href="<?= e(url_de('requerimientos')) ?>">Limpiar</a>
        </div>
    </form>
    <p class="resultado">Se muestran <?= e(count($filtrados)) ?> de <?= e(count($requerimientos)) ?> requerimientos.</p>
    <?php if ($usuario['perfil'] === 'responsable'): ?>
        <p class="nota-semaforo">Puede cambiar el estado cuando su instituto es quien debe atender el requerimiento. Puede registrar uno nuevo vinculado a una actividad de su ámbito.</p>
    <?php elseif ($usuario['perfil'] === 'consulta'): ?>
        <p class="nota-semaforo">Su perfil es de consulta: ve el estado de resolución y no lo modifica.</p>
    <?php endif; ?>
    <div class="tabla-contenedor">
        <table class="tabla">
            <caption class="sr-only">Requerimientos vinculados a actividades y tareas</caption>
            <thead>
                <tr>
                    <th scope="col">Identificador</th>
                    <th scope="col">Tipo y descripción</th>
                    <th scope="col">Vinculado a</th>
                    <th scope="col">Solicita</th>
                    <th scope="col">Atiende</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Alta</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($filtrados === []): ?>
                    <tr>
                        <td colspan="7">Ningún requerimiento coincide con el filtro.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($filtrados as $requerimiento): ?>
                    <tr id="<?= e($requerimiento['id']) ?>">
                        <td class="id"><?= e($requerimiento['id']) ?></td>
                        <td>
                            <?= e($requerimiento['tipo']) ?>
                            <span class="sub"><?= e($requerimiento['descripcion']) ?></span>
                            <?php if ($requerimiento['observaciones'] !== ''): ?>
                                <span class="sub">Observaciones: <?= e($requerimiento['observaciones']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= e(url_de('actividad', ['id' => $requerimiento['actividad_id']])) ?>"><?= e($requerimiento['actividad_id']) ?></a>
                            <?php if ($requerimiento['tarea_id']): ?>
                                <span class="sub"><?= e($requerimiento['tarea_id']) ?></span>
                            <?php else: ?>
                                <span class="sub">Sin tarea específica</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(sigla_instituto($institutos, $requerimiento['instituto_solicitante'])) ?></td>
                        <td>
                            <?= e(sigla_instituto($institutos, $requerimiento['instituto_destino'])) ?>
                            <span class="sub"><?= e(nombre_usuario($usuarios, $requerimiento['responsable_id'])) ?></span>
                        </td>
                        <td><span class="estado estado-<?= e(clase_estado($requerimiento['estado'])) ?>"><?= e($requerimiento['estado']) ?></span></td>
                        <td><?= e(fecha_es($requerimiento['creado'])) ?></td>
                    </tr>
                    <?php if (Politica::editaRequerimiento($usuario, $requerimiento)): ?>
                        <tr class="fila-formulario">
                            <td colspan="7">
                                <form class="form-linea" method="post" action="index.php">
                                    <?= campo_csrf() ?>
                                    <input type="hidden" name="accion" value="requerimiento_estado">
                                    <input type="hidden" name="requerimiento_id" value="<?= e($requerimiento['id']) ?>">
                                    <?= campos_volver('requerimientos') ?>
                                    <div>
                                        <label for="estado-<?= e($requerimiento['id']) ?>">Estado de <?= e($requerimiento['id']) ?></label>
                                        <select id="estado-<?= e($requerimiento['id']) ?>" name="estado">
                                            <?php foreach (estados_requerimiento() as $opcion): ?>
                                                <option value="<?= e($opcion) ?>"<?= $opcion === $requerimiento['estado'] ? ' selected' : '' ?>><?= e($opcion) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="campo-ancho">
                                        <label for="obs-req-<?= e($requerimiento['id']) ?>">Observaciones de resolución</label>
                                        <textarea id="obs-req-<?= e($requerimiento['id']) ?>" name="observaciones" maxlength="500" rows="2"><?= e($requerimiento['observaciones']) ?></textarea>
                                    </div>
                                    <button type="submit">Guardar resolución</button>
                                </form>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if (Politica::creaRequerimiento($usuario)): ?>
    <section class="tarjeta">
        <h2>Registrar requerimiento</h2>
        <p class="nota-semaforo">Queda vinculado a una actividad y, si se elige, a una tarea. El responsable de atenderlo es la persona de referencia del instituto destino.</p>
        <?php require ROOT . '/views/partials/form_requerimiento.php'; ?>
    </section>
<?php endif; ?>
