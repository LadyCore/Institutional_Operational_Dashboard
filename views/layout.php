<?php

declare(strict_types=1);

$menu = [
    'panel' => ['Panel', 'inicio'],
    'actividades' => ['Actividades', 'lista'],
    'requerimientos' => ['Requerimientos', 'documento'],
    'institutos' => ['Dependencias', 'edificio'],
];
if (Politica::administraOperaciones($usuario)) {
    $menu['historial'] = ['Historial', 'reloj'];
}
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
$esPanel = $ruta === 'panel';
$subtitulo = $esPanel
    ? 'Seguimiento de actividades, tareas y requerimientos'
    : 'Organismo central';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($titulo_pagina) ?></title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/app.css?v=22">
    <script>
        (function () {
            try {
                if (localStorage.getItem("digeduc-tema") === "oscuro") {
                    document.documentElement.setAttribute("data-tema", "oscuro");
                }
            } catch (error) {}
        }());
    </script>
</head>
<body<?= $esPanel ? ' class="es-panel"' : '' ?>>
<a class="salto" href="#contenido">Saltar al contenido</a>
<div class="app">
    <aside class="sidebar" id="menu">
        <div class="marca">
            <img class="escudo-marca" src="assets/escudo-central.png" alt="" width="992" height="398">
            <div>
                <strong>CENTRAL</strong>
                <span>Organismo central</span>
            </div>
        </div>
        <nav class="menu" aria-label="Secciones">
            <?php foreach ($menu as $clave => $item): ?>
                <a href="<?= e(url_de($clave)) ?>"<?= $activa === $clave ? ' aria-current="page"' : '' ?>>
                    <?= icono($item[1]) ?>
                    <span><?= e($item[0]) ?></span>
                    <?php if ($clave === 'requerimientos' && $pendientes > 0): ?>
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
        <div class="onda-pie" aria-hidden="true">
            <svg viewBox="0 0 280 90" preserveAspectRatio="none">
                <path d="M0 50c40-28 70-28 110 0s70 28 110 0 40-22 60-8v48H0z" fill="#e7fbfb"/>
                <path d="M0 62c46-22 78-22 118 0s72 22 112 0 30-16 50-6" fill="none" stroke="#14c4c8" stroke-width="2"/>
            </svg>
        </div>
    </aside>
    <button class="cortina" type="button" data-nav-close aria-label="Cerrar menú"></button>
    <div class="principal">
        <header class="barra">
            <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="menu">Menú</button>
            <div class="barra-titulo">
                <h1><?= e($titulo) ?></h1>
                <p class="subtitulo"><?= e($subtitulo) ?></p>
            </div>
            <div class="onda-encabezado" aria-hidden="true">
                <svg viewBox="0 0 640 120" preserveAspectRatio="none">
                    <path d="M0 70c80-40 140-40 220 0s150 36 240-8 120-30 180 6" fill="none" stroke="#14c4c8" stroke-width="2"/>
                    <path d="M40 88c70-28 130-28 200 0s140 24 220-10 100-24 160 8" fill="none" stroke="#8eebed" stroke-width="1.5"/>
                </svg>
            </div>
            <div class="barra-meta">
                <p class="fecha-ref"><?= icono('calendario') ?><span><?= e(fecha_compacta(hoy())) ?></span></p>
                <div class="tema" role="group" aria-label="Apariencia">
                    <button type="button" data-tema-opcion="claro" aria-pressed="true"><?= icono('sol') ?><span>Claro</span></button>
                    <button type="button" data-tema-opcion="oscuro" aria-pressed="false"><?= icono('luna') ?><span>Oscuro</span></button>
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
                                <?php if (!responsable_en_tablero($persona, $institutos)) { continue; } ?>
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
            </div>
        </header>
        <main id="contenido" class="contenido">
            <?php if ($usuario['perfil'] === 'admin'): ?>
                <p class="aviso-perfil">Ámbito completo. Puede crear organismos, actividades y tareas, asignarlas y conceder prórrogas. Los cambios viven en esta sesión.</p>
            <?php elseif ($usuario['perfil'] === 'responsable'): ?>
                <p class="aviso-perfil">Ámbito: actividades de <?= e($institutoActual['nombre'] ?? 'su instituto') ?>. Puede actualizar las tareas de su instituto. El resto queda en consulta.</p>
            <?php else: ?>
                <p class="aviso-perfil">Consulta: puede leer la información institucional. Este perfil no modifica tareas ni requerimientos.</p>
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
