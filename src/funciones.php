<?php

declare(strict_types=1);

function e(string|int|float|null $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function longitud(string $texto): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($texto, 'UTF-8');
    }

    $cantidad = preg_match_all('/./u', $texto);

    return $cantidad === false ? strlen($texto) : $cantidad;
}

function contiene(string $texto, string $busqueda): bool
{
    if ($busqueda === '') {
        return true;
    }

    if (function_exists('mb_stripos')) {
        return mb_stripos($texto, $busqueda, 0, 'UTF-8') !== false;
    }

    return stripos($texto, $busqueda) !== false;
}

function hoy(): DateTimeImmutable
{
    return new DateTimeImmutable('today');
}

function fecha_desde_hoy(int $dias): string
{
    return hoy()->modify(sprintf('%+d days', $dias))->format('Y-m-d');
}

function momento_desde_hoy(int $dias, string $hora): string
{
    return fecha_desde_hoy($dias) . ' ' . $hora;
}

function dias_hasta(string $fecha): int
{
    $limite = new DateTimeImmutable($fecha);

    return (int) hoy()->diff($limite)->format('%r%a');
}

function semaforo(string $fecha, string $estado): array
{
    if ($estado === 'Finalizada') {
        return ['clave' => 'cumplida', 'motivo' => 'cumplida', 'etiqueta' => 'Cumplida'];
    }

    $dias = dias_hasta($fecha);
    if ($dias < 0) {
        return ['clave' => 'rojo', 'motivo' => 'incumplido', 'etiqueta' => 'Vencida'];
    }
    if ($dias <= 7) {
        return ['clave' => 'amarillo', 'motivo' => 'proximo', 'etiqueta' => 'Por vencer'];
    }

    return ['clave' => 'verde', 'motivo' => 'en_plazo', 'etiqueta' => 'En plazo'];
}

function dias_entre(string $desde, string $hasta): int
{
    $inicio = new DateTimeImmutable($desde);
    $fin = new DateTimeImmutable($hasta);

    return (int) $inicio->diff($fin)->format('%r%a');
}

function semaforo_tarea(array $tarea): array
{
    if (($tarea['estado'] ?? '') === 'Finalizada') {
        return ['clave' => 'cumplida', 'motivo' => 'cumplida', 'etiqueta' => 'Cumplimentada'];
    }

    $inicio = (string) ($tarea['fecha_inicio'] ?? '');
    $limite = (string) ($tarea['fecha_limite'] ?? '');
    $prorrogas = is_array($tarea['reprogramaciones'] ?? null) ? $tarea['reprogramaciones'] : [];
    if (!fecha_valida($inicio) || !fecha_valida($limite)) {
        return ['clave' => 'invalido', 'motivo' => 'invalido', 'etiqueta' => 'Plazo no válido'];
    }

    $dias = dias_hasta($limite);
    if ($prorrogas !== []) {
        if ($dias < 0) {
            return ['clave' => 'rojo', 'motivo' => 'prorroga_incumplida', 'etiqueta' => 'Vencimiento incumplido'];
        }

        return ['clave' => 'amarillo', 'motivo' => 'prorroga', 'etiqueta' => 'Prórroga vigente'];
    }

    if (!plazo_coherente($inicio, $limite)) {
        return ['clave' => 'invalido', 'motivo' => 'invalido', 'etiqueta' => 'Plazo no válido'];
    }
    if ($dias < 0) {
        return ['clave' => 'rojo', 'motivo' => 'incumplido', 'etiqueta' => 'Vencimiento incumplido'];
    }
    if ($dias <= 5) {
        return ['clave' => 'rojo', 'motivo' => 'proximo', 'etiqueta' => 'Próxima a vencer'];
    }

    $total = dias_entre($inicio, $limite);
    if ($total <= 0) {
        return ['clave' => 'invalido', 'motivo' => 'invalido', 'etiqueta' => 'Plazo no válido'];
    }
    $transcurrido = dias_entre($inicio, hoy()->format('Y-m-d'));
    if (($transcurrido / $total) >= 0.5) {
        return ['clave' => 'amarillo', 'motivo' => 'avance', 'etiqueta' => 'Avance del plazo'];
    }

    return ['clave' => 'verde', 'motivo' => 'en_plazo', 'etiqueta' => 'En plazo'];
}

function situacion_desde_tareas(array $tareas): array
{
    $hayRojo = false;
    $hayAmarillo = false;
    $abiertas = 0;
    foreach ($tareas as $tarea) {
        if (!is_array($tarea)) {
            continue;
        }
        $luz = semaforo_tarea($tarea);
        if ($luz['clave'] === 'cumplida') {
            continue;
        }
        $abiertas++;
        if ($luz['clave'] === 'rojo') {
            $hayRojo = true;
        } elseif ($luz['clave'] === 'amarillo') {
            $hayAmarillo = true;
        }
    }

    if ($tareas !== [] && $abiertas === 0) {
        return ['clave' => 'finalizada', 'etiqueta' => 'Finalizada'];
    }
    if ($hayRojo) {
        return ['clave' => 'rojo', 'etiqueta' => 'Situación crítica'];
    }
    if ($hayAmarillo) {
        return ['clave' => 'amarillo', 'etiqueta' => 'Seguimiento necesario'];
    }

    return ['clave' => 'verde', 'etiqueta' => 'Situación normal'];
}

function etiqueta_ordinal_vencimiento(int $numero): string
{
    return match ($numero) {
        1 => 'Vencimiento original',
        2 => 'Segundo vencimiento',
        3 => 'Tercer vencimiento',
        4 => 'Cuarto vencimiento',
        default => 'Vencimiento ' . $numero,
    };
}

function etiqueta_exigible(array $tarea): string
{
    $prorrogas = is_array($tarea['reprogramaciones'] ?? null) ? count($tarea['reprogramaciones']) : 0;

    return etiqueta_ordinal_vencimiento($prorrogas + 1);
}

function vencimientos_tarea(array $tarea): array
{
    $prorrogas = is_array($tarea['reprogramaciones'] ?? null) ? array_values($tarea['reprogramaciones']) : [];
    $lista = [[
        'etiqueta' => 'Vencimiento original',
        'fecha' => (string) ($tarea['fecha_vencimiento_original'] ?? ''),
        'exigible' => $prorrogas === [],
    ]];
    foreach ($prorrogas as $indice => $prorroga) {
        $lista[] = [
            'etiqueta' => etiqueta_ordinal_vencimiento($indice + 2),
            'fecha' => (string) ($prorroga['fecha_nueva'] ?? ''),
            'exigible' => $indice === count($prorrogas) - 1,
        ];
    }

    return $lista;
}

function responsable_en_tablero(array $persona, array $institutos): bool
{
    if (($persona['perfil'] ?? '') !== 'responsable') {
        return false;
    }
    $instituto = $institutos[(string) ($persona['instituto_id'] ?? '')] ?? null;

    return is_array($instituto)
        && ($instituto['tipo'] ?? '') === 'instituto'
        && instituto_activo($instituto)
        && !registro_baja($instituto);
}

function instituto_activo(array $instituto): bool
{
    if (!array_key_exists('activo', $instituto)) {
        return true;
    }

    return $instituto['activo'] === true || $instituto['activo'] === 1 || $instituto['activo'] === '1';
}

function registro_baja(array $registro): bool
{
    return ($registro['baja'] ?? false) === true;
}

function tareas_operativas(array $tareas): array
{
    return array_values(array_filter(
        $tareas,
        static fn (mixed $tarea): bool => is_array($tarea) && !registro_baja($tarea)
    ));
}

function normalizar_instituto(array $instituto): array
{
    $instituto['activo'] = instituto_activo($instituto);
    $instituto['baja'] = registro_baja($instituto);
    if (!is_string($instituto['sigla'] ?? null) || $instituto['sigla'] === '') {
        $instituto['sigla'] = (string) ($instituto['id'] ?? '');
    }

    return $instituto;
}

function texto_plazo(string $fecha, string $estado): string
{
    if ($estado === 'Finalizada') {
        return 'Finalizada';
    }

    $dias = dias_hasta($fecha);
    if ($dias === 0) {
        return 'Vence hoy';
    }
    if ($dias === 1) {
        return 'Falta 1 día';
    }
    if ($dias > 1) {
        return 'Faltan ' . $dias . ' días';
    }
    if ($dias === -1) {
        return 'Venció hace 1 día';
    }

    return 'Venció hace ' . abs($dias) . ' días';
}

function fecha_valida(string $fecha): bool
{
    $momento = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

    return $momento instanceof DateTimeImmutable && $momento->format('Y-m-d') === $fecha;
}

function plazo_coherente(string $inicio, string $fin): bool
{
    return fecha_valida($inicio) && fecha_valida($fin) && $inicio <= $fin;
}

function id_actividad_valido(string $id): bool
{
    return preg_match('/^(?:ACT-2026-\d{3}|ACT-S-\d{3})$/', $id) === 1;
}

function id_tarea_valido(string $id): bool
{
    return preg_match('/^(?:TAR-\d{3}-\d{2}|TAR-S-\d{3})$/', $id) === 1;
}

function normalizar_actividad(array $actividad): array
{
    $fin = is_string($actividad['fecha'] ?? null) ? $actividad['fecha'] : '';
    if (!is_string($actividad['fecha_inicio'] ?? null) || $actividad['fecha_inicio'] === '') {
        $actividad['fecha_inicio'] = $fin;
    }
    if (!is_array($actividad['institutos'] ?? null)) {
        $actividad['institutos'] = [];
    }
    $actividad['baja'] = registro_baja($actividad);

    return $actividad;
}

function normalizar_tarea(array $tarea): array
{
    $limite = is_string($tarea['fecha_limite'] ?? null) ? $tarea['fecha_limite'] : '';
    if (!is_string($tarea['fecha_inicio'] ?? null) || $tarea['fecha_inicio'] === '') {
        $tarea['fecha_inicio'] = $limite;
    }
    if (!is_string($tarea['fecha_vencimiento_original'] ?? null) || $tarea['fecha_vencimiento_original'] === '') {
        $tarea['fecha_vencimiento_original'] = $limite;
    }
    if (!is_array($tarea['reprogramaciones'] ?? null)) {
        $tarea['reprogramaciones'] = [];
    } else {
        $tarea['reprogramaciones'] = array_values($tarea['reprogramaciones']);
    }
    if (!is_string($tarea['fecha_cumplimiento'] ?? null) || !fecha_valida($tarea['fecha_cumplimiento'])) {
        $tarea['fecha_cumplimiento'] = null;
    }
    if (!is_bool($tarea['cumplida_dentro_plazo_original'] ?? null)) {
        $tarea['cumplida_dentro_plazo_original'] = null;
    }
    $tarea['baja'] = registro_baja($tarea);

    return $tarea;
}

function fecha_es(string $fecha): string
{
    $momento = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);
    if (!$momento instanceof DateTimeImmutable) {
        return $fecha;
    }

    return $momento->format('d/m/Y');
}

function fecha_compacta(DateTimeImmutable $fecha): string
{
    $meses = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];

    return $fecha->format('d') . ' ' . $meses[(int) $fecha->format('n')] . ' ' . $fecha->format('Y');
}

function fecha_dia_mes(string $fecha): array
{
    $meses = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
    $momento = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);
    if (!$momento instanceof DateTimeImmutable) {
        return ['dia' => '', 'mes' => ''];
    }

    return ['dia' => $momento->format('d'), 'mes' => $meses[(int) $momento->format('n')]];
}

function icono(string $nombre): string
{
    $trazos = [
        'inicio' => '<path d="M4 11 12 4l8 7"/><path d="M7 10.5V20h10v-9.5"/>',
        'lista' => '<path d="M8 7h11"/><path d="M8 12h11"/><path d="M8 17h11"/><path d="M4 7h.01"/><path d="M4 12h.01"/><path d="M4 17h.01"/>',
        'documento' => '<path d="M7 3.5h7l4 4V20.5H7z"/><path d="M14 3.5V8h4"/><path d="M9.5 12.5h5"/><path d="M9.5 16h5"/>',
        'edificio' => '<path d="M4 20.5h16"/><path d="M6 20.5V5.5h8v15"/><path d="M14 9.5h4v11"/><path d="M8.5 9h2"/><path d="M8.5 13h2"/><path d="M8.5 17h2"/>',
        'alerta' => '<path d="M12 4 3.5 19h17z"/><path d="M12 9.5v4"/><path d="M12 16.5h.01"/>',
        'reloj' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4.5l3 2"/>',
        'ok' => '<circle cx="12" cy="12" r="8"/><path d="m8.5 12.2 2.3 2.3 4.7-5"/>',
        'bandera' => '<path d="M6 20.5V4"/><path d="M6 5h10.5L14 8.2 16.5 11.5H6"/>',
        'campana' => '<path d="M6 16.5h12"/><path d="M7.5 16.5V11a4.5 4.5 0 0 1 9 0v5.5"/><path d="M10 16.5a2 2 0 0 0 4 0"/>',
        'calendario' => '<rect x="4" y="5.5" width="16" height="14" rx="2"/><path d="M8 3.5v4"/><path d="M16 3.5v4"/><path d="M4 10h16"/>',
        'capas' => '<path d="m12 4 8 4-8 4-8-4z"/><path d="m4 12 8 4 8-4"/><path d="m4 16 8 4 8-4"/>',
        'flecha' => '<path d="M9 6l6 6-6 6"/>',
        'info' => '<circle cx="12" cy="12" r="8"/><path d="M12 11v5"/><path d="M12 8h.01"/>',
        'sol' => '<circle cx="12" cy="12" r="3.6"/><path d="M12 2.8v2.1M12 19.1v2.1M2.8 12h2.1M19.1 12h2.1M5.1 5.1l1.5 1.5M17.4 17.4l1.5 1.5M18.9 5.1l-1.5 1.5M6.6 17.4l-1.5 1.5"/>',
        'luna' => '<path d="M16.2 3.2a7.6 7.6 0 1 0 4.4 10.6A6.1 6.1 0 0 1 16.2 3.2z"/>',
    ];
    $trazo = $trazos[$nombre] ?? '';

    return '<svg class="icono" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $trazo . '</svg>';
}

function fecha_hora_es(string $cuando): string
{
    $momento = DateTimeImmutable::createFromFormat('Y-m-d H:i', $cuando);
    if (!$momento instanceof DateTimeImmutable) {
        return $cuando;
    }

    return $momento->format('d/m/Y H:i');
}

function fecha_larga(DateTimeImmutable $fecha): string
{
    $meses = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',
    ];

    return (int) $fecha->format('j') . ' de ' . $meses[(int) $fecha->format('n')] . ' de ' . $fecha->format('Y');
}

function tipos_actividad(): array
{
    return [
        'Ceremonias y actos oficiales',
        'Comisiones y desplazamientos',
        'Vuelos programados',
        'Reuniones institucionales',
        'Ejercicios y actividades académicas',
        'Requerimientos administrativos',
        'Otras actividades operativas',
    ];
}

function estados_actividad(): array
{
    return ['Planificada', 'En curso', 'Finalizada'];
}

function estados_tarea(): array
{
    return ['Pendiente', 'En curso', 'Bloqueada', 'Finalizada'];
}

function tipos_requerimiento(): array
{
    return [
        'Necesidad de recursos',
        'Solicitud a la autoridad central',
        'Requerimiento a otro instituto',
        'Impedimento',
    ];
}

function estados_requerimiento(): array
{
    return ['Pendiente', 'En atención', 'Resuelto'];
}

function clase_estado(string $estado): string
{
    return match ($estado) {
        'Planificada' => 'planificada',
        'En curso' => 'en-curso',
        'Finalizada' => 'finalizada',
        'Pendiente' => 'pendiente',
        'Bloqueada' => 'bloqueada',
        'En atención' => 'en-atencion',
        'Resuelto' => 'resuelto',
        default => 'otro',
    };
}

function etiqueta_perfil(string $perfil): string
{
    return match ($perfil) {
        'admin' => 'Administrador central',
        'responsable' => 'Responsable de instituto',
        'consulta' => 'Usuario de consulta',
        default => $perfil,
    };
}

function estado_desde_tareas(array $tareas): string
{
    if ($tareas === []) {
        return 'Planificada';
    }

    $todasCerradas = true;
    $algunaIniciada = false;
    foreach ($tareas as $tarea) {
        if ($tarea['estado'] !== 'Finalizada') {
            $todasCerradas = false;
        }
        if ($tarea['estado'] !== 'Pendiente' || (int) $tarea['avance'] > 0) {
            $algunaIniciada = true;
        }
    }

    if ($todasCerradas) {
        return 'Finalizada';
    }
    if ($algunaIniciada) {
        return 'En curso';
    }

    return 'Planificada';
}

function avance_desde_tareas(array $tareas): int
{
    if ($tareas === []) {
        return 0;
    }

    $suma = 0;
    foreach ($tareas as $tarea) {
        $suma += (int) $tarea['avance'];
    }

    return (int) round($suma / count($tareas));
}

function url_de(string $ruta, array $params = []): string
{
    return 'index.php?' . http_build_query(['r' => $ruta] + $params);
}

function url_volver(array $entrada): string
{
    $rutas = ['panel', 'actividades', 'actividad', 'requerimientos', 'historial', 'institutos'];
    $ruta = (string) ($entrada['volver_r'] ?? 'panel');
    if (!in_array($ruta, $rutas, true)) {
        $ruta = 'panel';
    }

    $params = [];
    $id = (string) ($entrada['volver_id'] ?? '');
    if ($ruta === 'actividad' && id_actividad_valido($id)) {
        $params['id'] = $id;
    }
    $situacion = en_lista((string) ($entrada['volver_situacion'] ?? ''), ['rojo', 'amarillo', 'verde', 'finalizada']);
    if ($situacion !== '' && ($ruta === 'actividades' || $ruta === 'actividad')) {
        $params['situacion'] = $situacion;
    }

    return url_de($ruta, $params);
}

function campos_volver(string $ruta, ?string $id = null, ?string $situacion = null): string
{
    $html = '<input type="hidden" name="volver_r" value="' . e($ruta) . '">';
    if ($id !== null) {
        $html .= '<input type="hidden" name="volver_id" value="' . e($id) . '">';
    }
    $situacion = en_lista((string) $situacion, ['rojo', 'amarillo', 'verde', 'finalizada']);
    if ($situacion !== '') {
        $html .= '<input type="hidden" name="volver_situacion" value="' . e($situacion) . '">';
    }

    return $html;
}

function campo_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['csrf'];
}

function csrf_valido(mixed $token): bool
{
    $conocido = $_SESSION['csrf'] ?? '';

    return is_string($token) && is_string($conocido) && $conocido !== '' && hash_equals($conocido, $token);
}

function texto_post(array $entrada, string $clave): ?string
{
    if (!isset($entrada[$clave]) || is_array($entrada[$clave])) {
        return null;
    }

    return trim((string) $entrada[$clave]);
}

function en_lista(string $valor, array $permitidos): string
{
    return in_array($valor, $permitidos, true) ? $valor : '';
}

function nombre_usuario(array $usuarios, string $id): string
{
    return $usuarios[$id]['nombre'] ?? $id;
}

function nombre_instituto(array $institutos, string $id): string
{
    return $institutos[$id]['nombre'] ?? $id;
}

function sigla_instituto(array $institutos, string $id): string
{
    return $institutos[$id]['sigla'] ?? $id;
}

function dependencias_visibles(array $ids, array $institutos): array
{
    $visibles = [];
    foreach ($ids as $id) {
        if (!is_string($id) || !isset($institutos[$id]) || registro_baja($institutos[$id]) || !instituto_activo($institutos[$id])) {
            continue;
        }
        $visibles[] = $institutos[$id];
    }

    return $visibles;
}

function filtrar_actividades(array $actividades, array $filtros): array
{
    return array_values(array_filter($actividades, static function (array $actividad) use ($filtros): bool {
        if ($filtros['q'] !== ''
            && !contiene($actividad['id'] . ' ' . $actividad['titulo'] . ' ' . $actividad['descripcion'], $filtros['q'])
        ) {
            return false;
        }
        if ($filtros['tipo'] !== '' && $actividad['tipo'] !== $filtros['tipo']) {
            return false;
        }
        if ($filtros['estado'] !== '' && $actividad['estado'] !== $filtros['estado']) {
            return false;
        }
        if ($filtros['instituto'] !== '' && !in_array($filtros['instituto'], $actividad['institutos'], true)) {
            return false;
        }
        $plazo = [
            'vencida' => 'rojo',
            'proxima' => 'amarillo',
            'en_plazo' => 'verde',
            'finalizada' => 'cumplida',
        ];
        if ($filtros['plazo'] !== '' && $actividad['semaforo']['clave'] !== ($plazo[$filtros['plazo']] ?? '')) {
            return false;
        }
        if ($filtros['req'] === 'pendientes' && (int) $actividad['reqs_pendientes'] === 0) {
            return false;
        }
        if (($filtros['situacion'] ?? '') !== '' && (($actividad['situacion']['clave'] ?? '') !== $filtros['situacion'])) {
            return false;
        }

        return true;
    }));
}

function filtrar_requerimientos(array $requerimientos, array $filtros): array
{
    return array_values(array_filter($requerimientos, static function (array $requerimiento) use ($filtros): bool {
        if (($filtros['estado'] ?? '') === 'sin_resolver' && $requerimiento['estado'] === 'Resuelto') {
            return false;
        }
        if ($filtros['estado'] !== '' && $filtros['estado'] !== 'sin_resolver' && $requerimiento['estado'] !== $filtros['estado']) {
            return false;
        }
        if ($filtros['tipo'] !== '' && $requerimiento['tipo'] !== $filtros['tipo']) {
            return false;
        }
        if ($filtros['instituto'] !== ''
            && $requerimiento['instituto_solicitante'] !== $filtros['instituto']
            && $requerimiento['instituto_destino'] !== $filtros['instituto']
        ) {
            return false;
        }
        if ($filtros['q'] !== ''
            && !contiene($requerimiento['id'] . ' ' . $requerimiento['descripcion'], $filtros['q'])
        ) {
            return false;
        }

        return true;
    }));
}

function etiqueta_operacion(string $operacion): string
{
    return match ($operacion) {
        'alta' => 'Alta',
        'modificacion' => 'Modificación',
        'baja' => 'Baja',
        'activacion' => 'Activación',
        'desactivacion' => 'Desactivación',
        'reasignacion' => 'Reasignación',
        'prorroga' => 'Prórroga',
        default => 'Modificación',
    };
}

function etiqueta_tipo_registro(string $tipo): string
{
    return match ($tipo) {
        'organismo' => 'Organismo',
        'actividad' => 'Actividad',
        'tarea' => 'Tarea',
        'requerimiento' => 'Requerimiento',
        default => 'Registro',
    };
}

/**
 * Completa las filas precargadas para la consulta de demostración.
 * El historial de esta etapa vive en la sesión y no es una auditoría permanente.
 */
function normalizar_historial(array $fila): array
{
    $entidad = (string) ($fila['entidad'] ?? '');
    $campo = (string) ($fila['campo'] ?? '');
    $nuevo = (string) ($fila['nuevo'] ?? '');
    $tipo = (string) ($fila['tipo_registro'] ?? '');
    if ($tipo === '') {
        $tipo = match (true) {
            str_starts_with($entidad, 'Organismo') => 'organismo',
            str_starts_with($entidad, 'Actividad') => 'actividad',
            str_starts_with($entidad, 'Tarea') => 'tarea',
            str_starts_with($entidad, 'Requerimiento') => 'requerimiento',
            default => 'otro',
        };
    }
    $operacion = (string) ($fila['operacion'] ?? '');
    if ($operacion === '') {
        $operacion = match (true) {
            str_starts_with($campo, 'Alta') => 'alta',
            $campo === 'Situación' && $nuevo === 'Activo' => 'activacion',
            $campo === 'Situación' => 'desactivacion',
            $campo === 'Organismo' || $campo === 'Responsable' => 'reasignacion',
            str_contains($campo, 'vencimiento') || $campo === 'Motivo de reprogramación' => 'prorroga',
            default => 'modificacion',
        };
    }
    if (!is_string($fila['registro_id'] ?? null) || $fila['registro_id'] === '') {
        $fila['registro_id'] = preg_match('/((?:ACT|TAR|REQ|ORG)-\S+|[A-Z]{3,8})$/', $entidad, $coincidencia) === 1
            ? $coincidencia[1]
            : $entidad;
    }
    if (!is_string($fila['registro_nombre'] ?? null) || $fila['registro_nombre'] === '') {
        $fila['registro_nombre'] = $nuevo !== '—' && $nuevo !== '' ? $nuevo : $entidad;
    }
    if (!is_string($fila['motivo'] ?? null)) {
        $fila['motivo'] = $campo === 'Motivo de reprogramación' ? $nuevo : '';
    }
    $fila['tipo_registro'] = $tipo;
    $fila['operacion'] = $operacion;
    $fila['entidad'] = $entidad;
    $fila['campo'] = $campo;
    $fila['anterior'] = (string) ($fila['anterior'] ?? '');
    $fila['nuevo'] = $nuevo;

    return $fila;
}

function filtrar_auditoria(array $filas, array $filtros): array
{
    return array_values(array_filter($filas, static function (array $fila) use ($filtros): bool {
        $fila = normalizar_historial($fila);
        if (($filtros['tipo_registro'] ?? '') !== '' && $fila['tipo_registro'] !== $filtros['tipo_registro']) {
            return false;
        }
        if (($filtros['operacion'] ?? '') !== '' && $fila['operacion'] !== $filtros['operacion']) {
            return false;
        }
        $dia = substr((string) ($fila['cuando'] ?? ''), 0, 10);
        if (($filtros['desde'] ?? '') !== '' && $dia < $filtros['desde']) {
            return false;
        }
        if (($filtros['hasta'] ?? '') !== '' && $dia > $filtros['hasta']) {
            return false;
        }
        if (($filtros['instituto'] ?? '') !== '' && $fila['instituto_id'] !== $filtros['instituto'] && $fila['ambito_id'] !== $filtros['instituto']) {
            return false;
        }
        if (($filtros['q'] ?? '') !== ''
            && !contiene(
                $fila['entidad'] . ' ' . $fila['campo'] . ' ' . $fila['anterior'] . ' ' . $fila['nuevo'] . ' ' . $fila['registro_id'] . ' ' . $fila['registro_nombre'] . ' ' . $fila['motivo'],
                $filtros['q']
            )
        ) {
            return false;
        }

        return true;
    }));
}
