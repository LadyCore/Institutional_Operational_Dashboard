<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$servicio = servicio();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $destino = $servicio->procesarPost($_POST);
    header('Location: ' . $destino, true, 303);
    header('Cache-Control: no-store');
    exit;
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: SAMEORIGIN');

$usuario = $servicio->usuarioActual();
$ctx = $servicio->contexto($usuario);
$ctx['flash'] = $servicio->tomarFlash();
$ctx['ruta'] = $_GET['r'] ?? 'panel';
$rutas = [
    'panel' => 'Panel',
    'actividades' => 'Actividades',
    'actividad' => 'Actividad',
    'requerimientos' => 'Requerimientos',
    'auditoria' => 'Auditoría',
    'institutos' => 'Institutos',
];
if (!isset($rutas[$ctx['ruta']])) {
    $ctx['ruta'] = 'panel';
}

$q = trim((string) ($_GET['q'] ?? ''));
if (strlen($q) > 120) {
    $q = substr($q, 0, 120);
}
$institutoIds = array_keys($ctx['institutos']);
$ctx['filtro_actividades'] = [
    'q' => $q,
    'tipo' => en_lista((string) ($_GET['tipo'] ?? ''), tipos_actividad()),
    'estado' => en_lista((string) ($_GET['estado'] ?? ''), estados_actividad()),
    'instituto' => en_lista((string) ($_GET['instituto'] ?? ''), $institutoIds),
    'plazo' => en_lista((string) ($_GET['plazo'] ?? ''), ['vencida', 'proxima', 'en_plazo', 'finalizada']),
    'req' => (($_GET['req'] ?? '') === 'pendientes') ? 'pendientes' : '',
];
$ctx['filtro_requerimientos'] = [
    'q' => $ctx['ruta'] === 'requerimientos' ? $q : '',
    'tipo' => en_lista((string) ($_GET['tipo'] ?? ''), tipos_requerimiento()),
    'estado' => en_lista((string) ($_GET['estado'] ?? ''), estados_requerimiento()),
    'instituto' => en_lista((string) ($_GET['instituto'] ?? ''), $institutoIds),
];
$ctx['filtro_auditoria'] = [
    'q' => $ctx['ruta'] === 'auditoria' ? $q : '',
    'instituto' => en_lista((string) ($_GET['instituto'] ?? ''), $institutoIds),
];

$ctx['titulo'] = $rutas[$ctx['ruta']];
$ctx['actividad'] = null;
$ctx['aviso'] = null;
$vista = $ctx['ruta'] === 'panel' ? 'panel' : $ctx['ruta'];

if ($ctx['ruta'] === 'actividad') {
    $id = (string) ($_GET['id'] ?? '');
    if (!isset($ctx['actividades_por_id'][$id])) {
        http_response_code($servicio->existeActividad($id) ? 403 : 404);
        $ctx['aviso'] = $servicio->existeActividad($id)
            ? 'Esta actividad no pertenece al ámbito de su perfil.'
            : 'No hay una actividad con ese identificador.';
        $ctx['titulo'] = 'Actividad no disponible';
        $vista = 'aviso';
    } else {
        $ctx['actividad'] = $ctx['actividades_por_id'][$id];
        $ctx['titulo'] = $ctx['actividad']['titulo'];
        $vista = 'actividad';
    }
}

$ctx['titulo_pagina'] = $ctx['titulo'] . ' · DIGEDUC';
render($vista, $ctx);

function render(string $vista, array $ctx): void
{
    $vistaArchivo = ROOT . '/views/' . $vista . '.php';
    if (!is_file($vistaArchivo)) {
        http_response_code(404);
        $ctx['aviso'] = 'La sección no existe.';
        $ctx['titulo'] = 'Sección no disponible';
        $ctx['titulo_pagina'] = 'Sección no disponible · DIGEDUC';
        $vistaArchivo = ROOT . '/views/aviso.php';
    }
    extract($ctx, EXTR_SKIP);
    require ROOT . '/views/layout.php';
}
