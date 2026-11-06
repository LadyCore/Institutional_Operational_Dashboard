<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$errores = [];

function esperar(bool $condicion, string $mensaje): void
{
    global $errores;
    if (!$condicion) {
        $errores[] = $mensaje;
    }
}

$servicio = servicio();
$_SESSION['user_id'] = 'u-quelvo';
$admin = $servicio->contexto($servicio->usuarioActual());

esperar(count($admin['institutos_dependientes']) === 5, 'Deben existir cinco institutos.');
esperar(isset($admin['institutos']['DIGEDUC']) && $admin['institutos']['DIGEDUC']['tipo'] === 'autoridad', 'Falta la autoridad central.');
esperar(count($admin['actividades']) >= 15, 'Faltan actividades.');

$tipos = [];
$anteriorVencida = true;
$fechaAnterior = null;
foreach ($admin['actividades'] as $actividad) {
    $tipos[$actividad['tipo']] = true;
    if ($actividad['vencida']) {
        esperar($anteriorVencida, 'Una vencida quedó después de una no vencida: ' . $actividad['id']);
    } elseif ($fechaAnterior !== null && !$anteriorVencida) {
        esperar(strcmp($actividad['fecha'], $fechaAnterior) >= 0, 'El resto no quedó ordenado por fecha: ' . $actividad['id']);
    }
    if (!$actividad['vencida']) {
        $anteriorVencida = false;
        $fechaAnterior = $actividad['fecha'];
    }
    if ($actividad['estado'] === 'Finalizada') {
        esperar($actividad['semaforo']['clave'] === 'cumplida', $actividad['id'] . ' finalizada no debe verse vencida.');
    }
}
foreach (tipos_actividad() as $tipo) {
    esperar(isset($tipos[$tipo]), 'Falta el tipo: ' . $tipo);
}

$tareaBloqueada = null;
$tareaCerrada = null;
foreach ($admin['tareas'] as $tarea) {
    if ($tarea['id'] === 'TAR-011-02') {
        $tareaBloqueada = $tarea;
    }
    if ($tarea['id'] === 'TAR-007-02') {
        $tareaCerrada = $tarea;
    }
}
esperar($tareaBloqueada !== null && $tareaBloqueada['estado'] === 'Bloqueada', 'Falta la tarea bloqueada.');
if ($tareaBloqueada !== null) {
    esperar(semaforo($tareaBloqueada['fecha_limite'], $tareaBloqueada['estado'])['clave'] === 'rojo', 'La tarea bloqueada vencida debe estar en rojo.');
}
if ($tareaCerrada !== null) {
    esperar(semaforo($tareaCerrada['fecha_limite'], $tareaCerrada['estado'])['clave'] === 'cumplida', 'La tarea finalizada no debe quedar vencida.');
}
esperar(count(array_filter($admin['requerimientos'], static fn (array $r): bool => $r['estado'] === 'Pendiente')) > 0, 'Faltan requerimientos pendientes.');
esperar(count(array_filter($admin['requerimientos'], static fn (array $r): bool => $r['estado'] === 'Resuelto')) > 0, 'Faltan requerimientos resueltos.');
esperar(count($admin['auditoria']) >= 8, 'Falta historial de ejemplo.');

$_SESSION['user_id'] = 'u-herrera';
$ifal = $servicio->contexto($servicio->usuarioActual());
foreach ($ifal['actividades'] as $actividad) {
    esperar(in_array('IFAL', $actividad['institutos'], true), 'IFAL vio una actividad ajena: ' . $actividad['id']);
}
esperar(!isset($ifal['actividades_por_id']['ACT-2026-010']), 'IFAL no participa en ACT-2026-010.');

$_SESSION['user_id'] = 'u-duarte';
$consulta = $servicio->usuarioActual();
esperar(!Politica::editaTarea($consulta, $tareaBloqueada), 'Consulta no debe editar tareas.');
esperar(!Politica::creaRequerimiento($consulta), 'Consulta no debe crear requerimientos.');

$_SESSION['user_id'] = 'u-quelvo';
$_SESSION['csrf'] = 'prueba';
$antes = count($servicio->contexto($servicio->usuarioActual())['auditoria']);
$resultado = $servicio->actualizarTarea($servicio->usuarioActual(), [
    'csrf' => 'prueba',
    'tarea_id' => 'TAR-011-02',
    'estado' => 'Bloqueada',
    'avance' => '25',
    'observaciones' => 'Sigue bloqueada, con el dato pedido en revisión.',
]);
$despues = $servicio->contexto($servicio->usuarioActual());
esperar($resultado['tipo'] === 'ok', 'La actualización de tarea no se aplicó.');
$actualizada = $despues['tareas_por_id']['TAR-011-02'];
esperar($actualizada['estado'] === 'Bloqueada' && (int) $actualizada['avance'] === 25, 'El estado y el avance no quedaron separados.');
esperar(semaforo($actualizada['fecha_limite'], $actualizada['estado'])['clave'] === 'rojo', 'Después de editar, la tarea vencida sigue en rojo.');
esperar(count($despues['auditoria']) > $antes, 'La auditoría de sesión no sumó el cambio.');

$_SESSION['user_id'] = 'u-herrera';
$ajena = $servicio->actualizarTarea($servicio->usuarioActual(), [
    'tarea_id' => 'TAR-010-01',
    'estado' => 'Finalizada',
    'avance' => '100',
    'observaciones' => 'Intento fuera de ámbito.',
]);
esperar($ajena['tipo'] === 'error', 'Un responsable pudo editar la tarea de otro instituto.');

if ($errores === []) {
    echo "OK actividades=" . count($admin['actividades']) . " vencidas=" . $admin['kpis']['vencidas'] . " proximas=" . $admin['kpis']['proximas'] . " finalizadas=" . $admin['kpis']['finalizadas'] . PHP_EOL;
    exit(0);
}

fwrite(STDERR, implode(PHP_EOL, $errores) . PHP_EOL);
exit(1);
