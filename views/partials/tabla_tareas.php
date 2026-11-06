<?php

declare(strict_types=1);

$editarTareas = $editarTareas ?? false;
$volverRuta = $volverRuta ?? 'actividades';
$volverId = $volverId ?? null;
?>
<div class="tabla-contenedor">
    <table class="tabla tabla-tareas">
        <caption class="sr-only">Tareas de la actividad</caption>
        <thead>
            <tr>
                <th scope="col">Instituto</th>
                <th scope="col">Responsable</th>
                <th scope="col">Tarea</th>
                <th scope="col">Fecha límite</th>
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
                $luz = semaforo($tarea['fecha_limite'], $tarea['estado']);
                $reqsTarea = $reqs_por_tarea[$tarea['id']] ?? [];
                $puedeEditar = $editarTareas && Politica::editaTarea($usuario, $tarea);
                ?>
                <tr>
                    <td><?= e(sigla_instituto($institutos, $tarea['instituto_id'])) ?></td>
                    <td><?= e(nombre_usuario($usuarios, $tarea['usuario_id'])) ?></td>
                    <td>
                        <span class="id-menor"><?= e($tarea['id']) ?></span>
                        <?= e($tarea['descripcion']) ?>
                    </td>
                    <td>
                        <?= e(fecha_es($tarea['fecha_limite'])) ?>
                        <span class="sub"><?= e(texto_plazo($tarea['fecha_limite'], $tarea['estado'])) ?></span>
                    </td>
                    <td><span class="sem sem-<?= e($luz['clave']) ?>"><?= e($luz['etiqueta']) ?></span></td>
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
                                <?= campos_volver($volverRuta, $volverId) ?>
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
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
