<?php

declare(strict_types=1);

$menu = [
    'panel' => 'Panel',
    'actividades' => 'Actividades',
    'requerimientos' => 'Requerimientos',
    'auditoria' => 'Auditoría',
    'institutos' => 'Institutos',
];
$activa = $ruta === 'actividad' ? 'actividades' : $ruta;
$pendientes = 0;
foreach ($requerimientos as $requerimientoNav) {
    if ($requerimientoNav['estado'] !== 'Resuelto') {
        $pendientes++;
    }
}
$institutoActual = $institutos[$usuario['instituto_id']] ?? null;
$actividadId = is_array($actividad ?? null) ? (string) ($actividad['id'] ?? '') : '';
$volverRuta = $ruta === 'actividad' ? ($actividadId !== '' ? 'actividad' : 'actividades') : $ruta;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($titulo_pagina) ?></title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/app.css?v=7">
</head>
<body>
<a class="salto" href="#contenido">Saltar al contenido</a>
<div class="app">
    <aside class="sidebar" id="menu">
        <div class="marca">
            <span class="monograma" aria-hidden="true">DG</span>
            <div>
                <strong>DIGEDUC</strong>
                <span>Seguimiento operativo</span>
            </div>
        </div>
        <nav class="menu" aria-label="Secciones">
            <?php foreach ($menu as $clave => $etiqueta): ?>
                <a href="<?= e(url_de($clave)) ?>"<?= $activa === $clave ? ' aria-current="page"' : '' ?>>
                    <span><?= e($etiqueta) ?></span>
                    <?php if ($clave === 'panel' && $kpis['vencidas'] > 0): ?>
                        <span class="cuenta cuenta-roja"><?= e($kpis['vencidas']) ?></span>
                    <?php elseif ($clave === 'requerimientos' && $pendientes > 0): ?>
                        <span class="cuenta"><?= e($pendientes) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="lado-usuario">
            <p class="lado-nombre"><?= e($usuario['nombre']) ?></p>
            <p><?= e(etiqueta_perfil($usuario['perfil'])) ?></p>
            <p><?= e($institutoActual['sigla'] ?? '') ?></p>
        </div>
        <form class="lado-restaurar" method="post" action="index.php">
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="restaurar">
            <?= campos_volver($volverRuta, $actividadId !== '' ? $actividadId : null) ?>
            <button type="submit">Descartar cambios de la sesión</button>
        </form>
        <p class="lado-nota">Prototipo demostrativo. Organización, personas y operaciones ficticias. Sin base de datos.</p>
    </aside>
    <button class="cortina" type="button" data-nav-close aria-label="Cerrar menú"></button>
    <div class="principal">
        <header class="barra">
            <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="menu">Menú</button>
            <div class="barra-titulo">
                <p class="miga">Dirección General de Educación</p>
                <h1><?= e($titulo) ?></h1>
            </div>
            <form class="perfil" method="post" action="index.php">
                <?= campo_csrf() ?>
                <input type="hidden" name="accion" value="perfil">
                <?= campos_volver($volverRuta, $actividadId !== '' ? $actividadId : null) ?>
                <label for="user_id">Ver como</label>
                <select id="user_id" name="user_id">
                    <optgroup label="Administrador central">
                        <?php foreach ($directorio as $persona): ?>
                            <?php if ($persona['perfil'] !== 'admin') { continue; } ?>
                            <option value="<?= e($persona['id']) ?>"<?= $persona['id'] === $usuario['id'] ? ' selected' : '' ?>><?= e($persona['nombre']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Responsable de instituto">
                        <?php foreach ($directorio as $persona): ?>
                            <?php if ($persona['perfil'] !== 'responsable') { continue; } ?>
                            <option value="<?= e($persona['id']) ?>"<?= $persona['id'] === $usuario['id'] ? ' selected' : '' ?>><?= e($persona['nombre']) ?> · <?= e(sigla_instituto($institutos, $persona['instituto_id'])) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Usuario de consulta">
                        <?php foreach ($directorio as $persona): ?>
                            <?php if ($persona['perfil'] !== 'consulta') { continue; } ?>
                            <option value="<?= e($persona['id']) ?>"<?= $persona['id'] === $usuario['id'] ? ' selected' : '' ?>><?= e($persona['nombre']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
                <button type="submit">Aplicar</button>
            </form>
        </header>
        <main id="contenido" class="contenido">
            <p class="referencia">Fecha de referencia: <?= e($hoy_largo) ?>. Los plazos se calculan en días calendario respecto de este día.</p>
            <?php if ($usuario['perfil'] === 'admin'): ?>
                <p class="aviso-perfil">Ámbito: todas las actividades. Puede actualizar tareas y requerimientos. El alta de actividades nuevas no forma parte de este prototipo. Los cambios viven en la sesión y no se escriben en una base de datos.</p>
            <?php elseif ($usuario['perfil'] === 'responsable'): ?>
                <p class="aviso-perfil">Ámbito: actividades en las que participa <?= e($institutoActual['nombre'] ?? 'su instituto') ?>. Puede actualizar las tareas de su instituto y registrar requerimientos. El resto se muestra para coordinar y queda en solo lectura.</p>
            <?php else: ?>
                <p class="aviso-perfil">Ámbito de consulta: puede leer la información institucional. Este perfil no modifica tareas ni requerimientos.</p>
            <?php endif; ?>
            <?php if ($flash): ?>
                <p class="flash flash-<?= e($flash['tipo']) ?>" role="<?= $flash['tipo'] === 'error' ? 'alert' : 'status' ?>"><?= e($flash['texto']) ?></p>
            <?php endif; ?>
            <?php require $vistaArchivo; ?>
        </main>
    </div>
</div>
<script src="assets/js/app.js" defer></script>
</body>
</html>
