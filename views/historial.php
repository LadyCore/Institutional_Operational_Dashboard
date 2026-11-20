<?php

declare(strict_types=1);

$filas = filtrar_auditoria($auditoria, $filtro_auditoria);
?>
<section class="tarjeta">
    <header class="tarjeta-encabezado">
        <h2>Historial de la sesión</h2>
        <p>Simulación funcional de la trazabilidad. Registra las operaciones de esta visita y es de solo lectura. No es un historial permanente: se descarta al cerrar la sesión o al restablecer los datos ficticios.</p>
    </header>
    <form class="filtros" method="get" action="index.php">
        <input type="hidden" name="r" value="historial">
        <div>
            <label for="q">Buscar</label>
            <input id="q" name="q" type="search" value="<?= e($filtro_auditoria['q']) ?>" maxlength="120">
        </div>
        <div>
            <label for="desde">Desde</label>
            <input id="desde" name="desde" type="date" value="<?= e($filtro_auditoria['desde']) ?>">
        </div>
        <div>
            <label for="hasta">Hasta</label>
            <input id="hasta" name="hasta" type="date" value="<?= e($filtro_auditoria['hasta']) ?>">
        </div>
        <div>
            <label for="tipo_registro">Tipo de registro</label>
            <select id="tipo_registro" name="tipo_registro">
                <option value="">Todos</option>
                <?php foreach (['organismo', 'actividad', 'tarea', 'requerimiento'] as $tipo): ?>
                    <option value="<?= e($tipo) ?>"<?= $filtro_auditoria['tipo_registro'] === $tipo ? ' selected' : '' ?>><?= e(etiqueta_tipo_registro($tipo)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="operacion">Tipo de operación</label>
            <select id="operacion" name="operacion">
                <option value="">Todas</option>
                <?php foreach (['alta', 'modificacion', 'baja', 'activacion', 'desactivacion', 'reasignacion', 'prorroga'] as $operacion): ?>
                    <option value="<?= e($operacion) ?>"<?= $filtro_auditoria['operacion'] === $operacion ? ' selected' : '' ?>><?= e(etiqueta_operacion($operacion)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filtros-acciones">
            <button type="submit">Filtrar</button>
            <a href="<?= e(url_de('historial')) ?>">Limpiar</a>
        </div>
    </form>
    <p class="resultado">Se muestran <?= e(count($filas)) ?> de <?= e(count($auditoria)) ?> operaciones, de la más reciente a la más antigua.</p>
    <?php if ($filas === []): ?>
        <p>Ninguna operación coincide con el filtro.</p>
    <?php else: ?>
        <div class="historial">
            <?php foreach ($filas as $fila): ?>
                <?php $fila = normalizar_historial($fila); ?>
                <details>
                    <summary>
                        <strong><?= e(fecha_hora_es($fila['cuando'])) ?></strong>
                        · <?= e(etiqueta_operacion($fila['operacion'])) ?>
                        · <?= e(etiqueta_tipo_registro($fila['tipo_registro'])) ?>
                        <?= e($fila['registro_id']) ?>
                        · <?= e($fila['registro_nombre']) ?>
                    </summary>
                    <dl>
                        <dt>Usuario</dt>
                        <dd><?= e(nombre_usuario($usuarios, $fila['usuario_id'])) ?></dd>
                        <dt>Campo</dt>
                        <dd><?= e($fila['campo']) ?></dd>
                        <dt>Valor anterior</dt>
                        <dd><?= e($fila['anterior']) ?></dd>
                        <dt>Valor nuevo</dt>
                        <dd><?= e($fila['nuevo']) ?></dd>
                        <dt>Motivo</dt>
                        <dd><?= $fila['motivo'] !== '' ? e($fila['motivo']) : 'No requerido.' ?></dd>
                    </dl>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
