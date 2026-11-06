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
        return ['clave' => 'cumplida', 'etiqueta' => 'Cumplida'];
    }

    $dias = dias_hasta($fecha);
    if ($dias < 0) {
        return ['clave' => 'rojo', 'etiqueta' => 'Vencida'];
    }
    if ($dias <= 7) {
        return ['clave' => 'amarillo', 'etiqueta' => 'Por vencer'];
    }

    return ['clave' => 'verde', 'etiqueta' => 'En plazo'];
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

function fecha_es(string $fecha): string
{
    $momento = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);
    if (!$momento instanceof DateTimeImmutable) {
        return $fecha;
    }

    return $momento->format('d/m/Y');
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
    $rutas = ['panel', 'actividades', 'actividad', 'requerimientos', 'auditoria', 'institutos'];
    $ruta = (string) ($entrada['volver_r'] ?? 'panel');
    if (!in_array($ruta, $rutas, true)) {
        $ruta = 'panel';
    }

    $params = [];
    $id = (string) ($entrada['volver_id'] ?? '');
    if ($ruta === 'actividad' && preg_match('/^ACT-2026-\d{3}$/', $id) === 1) {
        $params['id'] = $id;
    }

    return url_de($ruta, $params);
}

function campos_volver(string $ruta, ?string $id = null): string
{
    $html = '<input type="hidden" name="volver_r" value="' . e($ruta) . '">';
    if ($id !== null) {
        $html .= '<input type="hidden" name="volver_id" value="' . e($id) . '">';
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

        return true;
    }));
}

function filtrar_requerimientos(array $requerimientos, array $filtros): array
{
    return array_values(array_filter($requerimientos, static function (array $requerimiento) use ($filtros): bool {
        if ($filtros['estado'] !== '' && $requerimiento['estado'] !== $filtros['estado']) {
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

function filtrar_auditoria(array $filas, array $filtros): array
{
    return array_values(array_filter($filas, static function (array $fila) use ($filtros): bool {
        if ($filtros['instituto'] !== '' && $fila['instituto_id'] !== $filtros['instituto'] && $fila['ambito_id'] !== $filtros['instituto']) {
            return false;
        }
        if ($filtros['q'] !== ''
            && !contiene($fila['entidad'] . ' ' . $fila['campo'] . ' ' . $fila['anterior'] . ' ' . $fila['nuevo'], $filtros['q'])
        ) {
            return false;
        }

        return true;
    }));
}
