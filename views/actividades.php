<?php

declare(strict_types=1);

$filtradas = filtrar_actividades($actividades, $filtro_actividades);
?>
<section class="tarjeta">
    <p class="subida">El plazo de cada actividad usa su fecha de realización. Cada tarea conserva su propia fecha límite.</p>
    <form class="filtros" method="get" action="index.php">
        <input type="hidden" name="r" value="actividades">
        <div>
            <label for="q">Buscar</label>
            <input id="q" name="q" type="search" value="<?= e($filtro_actividades['q']) ?>" maxlength="120">
        </div>
        <div>
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo">
                <option value="">Todos</option>
                <?php foreach (tipos_actividad() as $tipo): ?>
                    <option value="<?= e($tipo) ?>"<?= $filtro_actividades['tipo'] === $tipo ? ' selected' : '' ?>><?= e($tipo) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                <?php foreach (estados_actividad() as $estado): ?>
                    <option value="<?= e($estado) ?>"<?= $filtro_actividades['estado'] === $estado ? ' selected' : '' ?>><?= e($estado) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="instituto">Instituto</label>
            <select id="instituto" name="instituto">
                <option value="">Todos</option>
                <?php foreach ($institutos_dependientes as $instituto): ?>
                    <option value="<?= e($instituto['id']) ?>"<?= $filtro_actividades['instituto'] === $instituto['id'] ? ' selected' : '' ?>><?= e($instituto['sigla']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="plazo">Plazo</label>
            <select id="plazo" name="plazo">
                <option value="">Todos</option>
                <option value="vencida"<?= $filtro_actividades['plazo'] === 'vencida' ? ' selected' : '' ?>>Vencidas</option>
                <option value="proxima"<?= $filtro_actividades['plazo'] === 'proxima' ? ' selected' : '' ?>>Por vencer</option>
                <option value="en_plazo"<?= $filtro_actividades['plazo'] === 'en_plazo' ? ' selected' : '' ?>>En plazo</option>
                <option value="finalizada"<?= $filtro_actividades['plazo'] === 'finalizada' ? ' selected' : '' ?>>Finalizadas</option>
            </select>
        </div>
        <div>
            <label for="req">Requerimientos</label>
            <select id="req" name="req">
                <option value="">Todos</option>
                <option value="pendientes"<?= $filtro_actividades['req'] === 'pendientes' ? ' selected' : '' ?>>Con pendientes</option>
            </select>
        </div>
        <div class="filtros-acciones">
            <button type="submit">Filtrar</button>
            <a href="<?= e(url_de('actividades')) ?>">Limpiar</a>
        </div>
    </form>
    <p class="resultado">Se muestran <?= e(count($filtradas)) ?> de <?= e(count($actividades)) ?> actividades.</p>
    <?php require ROOT . '/views/partials/leyenda.php'; ?>
    <?php
    $listaActividades = $filtradas;
    require ROOT . '/views/partials/tabla_actividades.php';
    ?>
</section>
