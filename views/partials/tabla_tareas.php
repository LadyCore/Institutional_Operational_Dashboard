<?php

declare(strict_types=1);

$editarTareas = $editarTareas ?? false;
$volverRuta = $volverRuta ?? 'actividades';
$volverId = $volverId ?? null;
$mostrarHistorial = $mostrarHistorial ?? false;
?>
<div class="tabla-contenedor">
    <table class="tabla tabla-tareas">
        <caption class="sr-only">Tareas de la actividad</caption>
        <thead>
            <tr>
                <th scope="col">Instituto</th>
                <th scope="col">Responsable</th>
                <th scope="col">Tarea</th>
                <th scope="col">Fecha exigible</th>
                <th scope="col">Plazo</th>
                <th scope="col">Estado</th>
                <th scope="col">Avance</th>
                <th scope="col">Observaciones</th>
                <th scope="col">Requerimientos</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tareasDeActividad as $tarea): ?>
                <?php
                $luz = semaforo_tarea($tarea);
                $reqsTarea = $reqs_por_tarea[$tarea['id']] ?? [];
                $puedeEditar = $editarTareas && !registro_baja($tarea) && Politica::editaTarea($usuario, $tarea);
                $exigible = etiqueta_exigible($tarea);
                ?>
                <tr>
                    <td><?= e(sigla_instituto($institutos, $tarea['instituto_id'])) ?></td>
                    <td><?= e(nombre_usuario($usuarios, $tarea['usuario_id'])) ?></td>
                    <td>
                        <span class="id-menor"><?= e($tarea['id']) ?></span>
                        <?= e($tarea['descripcion']) ?>
                        <?php if (registro_baja($tarea)): ?>
                            <span class="sub">Dada de baja. Se conserva como antecedente de esta sesión.</span>
                        <?php endif; ?>
                        <?php if ($mostrarHistorial && $tarea['reprogramaciones'] !== []): ?>
                            <ul class="historial-reprog">
                                <?php foreach ($tarea['reprogramaciones'] as $indice => $reprogramacion): ?>
                                    <li>
                                        <?= e(etiqueta_ordinal_vencimiento($indice + 2)) ?>:
                                        <?= e(fecha_es((string) $reprogramacion['fecha_anterior'])) ?>
                                        pasa a <?= e(fecha_es((string) $reprogramacion['fecha_nueva'])) ?>.
                                        <?= e((string) $reprogramacion['motivo']) ?>.
                                        <?= e(nombre_usuario($usuarios, (string) $reprogramacion['usuario_id'])) ?>,
                                        <?= e(fecha_hora_es((string) $reprogramacion['cuando'])) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= e($exigible) ?></strong>
                        <?= e(fecha_es($tarea['fecha_limite'])) ?>
                        <?php if ($tarea['reprogramaciones'] !== []): ?>
                            <span class="sub">Con prórroga</span>
                        <?php endif; ?>
                        <?php if ($mostrarHistorial): ?>
                            <?php foreach (vencimientos_tarea($tarea) as $vencimiento): ?>
                                <span class="sub"><?= e($vencimiento['etiqueta']) ?>: <?= e(fecha_es($vencimiento['fecha'])) ?><?= $vencimiento['exigible'] ? ' · exigible' : '' ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <span class="sub">Inicio <?= e(fecha_es($tarea['fecha_inicio'])) ?></span>
                        <span class="sub"><?= e(texto_plazo($tarea['fecha_limite'], $tarea['estado'])) ?></span>
                        <?php if ($tarea['estado'] === 'Finalizada' && $tarea['fecha_cumplimiento'] !== null): ?>
                            <span class="sub">Cumplida el <?= e(fecha_es($tarea['fecha_cumplimiento'])) ?><?= $tarea['cumplida_dentro_plazo_original'] === true ? ', dentro del plazo original' : ($tarea['cumplida_dentro_plazo_original'] === false ? ', después del plazo original' : '') ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="sem sem-<?= e($luz['clave']) ?> sem-<?= e($luz['motivo']) ?>"><?= e($luz['etiqueta']) ?></span></td>
                    <td><span class="estado estado-<?= e(clase_estado($tarea['estado'])) ?>"><?= e($tarea['estado']) ?></span></td>
                    <td><?= e($tarea['avance']) ?> %</td>
                    <td><?= $tarea['observaciones'] !== '' ? e($tarea['observaciones']) : '—' ?></td>
                    <td>
                        <?php if ($reqsTarea === []): ?>
                            Ninguno
                        <?php else: ?>
                            <ul class="lista-reqs">
                                <?php foreach ($reqsTarea as $reqTarea): ?>
                                    <li>
                                        <a href="<?= e(url_de('requerimientos')) ?>#<?= e($reqTarea['id']) ?>"><?= e($reqTarea['id']) ?></a>
                                        <?= e($reqTarea['tipo']) ?> · <?= e($reqTarea['estado']) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($puedeEditar): ?>
                    <tr class="fila-formulario">
                        <td colspan="9">
                            <form class="form-linea" method="post" action="index.php" data-tarea-form>
                                <?= campo_csrf() ?>
                                <input type="hidden" name="accion" value="tarea">
                                <input type="hidden" name="tarea_id" value="<?= e($tarea['id']) ?>">
                                <?= campos_volver($volverRuta, $volverId, $situacionVuelta ?? null) ?>
                                <div>
                                    <label for="estado-<?= e($tarea['id']) ?>">Estado de <?= e($tarea['id']) ?></label>
                                    <select id="estado-<?= e($tarea['id']) ?>" name="estado">
                                        <?php foreach (estados_tarea() as $opcion): ?>
                                            <option value="<?= e($opcion) ?>"<?= $opcion === $tarea['estado'] ? ' selected' : '' ?>><?= e($opcion) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="avance-<?= e($tarea['id']) ?>">Avance %</label>
                                    <input id="avance-<?= e($tarea['id']) ?>" name="avance" type="number" min="0" max="100" required value="<?= e($tarea['avance']) ?>">
                                </div>
                                <div class="campo-ancho">
                                    <label for="obs-<?= e($tarea['id']) ?>">Observaciones</label>
                                    <textarea id="obs-<?= e($tarea['id']) ?>" name="observaciones" maxlength="500" rows="2"><?= e($tarea['observaciones']) ?></textarea>
                                </div>
                                <button type="submit">Guardar tarea</button>
                            </form>
                            <?php if (Politica::administraOperaciones($usuario) && ($participantes ?? []) !== []): ?>
                                <?php require ROOT . '/views/partials/form_tarea_admin.php'; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
