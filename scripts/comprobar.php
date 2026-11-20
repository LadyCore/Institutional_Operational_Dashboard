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

esperar(count($admin['institutos_dependientes']) === 9, 'Deben existir nueve dependencias.');
$siglasDependencias = array_column($admin['institutos_dependientes'], 'sigla');
sort($siglasDependencias);
esperar($siglasDependencias === ['CIIFA', 'EAM', 'ESFAC', 'ESFAE', 'ESGA', 'FACFA', 'INDAE', 'IUA', 'LAM'], 'El listado de dependencias no coincide con las dependencias cargadas.');
foreach ($admin['institutos_dependientes'] as $dependenciaInicial) {
    esperar(($dependenciaInicial['descripcion'] ?? '') === '' && ($dependenciaInicial['sede'] ?? '') === '', 'Una dependencia nueva no debería traer sede ni descripción.');
}
esperar(isset($admin['institutos']['DIGEDUC']) && $admin['institutos']['DIGEDUC']['tipo'] === 'autoridad', 'Falta la autoridad central.');
esperar(count($admin['actividades']) >= 15, 'Faltan actividades.');
$cantidadInicial = count($admin['actividades']);
$tareasIniciales = count($admin['tareas']);
$muestraActividad = $admin['actividades'][0];
$muestraTarea = $admin['tareas'][0];
esperar(isset($muestraActividad['fecha_inicio']) && $muestraActividad['fecha_inicio'] !== '' && $muestraActividad['fecha_inicio'] <= $muestraActividad['fecha'], 'La actividad de ejemplo debe tener inicio y finalización.');
esperar($muestraTarea['fecha_vencimiento_original'] === $muestraTarea['fecha_limite'], 'El vencimiento original inicial debe coincidir con el vigente.');
esperar($muestraTarea['reprogramaciones'] === [], 'Las tareas de ejemplo no deben nacer reprogramadas.');
esperar($muestraTarea['fecha_inicio'] !== '' && $muestraTarea['fecha_inicio'] <= $muestraTarea['fecha_limite'], 'La tarea de ejemplo debe tener un inicio anterior o igual al vencimiento.');
$luzVerde = semaforo_tarea($admin['tareas_por_id']['TAR-002-01']);
$luzAvance = semaforo_tarea($admin['tareas_por_id']['TAR-006-02']);
$luzProxima = semaforo_tarea($admin['tareas_por_id']['TAR-003-01']);
esperar($luzVerde['clave'] === 'verde' && $luzVerde['motivo'] === 'en_plazo', 'Un primer vencimiento holgado debe estar en verde.');
esperar($luzAvance['clave'] === 'amarillo' && $luzAvance['motivo'] === 'avance', 'El avance del plazo original debe quedar en amarillo, distinto de una prórroga.');
esperar($luzProxima['clave'] === 'rojo' && $luzProxima['motivo'] === 'proximo', 'A cinco días o menos del primer vencimiento debe estar en rojo.');
esperar(situacion_desde_tareas([$admin['tareas_por_id']['TAR-002-01']])['clave'] === 'verde', 'Una actividad sin tareas en amarillo ni rojo debe quedar en situación normal.');
esperar(situacion_desde_tareas([$admin['tareas_por_id']['TAR-002-01'], $admin['tareas_por_id']['TAR-006-02']])['clave'] === 'amarillo', 'Una tarea en amarillo debe marcar la actividad para seguimiento.');
esperar(situacion_desde_tareas([$admin['tareas_por_id']['TAR-002-01'], $admin['tareas_por_id']['TAR-006-02'], $admin['tareas_por_id']['TAR-003-01']])['clave'] === 'rojo', 'Una tarea en rojo debe imponer la situación crítica de la actividad.');

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
    esperar(semaforo_tarea($tareaBloqueada)['clave'] === 'rojo', 'La tarea bloqueada vencida debe estar en rojo.');
}
if ($tareaCerrada !== null) {
    esperar(semaforo_tarea($tareaCerrada)['clave'] === 'cumplida', 'La tarea finalizada no debe quedar vencida.');
    esperar(situacion_desde_tareas([$tareaCerrada])['clave'] === 'finalizada', 'Una actividad cumplimentada no debe generar alerta.');
}
esperar(count(array_filter($admin['requerimientos'], static fn (array $r): bool => $r['estado'] === 'Pendiente')) > 0, 'Faltan requerimientos pendientes.');
esperar(count(array_filter($admin['requerimientos'], static fn (array $r): bool => $r['estado'] === 'Resuelto')) > 0, 'Faltan requerimientos resueltos.');
esperar(count($admin['auditoria']) >= 8, 'Falta historial de ejemplo.');

$_SESSION['user_id'] = 'u-herrera';
$ifal = $servicio->contexto($servicio->usuarioActual());
foreach ($ifal['actividades'] as $actividad) {
    esperar(in_array('FACFA', $actividad['institutos'], true), 'FACFA vio una actividad ajena: ' . $actividad['id']);
}
esperar(!isset($ifal['actividades_por_id']['ACT-2026-010']), 'FACFA no participa en ACT-2026-010.');

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
esperar(semaforo_tarea($actualizada)['clave'] === 'rojo', 'Después de editar, la tarea vencida sigue en rojo.');
esperar(count($despues['auditoria']) > $antes, 'La auditoría de sesión no sumó el cambio.');

$_SESSION['user_id'] = 'u-herrera';
$ajena = $servicio->actualizarTarea($servicio->usuarioActual(), [
    'tarea_id' => 'TAR-010-01',
    'estado' => 'Finalizada',
    'avance' => '100',
    'observaciones' => 'Intento fuera de ámbito.',
]);
esperar($ajena['tipo'] === 'error', 'Un responsable pudo editar la tarea de otro instituto.');

$_SESSION['user_id'] = 'u-quelvo';
$adminUsuario = $servicio->usuarioActual();
$base = $servicio->contexto($adminUsuario)['actividades_por_id']['ACT-2026-010'];
$bloqueo = $servicio->editarActividad($adminUsuario, [
    'actividad_id' => 'ACT-2026-010',
    'titulo' => $base['titulo'],
    'descripcion' => $base['descripcion'],
    'tipo' => $base['tipo'],
    'fecha_inicio' => $base['fecha_inicio'],
    'fecha' => $base['fecha'],
    'responsable_id' => $base['responsable_id'],
    'observaciones' => $base['observaciones'],
    'institutos' => ['FACFA'],
]);
esperar($bloqueo['tipo'] === 'error', 'Se quitó un organismo que todavía tiene tareas.');

$ampliacion = $servicio->editarActividad($adminUsuario, [
    'actividad_id' => 'ACT-2026-010',
    'titulo' => $base['titulo'] . ' ampliada',
    'descripcion' => $base['descripcion'],
    'tipo' => $base['tipo'],
    'fecha_inicio' => $base['fecha_inicio'],
    'fecha' => $base['fecha'],
    'responsable_id' => $base['responsable_id'],
    'observaciones' => $base['observaciones'],
    'institutos' => ['EAM', 'FACFA'],
]);
esperar($ampliacion['tipo'] === 'ok', 'No se pudo agregar un organismo a la actividad.');
$editada = $servicio->contexto($adminUsuario)['actividades_por_id']['ACT-2026-010'];
esperar($editada['titulo'] === $base['titulo'] . ' ampliada', 'El nombre editado no quedó en la sesión.');
esperar(in_array('EAM', $editada['institutos'], true) && in_array('FACFA', $editada['institutos'], true), 'Los organismos de la actividad no quedaron actualizados.');

$cierre = $servicio->actualizarTarea($adminUsuario, [
    'tarea_id' => 'TAR-008-01',
    'estado' => 'Finalizada',
    'avance' => '40',
    'observaciones' => 'Cierre de prueba.',
]);
$cerrada = $servicio->contexto($adminUsuario)['tareas_por_id']['TAR-008-01'];
esperar($cierre['tipo'] === 'ok' && $cerrada['estado'] === 'Finalizada' && (int) $cerrada['avance'] === 100, 'La cumplimentación no forzó el avance al 100 %.');
esperar($cerrada['fecha_cumplimiento'] === hoy()->format('Y-m-d') && $cerrada['cumplida_dentro_plazo_original'] === true, 'No quedó registrado que la tarea se cumplió dentro del plazo original.');

$fechaActividad = $servicio->contexto($adminUsuario)['actividades_por_id']['ACT-2026-007']['fecha'];
$vencida = $servicio->contexto($adminUsuario)['tareas_por_id']['TAR-007-01'];
$inicioOriginal = $vencida['fecha_inicio'];
$vencimientoOriginal = $vencida['fecha_vencimiento_original'];
$estadoOriginal = $vencida['estado'];
$primera = fecha_desde_hoy(30);
$reprogramada = $servicio->reprogramarTarea($adminUsuario, [
    'tarea_id' => 'TAR-007-01',
    'fecha_limite' => $primera,
    'motivo' => 'La sede no confirma la recepción en la fecha prevista.',
]);
$trasPrimera = $servicio->contexto($adminUsuario)['tareas_por_id']['TAR-007-01'];
esperar($reprogramada['tipo'] === 'ok', 'La reprogramación no se aplicó.');
esperar($trasPrimera['fecha_inicio'] === $inicioOriginal, 'La reprogramación cambió la fecha de inicio.');
esperar($trasPrimera['fecha_vencimiento_original'] === $vencimientoOriginal, 'La reprogramación pisó el vencimiento original.');
esperar($trasPrimera['fecha_limite'] === $primera, 'No quedó el vencimiento vigente.');
esperar($trasPrimera['estado'] === $estadoOriginal && $trasPrimera['fecha_cumplimiento'] === null, 'La reprogramación se trató como cumplimentación.');
esperar(count($trasPrimera['reprogramaciones']) === 1, 'No se guardó el historial de la primera prórroga.');
$luzProrroga = semaforo_tarea($trasPrimera);
esperar($luzProrroga['clave'] === 'amarillo' && $luzProrroga['motivo'] === 'prorroga', 'La primera prórroga debe pasar de inmediato a amarillo.');
esperar($luzProrroga['clave'] !== 'verde', 'Una prórroga vigente no puede mostrarse en verde.');

$segunda = fecha_desde_hoy(45);
$otra = $servicio->reprogramarTarea($adminUsuario, [
    'tarea_id' => 'TAR-007-01',
    'fecha_limite' => $segunda,
    'motivo' => 'Se extiende otra vez por disponibilidad de la tripulación.',
]);
$trasSegunda = $servicio->contexto($adminUsuario)['tareas_por_id']['TAR-007-01'];
esperar($otra['tipo'] === 'ok' && count($trasSegunda['reprogramaciones']) === 2, 'La segunda reprogramación no conservó la anterior.');
esperar($trasSegunda['reprogramaciones'][0]['fecha_nueva'] === $primera && $trasSegunda['reprogramaciones'][1]['fecha_anterior'] === $primera, 'El historial de vencimientos quedó incompleto.');
esperar($trasSegunda['fecha_inicio'] === $inicioOriginal && $trasSegunda['fecha_vencimiento_original'] === $vencimientoOriginal, 'La segunda prórroga reinició el plazo original.');
$luzSegunda = semaforo_tarea($trasSegunda);
esperar($luzSegunda['clave'] === 'amarillo' && $luzSegunda['motivo'] === 'prorroga', 'Durante toda la prórroga vigente el indicador permanece en amarillo.');
esperar($servicio->contexto($adminUsuario)['actividades_por_id']['ACT-2026-007']['fecha'] === $fechaActividad, 'La prórroga modificó la finalización prevista de la actividad.');

$fueraDePlazo = $servicio->actualizarTarea($adminUsuario, [
    'tarea_id' => 'TAR-007-01',
    'estado' => 'Finalizada',
    'avance' => '100',
    'observaciones' => 'Cerrada después de la prórroga.',
]);
$cumplidaTarde = $servicio->contexto($adminUsuario)['tareas_por_id']['TAR-007-01'];
esperar($fueraDePlazo['tipo'] === 'ok' && $cumplidaTarde['cumplida_dentro_plazo_original'] === false, 'No se distinguió una cumplimentación posterior al plazo original.');
esperar(count($cumplidaTarde['reprogramaciones']) === 2, 'Cumplimentar la tarea borró el historial de reprogramaciones.');
esperar(semaforo_tarea($cumplidaTarde)['clave'] === 'cumplida', 'La tarea cumplimentada debe dejar de generar alertas.');

$fechaEjercicio = $servicio->contexto($adminUsuario)['actividades_por_id']['ACT-2026-002']['fecha'];
$venceHoy = $servicio->reprogramarTarea($adminUsuario, [
    'tarea_id' => 'TAR-002-01',
    'fecha_limite' => fecha_desde_hoy(0),
    'motivo' => 'Se concede el día de hoy como segunda oportunidad.',
]);
$enElDia = semaforo_tarea($servicio->contexto($adminUsuario)['tareas_por_id']['TAR-002-01']);
esperar($venceHoy['tipo'] === 'ok' && $enElDia['motivo'] === 'prorroga', 'El día del nuevo vencimiento la prórroga sigue en amarillo.');
$incumplida = $servicio->reprogramarTarea($adminUsuario, [
    'tarea_id' => 'TAR-002-01',
    'fecha_limite' => fecha_desde_hoy(-1),
    'motivo' => 'Se registra una fecha que ya quedó atrás para comprobar el rojo.',
]);
$enRojo = semaforo_tarea($servicio->contexto($adminUsuario)['tareas_por_id']['TAR-002-01']);
esperar($incumplida['tipo'] === 'ok' && $enRojo['clave'] === 'rojo' && $enRojo['motivo'] === 'prorroga_incumplida', 'Al día siguiente de incumplir la prórroga debe pasar a rojo.');
$recuperada = $servicio->reprogramarTarea($adminUsuario, [
    'tarea_id' => 'TAR-002-01',
    'fecha_limite' => fecha_desde_hoy(20),
    'motivo' => 'Se concede una nueva fecha después del incumplimiento.',
]);
$deNuevo = $servicio->contexto($adminUsuario)['tareas_por_id']['TAR-002-01'];
esperar($recuperada['tipo'] === 'ok' && semaforo_tarea($deNuevo)['motivo'] === 'prorroga', 'Una nueva prórroga debe volver a amarillo.');
esperar(situacion_desde_tareas([$deNuevo])['clave'] === 'amarillo', 'Una prórroga vigente, sin tareas en rojo, debe verse como seguimiento.');
esperar(count($deNuevo['reprogramaciones']) === 3, 'El historial de prórrogas quedó incompleto.');
esperar($deNuevo['reprogramaciones'][0]['fecha_nueva'] === fecha_desde_hoy(0) && $deNuevo['reprogramaciones'][2]['usuario_id'] === 'u-quelvo', 'El historial no conservó fechas, orden ni usuario.');
esperar($servicio->contexto($adminUsuario)['actividades_por_id']['ACT-2026-002']['fecha'] === $fechaEjercicio, 'La prórroga de la tarea cambió la fecha de la actividad.');

$_SESSION['user_id'] = 'u-herrera';
$responsable = $servicio->usuarioActual();
$rechazo = $servicio->reprogramarTarea($responsable, [
    'tarea_id' => 'TAR-003-01',
    'fecha_limite' => fecha_desde_hoy(80),
    'motivo' => 'Intento de prórroga sin permiso de administración.',
]);
$sinPermiso = $servicio->crearActividad($responsable, [
    'titulo' => 'Actividad que no debe crearse',
    'descripcion' => 'Intento fuera de perfil.',
    'tipo' => 'Otras actividades operativas',
    'fecha_inicio' => fecha_desde_hoy(1),
    'fecha' => fecha_desde_hoy(10),
    'responsable_id' => 'u-herrera',
    'institutos' => ['FACFA'],
]);
esperar($rechazo['tipo'] === 'error' && $sinPermiso['tipo'] === 'error', 'Un perfil subordinado pudo crear o reprogramar.');
$organismoAjeno = $servicio->crearOrganismo($responsable, [
    'sigla' => 'CEOP',
    'nombre' => 'Centro que no debe crearse',
    'sede' => 'Sede ficticia',
    'descripcion' => 'Intento fuera de perfil.',
    'responsable' => 'Tomás Herrera',
]);
esperar($organismoAjeno['tipo'] === 'error', 'Un perfil subordinado pudo crear un organismo.');
$intacta = $servicio->contexto($servicio->usuarioActual())['tareas_por_id']['TAR-003-01'] ?? null;
esperar($intacta !== null && $intacta['fecha_limite'] !== fecha_desde_hoy(80), 'La reprogramación rechazada igual cambió la fecha.');

$_SESSION['user_id'] = 'u-duarte';
$consultaNoCrea = $servicio->crearTareas($servicio->usuarioActual(), [
    'actividad_id' => 'ACT-2026-014',
    'descripcion' => 'Tarea que la consulta no puede crear.',
    'fecha_inicio' => fecha_desde_hoy(1),
    'fecha_limite' => fecha_desde_hoy(12),
    'usuario_id' => 'u-herrera',
    'institutos' => ['FACFA'],
]);
esperar($consultaNoCrea['tipo'] === 'error', 'La consulta pudo crear una tarea.');

$_SESSION['user_id'] = 'u-quelvo';
$creada = $servicio->crearActividad($servicio->usuarioActual(), [
    'titulo' => 'Coordinación de prueba de la sesión',
    'descripcion' => 'Actividad creada para comprobar el alta.',
    'tipo' => 'Otras actividades operativas',
    'fecha_inicio' => fecha_desde_hoy(2),
    'fecha' => fecha_desde_hoy(1),
    'responsable_id' => 'u-quelvo',
    'observaciones' => '',
    'institutos' => ['ESGA', 'CIIFA'],
]);
esperar($creada['tipo'] === 'ok' && isset($creada['destino']), 'No se creó la actividad de la sesión.');
$nuevaId = '';
if (preg_match('/id=(ACT-S-\d{3})/', (string) ($creada['destino'] ?? ''), $coincidencia) === 1) {
    $nuevaId = $coincidencia[1];
}
$fichaNueva = $nuevaId === '' ? null : ($servicio->contexto($servicio->usuarioActual())['actividades_por_id'][$nuevaId] ?? null);
esperar(is_array($fichaNueva) && $fichaNueva['fecha_inicio'] > $fichaNueva['fecha'], 'Una actividad con fechas incoherentes no debería rechazarse en el alta.');
$tareasAntes = count($servicio->contexto($servicio->usuarioActual())['tareas']);
$altaTareas = $servicio->crearTareas($servicio->usuarioActual(), [
    'actividad_id' => $nuevaId,
    'descripcion' => 'Preparar la participación de cada organismo.',
    'fecha_inicio' => fecha_desde_hoy(0),
    'fecha_limite' => fecha_desde_hoy(9),
    'usuario_id' => 'u-herrera',
    'institutos' => ['ESGA', 'CIIFA'],
]);
$despuesTareas = $servicio->contexto($servicio->usuarioActual());
$propias = array_values(array_filter(
    $despuesTareas['tareas'],
    static fn (array $tarea): bool => $tarea['actividad_id'] === $nuevaId
));
esperar($altaTareas['tipo'] === 'ok' && count($propias) === 2, 'No se creó una tarea independiente por organismo.');
esperar(count($despuesTareas['tareas']) === $tareasAntes + 2, 'El alta de tareas no incrementó el catálogo.');
$organismosNuevos = array_column($propias, 'instituto_id');
sort($organismosNuevos);
esperar($organismosNuevos === ['CIIFA', 'ESGA'], 'Las tareas nuevas no quedaron asignadas a los organismos elegidos.');
esperar($propias[0]['fecha_vencimiento_original'] === $propias[0]['fecha_limite'] && $propias[0]['estado'] === 'Pendiente', 'La tarea nueva no conservó su vencimiento original.');

$altaOrganismo = $servicio->crearOrganismo($servicio->usuarioActual(), [
    'sigla' => 'CEOP',
    'nombre' => 'Centro de Enlace Operativo de prueba',
    'sede' => 'Sede ficticia de enlace',
    'descripcion' => 'Organismo ficticio creado para comprobar el alta.',
    'responsable' => 'Nora Vidal',
]);
$conOrganismo = $servicio->contexto($servicio->usuarioActual());
$organismoNuevo = null;
foreach ($conOrganismo['institutos'] as $institutoCreado) {
    if (($institutoCreado['sigla'] ?? '') === 'CEOP') {
        $organismoNuevo = $institutoCreado;
    }
}
$responsableNuevo = null;
foreach ($conOrganismo['usuarios'] as $personaCreada) {
    if ($organismoNuevo !== null && $personaCreada['instituto_id'] === $organismoNuevo['id'] && $personaCreada['perfil'] === 'responsable') {
        $responsableNuevo = $personaCreada;
    }
}
$asignables = array_column($conOrganismo['institutos_asignables'], 'id');
esperar($altaOrganismo['tipo'] === 'ok' && $organismoNuevo !== null && $organismoNuevo['id'] !== 'CEOP', 'No se separó el identificador interno de la sigla.');
esperar($organismoNuevo !== null && in_array($organismoNuevo['id'], $asignables, true) && instituto_activo($organismoNuevo), 'El organismo nuevo no quedó disponible para los formularios.');
esperar($responsableNuevo !== null && $responsableNuevo['nombre'] === 'Nora Vidal', 'No se asoció el responsable al organismo.');
$idOrganismo = $organismoNuevo['id'] ?? '';
$actividadOrganismo = $servicio->crearActividad($servicio->usuarioActual(), [
    'titulo' => 'Coordinación con el organismo incorporado',
    'descripcion' => 'Actividad para asignar una tarea al organismo nuevo.',
    'tipo' => 'Otras actividades operativas',
    'fecha_inicio' => fecha_desde_hoy(1),
    'fecha' => fecha_desde_hoy(15),
    'responsable_id' => 'u-quelvo',
    'observaciones' => '',
    'institutos' => [$idOrganismo],
]);
$organismoActividad = '';
if (preg_match('/id=(ACT-S-\d{3})/', (string) ($actividadOrganismo['destino'] ?? ''), $coincidenciaOrganismo) === 1) {
    $organismoActividad = $coincidenciaOrganismo[1];
}
$tareaOrganismo = $servicio->crearTareas($servicio->usuarioActual(), [
    'actividad_id' => $organismoActividad,
    'descripcion' => 'Confirmar el enlace del organismo incorporado.',
    'fecha_inicio' => fecha_desde_hoy(1),
    'fecha_limite' => fecha_desde_hoy(12),
    'usuario_id' => '',
    'institutos' => [$idOrganismo],
]);
$tareasOrganismo = array_values(array_filter(
    $servicio->contexto($servicio->usuarioActual())['tareas'],
    static fn (array $tarea): bool => $tarea['actividad_id'] === $organismoActividad
));
esperar($tareaOrganismo['tipo'] === 'ok' && count($tareasOrganismo) === 1 && $tareasOrganismo[0]['instituto_id'] === $idOrganismo && $tareasOrganismo[0]['usuario_id'] === ($responsableNuevo['id'] ?? ''), 'No se asignó la tarea al organismo nuevo.');
$desactivado = $servicio->editarOrganismo($servicio->usuarioActual(), [
    'organismo_id' => $idOrganismo,
    'nombre' => 'Centro de Enlace Operativo de prueba',
    'sede' => 'Sede ficticia de enlace',
    'descripcion' => 'Organismo ficticio creado para comprobar el alta.',
    'responsable' => 'Nora Vidal',
]);
$trasBaja = $servicio->contexto($servicio->usuarioActual());
$sigueLaTarea = false;
foreach ($trasBaja['tareas'] as $tareaConservada) {
    if ($tareaConservada['actividad_id'] === $organismoActividad) {
        $sigueLaTarea = true;
    }
}
esperar($desactivado['tipo'] === 'ok' && instituto_activo($trasBaja['institutos'][$idOrganismo]) === false && $trasBaja['institutos'][$idOrganismo]['id'] === $idOrganismo, 'No se pudo desactivar el organismo.');
esperar(!isset($trasBaja['actividades_por_id'][$organismoActividad]) && !in_array($organismoActividad, array_column($trasBaja['actividades'], 'id'), true), 'La actividad sin dependencias sigue en el tablero.');
esperar($sigueLaTarea === false, 'La tarea de una dependencia desactivada sigue en el tablero.');
esperar(!in_array($idOrganismo, array_column($trasBaja['institutos_asignables'], 'id'), true), 'El organismo desactivado sigue ofrecido para nuevas asignaciones.');
$rechazoTarea = $servicio->crearTareas($servicio->usuarioActual(), [
    'actividad_id' => $organismoActividad,
    'descripcion' => 'Esta tarea no debe asignarse a un organismo desactivado.',
    'fecha_inicio' => fecha_desde_hoy(1),
    'fecha_limite' => fecha_desde_hoy(12),
    'institutos' => [$idOrganismo],
]);
esperar($rechazoTarea['tipo'] === 'error', 'Se asignó una tarea nueva a un organismo desactivado.');

$siglaCorregida = $servicio->editarOrganismo($servicio->usuarioActual(), [
    'organismo_id' => $idOrganismo,
    'sigla' => 'ENOP',
    'nombre' => 'Centro de Enlace Operativo de prueba',
    'sede' => 'Sede ficticia de enlace',
    'descripcion' => 'Organismo ficticio creado para comprobar el alta.',
    'responsable' => 'Nora Vidal',
    'activo' => '1',
]);
$trasSigla = $servicio->contexto($servicio->usuarioActual());
esperar($siglaCorregida['tipo'] === 'ok' && ($trasSigla['institutos'][$idOrganismo]['sigla'] ?? '') === 'ENOP' && ($trasSigla['institutos'][$idOrganismo]['id'] ?? '') === $idOrganismo, 'La sigla no pudo corregirse sin cambiar el identificador.');
$tareaRestaurada = false;
foreach ($trasSigla['tareas'] as $tareaRestauradaFila) {
    if ($tareaRestauradaFila['actividad_id'] === $organismoActividad && $tareaRestauradaFila['instituto_id'] === $idOrganismo) {
        $tareaRestaurada = true;
    }
}
esperar(sigla_instituto($trasSigla['institutos'], $idOrganismo) === 'ENOP', 'La sigla modificada no quedó disponible para las actividades.');
esperar(!isset($trasSigla['actividades_por_id'][$organismoActividad]) && $tareaRestaurada === false, 'Reactivar la dependencia recuperó una actividad que ya no tenía participantes.');
$bajaOrganismo = $servicio->darDeBajaOrganismo($servicio->usuarioActual(), [
    'organismo_id' => $idOrganismo,
    'motivo' => 'Baja de demostración del organismo incorporado.',
]);
$trasBajaLogica = $servicio->contexto($servicio->usuarioActual());
$dependientes = array_column($trasBajaLogica['institutos_dependientes'], 'id');
$actividadSinDependencia = $trasBajaLogica['actividades_por_id'][$organismoActividad] ?? null;
esperar($bajaOrganismo['tipo'] === 'ok' && !in_array($idOrganismo, $dependientes, true) && isset($trasBajaLogica['institutos'][$idOrganismo]), 'La baja no retiró el organismo de la operación.');
$tareaOculta = false;
foreach ($trasBajaLogica['tareas'] as $tareaOcultaFila) {
    if ($tareaOcultaFila['instituto_id'] === $idOrganismo) {
        $tareaOculta = true;
    }
}
esperar($actividadSinDependencia === null, 'La actividad sin dependencias sigue en el tablero después de la baja.');
esperar($tareaOculta === false, 'La baja del organismo dejó su tarea en el tablero.');
$bajaActividad = $servicio->darDeBajaActividad($servicio->usuarioActual(), [
    'actividad_id' => $organismoActividad,
    'motivo' => 'Baja de demostración de la actividad vinculada.',
]);
$trasActividad = $servicio->contexto($servicio->usuarioActual());
esperar($bajaActividad['tipo'] === 'ok' && !isset($trasActividad['actividades_por_id'][$organismoActividad]) === false && !in_array($organismoActividad, array_column($trasActividad['actividades'], 'id'), true), 'La actividad dada de baja sigue en el listado operativo.');
esperar(isset($trasActividad['actividades_por_id'][$organismoActividad]) && registro_baja($trasActividad['actividades_por_id'][$organismoActividad]), 'La actividad dada de baja perdió su antecedente.');
$idTareaBaja = $tareasOrganismo[0]['id'] ?? '';
$bajaTarea = $servicio->darDeBajaTarea($servicio->usuarioActual(), [
    'tarea_id' => $idTareaBaja,
    'motivo' => 'Baja de demostración de la tarea vinculada.',
]);
$trasTarea = $servicio->contexto($servicio->usuarioActual());
esperar($bajaTarea['tipo'] === 'ok', 'No se pudo dar de baja la tarea de la dependencia retirada.');
$operaciones = array_column($trasTarea['auditoria'], 'operacion');
esperar(in_array('baja', $operaciones, true) && in_array('alta', $operaciones, true), 'El historial de la sesión no registró las operaciones.');
$ajena = $servicio->darDeBajaTarea($responsable, [
    'tarea_id' => 'TAR-002-01',
    'motivo' => 'Un subordinado no puede dar de baja.',
]);
esperar($ajena['tipo'] === 'error', 'Un perfil subordinado pudo dar de baja una tarea.');

$servicio->editarOrganismo($servicio->usuarioActual(), [
    'organismo_id' => 'FACFA',
    'nombre' => 'Facultad de la Fuerza Aérea',
    'descripcion' => '',
    'responsable' => 'Tomás Herrera',
]);
$servicio->editarOrganismo($servicio->usuarioActual(), [
    'organismo_id' => 'EAM',
    'nombre' => 'Escuela de Aviación Militar',
    'descripcion' => '',
    'responsable' => 'Lucía Benítez',
]);
$sinParticipantes = $servicio->contexto($servicio->usuarioActual());
$idsSinParticipantes = array_column($sinParticipantes['actividades'], 'id');
esperar(!in_array('ACT-2026-003', $idsSinParticipantes, true) && !isset($sinParticipantes['actividades_por_id']['ACT-2026-003']), 'La actividad que solo tenía dependencias retiradas sigue en el tablero.');
esperar(!isset($sinParticipantes['tareas_por_id']['TAR-003-01']) && !isset($sinParticipantes['tareas_por_id']['TAR-003-02']) && !in_array('REQ-009', array_column($sinParticipantes['requerimientos'], 'id'), true), 'Las tareas o requerimientos de una actividad sin dependencias siguen en el tablero.');
esperar(in_array('ACT-2026-007', $idsSinParticipantes, true) && isset($sinParticipantes['tareas_por_id']['TAR-007-01']) && !isset($sinParticipantes['tareas_por_id']['TAR-007-02']), 'Una actividad con alguna dependencia vigente no conservó solo a esa dependencia.');
esperar(responsable_en_tablero($sinParticipantes['usuarios']['u-herrera'], $sinParticipantes['institutos']) === false && responsable_en_tablero($sinParticipantes['usuarios']['u-benitez'], $sinParticipantes['institutos']) === false, 'El selector sigue ofreciendo responsables de dependencias retiradas.');
esperar(responsable_en_tablero($sinParticipantes['usuarios']['u-molina'], $sinParticipantes['institutos']), 'El selector ocultó un responsable de una dependencia vigente.');

$_SESSION['csrf'] = 'prueba';
$servicio->procesarPost([
    'csrf' => 'prueba',
    'accion' => 'restaurar',
    'volver_r' => 'panel',
]);
$restaurado = $servicio->contexto($servicio->usuarioActual());
esperar(count($restaurado['actividades']) === $cantidadInicial, 'Descartar la sesión no volvió a las actividades de ejemplo.');
esperar(count($restaurado['tareas']) === $tareasIniciales, 'Descartar la sesión no volvió a las tareas de ejemplo.');
esperar($restaurado['actividades_por_id']['ACT-2026-010']['titulo'] === 'Requerimiento de material de instrucción', 'Descartar la sesión conservó una edición.');
esperar(!isset($restaurado['institutos']['CEOP']), 'Descartar la sesión conservó un organismo creado.');
esperar($restaurado['tareas_por_id']['TAR-007-01']['reprogramaciones'] === [], 'Descartar la sesión conservó una reprogramación.');

if ($errores === []) {
    $porSituacion = ['rojo' => 0, 'amarillo' => 0, 'verde' => 0, 'finalizada' => 0];
    $preparadas = [];
    foreach ($admin['actividades'] as $actividadPrueba) {
        $actividadPrueba['situacion'] = situacion_desde_tareas(tareas_operativas($admin['tareas_por_actividad'][$actividadPrueba['id']] ?? []));
        $porSituacion[$actividadPrueba['situacion']['clave']]++;
        $preparadas[] = $actividadPrueba;
    }
    $filtroSituacion = ['q' => '', 'tipo' => '', 'estado' => '', 'instituto' => '', 'plazo' => '', 'req' => '', 'situacion' => ''];
    foreach ($porSituacion as $claveSituacion => $cantidadSituacion) {
        $filtroSituacion['situacion'] = $claveSituacion;
        esperar(count(filtrar_actividades($preparadas, $filtroSituacion)) === $cantidadSituacion, 'El filtro de situación ' . $claveSituacion . ' debe coincidir con el panel.');
    }
    $sinResolver = count(array_filter($admin['requerimientos'], static fn (array $requerimiento): bool => $requerimiento['estado'] !== 'Resuelto'));
    esperar(count(filtrar_requerimientos($admin['requerimientos'], ['q' => '', 'tipo' => '', 'estado' => 'sin_resolver', 'instituto' => ''])) === $sinResolver, 'Los requerimientos sin resolver deben coincidir con el panel.');
    echo "OK actividades=" . count($admin['actividades']) . " vencidas=" . $admin['kpis']['vencidas'] . " proximas=" . $admin['kpis']['proximas'] . " finalizadas=" . $admin['kpis']['finalizadas'] . PHP_EOL;
    exit(0);
}

fwrite(STDERR, implode(PHP_EOL, $errores) . PHP_EOL);
exit(1);
