<?php

declare(strict_types=1);

$filas = filtrar_auditoria($auditoria, $filtro_auditoria);
?>
<section class="tarjeta">
    <p class="aviso-demo">Vista demostrativa. Las filas marcadas como ejemplo vienen precargadas. Las marcadas como sesión aparecen cuando alguien actualiza una tarea o un requerimiento durante esta visita. Descartar la sesión borra solo esas últimas. En la aplicación definitiva cada cambio se registrará solo, en el servidor, y un usuario común no podrá modificar ni borrar el historial.</p>
    <form class="filtros" method="get" action="index.php">
        <input type="hidden" name="r" value="auditoria">
        <div>
            <label for="q">Buscar</label>
            <input id="q" name="q" type="search" value="<?= e($filtro_auditoria['q']) ?>" maxlength="120">
        </div>
        <div>
            <label for="instituto">Instituto</label>
            <select id="instituto" name="instituto">
                <option value="">Todos</option>
                <?php foreach ($institutos as $instituto): ?>
                    <option value="<?= e($instituto['id']) ?>"<?= $filtro_auditoria['instituto'] === $instituto['id'] ? ' selected' : '' ?>><?= e($instituto['sigla']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filtros-acciones">
            <button type="submit">Filtrar</button>
            <a href="<?= e(url_de('auditoria')) ?>">Limpiar</a>
        </div>
    </form>
    <?php if ($usuario['perfil'] === 'responsable'): ?>
        <p class="nota-semaforo">Ve los movimientos de su instituto y los que otro perfil hizo sobre tareas o requerimientos de su ámbito.</p>
    <?php endif; ?>
    <p class="resultado">Se muestran <?= e(count($filas)) ?> de <?= e(count($auditoria)) ?> movimientos.</p>
    <div class="tabla-contenedor">
        <table class="tabla">
            <caption class="sr-only">Historial de modificaciones</caption>
            <thead>
                <tr>
                    <th scope="col">Fecha y hora</th>
                    <th scope="col">Usuario</th>
                    <th scope="col">Instituto</th>
                    <th scope="col">Campo modificado</th>
                    <th scope="col">Valor anterior</th>
                    <th scope="col">Valor nuevo</th>
                    <th scope="col">Origen</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($filas === []): ?>
                    <tr>
                        <td colspan="7">Ningún movimiento coincide con el filtro.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($filas as $fila): ?>
                    <?php $deSesion = strncmp((string) $fila['id'], 'AUD-S-', 6) === 0; ?>
                    <tr>
                        <td><?= e(fecha_hora_es($fila['cuando'])) ?></td>
                        <td><?= e(nombre_usuario($usuarios, $fila['usuario_id'])) ?></td>
                        <td><?= e(sigla_instituto($institutos, $fila['instituto_id'])) ?></td>
                        <td>
                            <?= e($fila['campo']) ?>
                            <span class="sub"><?= e($fila['entidad']) ?></span>
                        </td>
                        <td><?= e($fila['anterior']) ?></td>
                        <td><?= e($fila['nuevo']) ?></td>
                        <td><?= $deSesion ? 'Esta sesión' : 'Ejemplo' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
