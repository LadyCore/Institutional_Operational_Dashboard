<?php

declare(strict_types=1);

$lista = $listaActividades;
?>
<div class="tabla-contenedor">
    <table class="tabla">
        <caption class="sr-only">Actividades ordenadas con las vencidas primero y el resto por fecha de realización</caption>
        <thead>
            <tr>
                <th scope="col">Identificador</th>
                <th scope="col">Actividad</th>
                <th scope="col">Fecha</th>
                <th scope="col">Estado</th>
                <th scope="col">Avance</th>
                <th scope="col">Institutos</th>
                <th scope="col">Req.</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($lista === []): ?>
                <tr>
                    <td colspan="7">Ninguna actividad coincide con el filtro.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($lista as $item): ?>
                <?php $tareasItem = $tareas_por_actividad[$item['id']] ?? []; ?>
                <tr class="<?= $item['vencida'] ? 'fila-vencida' : '' ?><?= $item['estado'] === 'Finalizada' ? ' fila-finalizada' : '' ?>">
                    <td class="id"><?= e($item['id']) ?></td>
                    <td>
                        <a class="titulo-actividad" href="<?= e(url_de('actividad', ['id' => $item['id']])) ?>"><?= e($item['titulo']) ?></a>
                        <span class="sub"><?= e($item['tipo']) ?> · <?= e(nombre_usuario($usuarios, $item['responsable_id'])) ?></span>
                    </td>
                    <td>
                        <?= e(fecha_es($item['fecha'])) ?>
                        <span class="sem sem-<?= e($item['semaforo']['clave']) ?>"><?= e($item['semaforo']['etiqueta']) ?></span>
                        <span class="sub"><?= e(texto_plazo($item['fecha'], $item['estado'])) ?></span>
                    </td>
                    <td><span class="estado estado-<?= e(clase_estado($item['estado'])) ?>"><?= e($item['estado']) ?></span></td>
                    <td>
                        <span class="avance"><span style="width: <?= e($item['avance']) ?>%"></span></span>
                        <?= e($item['avance']) ?> %
                    </td>
                    <td>
                        <?php foreach ($item['institutos'] as $sigla): ?>
                            <span class="sigla"><?= e(sigla_instituto($institutos, $sigla)) ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td><?= e($item['reqs_pendientes']) ?></td>
                </tr>
                <tr class="fila-detalle">
                    <td colspan="7">
                        <details>
                            <summary>Tareas de <?= e($item['titulo']) ?> (<?= e(count($tareasItem)) ?>)</summary>
                            <p class="despliegue-texto"><?= e($item['descripcion']) ?></p>
                            <?php if ($item['observaciones'] !== ''): ?>
                                <p class="despliegue-texto"><strong>Observaciones.</strong> <?= e($item['observaciones']) ?></p>
                            <?php endif; ?>
                            <?php
                            $tareasDeActividad = $tareasItem;
                            $editarTareas = false;
                            require ROOT . '/views/partials/tabla_tareas.php';
                            ?>
                            <p class="seguir"><a href="<?= e(url_de('actividad', ['id' => $item['id']])) ?>">Abrir la ficha</a></p>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
