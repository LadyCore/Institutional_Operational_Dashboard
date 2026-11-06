<?php

declare(strict_types=1);

/**
 * Arma el tablero y aplica los cambios de la sesión.
 * En la aplicación definitiva, actualizarTarea(), crearRequerimiento()
 * y actualizarRequerimiento() escriben en MySQL o MariaDB dentro de una
 * transacción que también inserta la auditoría. El usuario de base no
 * debería poder modificar ni borrar esas filas.
 */
final class ServicioOperativo
{
    public function __construct(private RepositorioOperativo $repositorio)
    {
    }

    public function usuarioActual(): array
    {
        $usuarios = $this->indice($this->repositorio->usuarios());
        $id = $_SESSION['user_id'] ?? 'u-quelvo';
        if (!is_string($id) || !isset($usuarios[$id])) {
            $id = 'u-quelvo';
            $_SESSION['user_id'] = $id;
        }

        return $usuarios[$id];
    }

    public function existeActividad(string $id): bool
    {
        return isset($this->indice($this->repositorio->actividades())[$id]);
    }

    public function contexto(array $usuario): array
    {
        $completo = $this->catalogo();
        $actividades = array_values(array_filter(
            $completo['actividades'],
            static fn (array $actividad): bool => Politica::veActividad($usuario, $actividad)
        ));
        $ids = array_column($actividades, 'id');
        $tareas = array_values(array_filter(
            $completo['tareas'],
            static fn (array $tarea): bool => in_array($tarea['actividad_id'], $ids, true)
        ));
        $requerimientos = array_values(array_filter(
            $completo['requerimientos'],
            static fn (array $requerimiento): bool => in_array($requerimiento['actividad_id'], $ids, true)
        ));
        $auditoria = array_values(array_filter(
            $completo['auditoria'],
            static fn (array $fila): bool => Politica::veAuditoria($usuario, $fila)
        ));

        $tareasPorActividad = [];
        $tareasPorId = [];
        foreach ($tareas as $tarea) {
            $tareasPorActividad[$tarea['actividad_id']][] = $tarea;
            $tareasPorId[$tarea['id']] = $tarea;
        }
        foreach ($tareasPorActividad as &$grupo) {
            usort($grupo, static function (array $a, array $b): int {
                $instituto = strcmp($a['instituto_id'], $b['instituto_id']);
                if ($instituto !== 0) {
                    return $instituto;
                }

                return strcmp($a['fecha_limite'], $b['fecha_limite']);
            });
        }
        unset($grupo);

        $reqsPorActividad = [];
        $reqsPorTarea = [];
        foreach ($requerimientos as $requerimiento) {
            $reqsPorActividad[$requerimiento['actividad_id']][] = $requerimiento;
            if ($requerimiento['tarea_id'] !== null) {
                $reqsPorTarea[$requerimiento['tarea_id']][] = $requerimiento;
            }
        }

        $actividadesPorId = [];
        foreach ($actividades as $actividad) {
            $actividadesPorId[$actividad['id']] = $actividad;
        }

        return [
            'usuario' => $usuario,
            'usuarios' => $completo['usuarios'],
            'directorio' => array_values($completo['usuarios']),
            'institutos' => $completo['institutos'],
            'institutos_dependientes' => array_values(array_filter(
                $completo['institutos'],
                static fn (array $instituto): bool => $instituto['tipo'] === 'instituto'
            )),
            'actividades' => $actividades,
            'actividades_por_id' => $actividadesPorId,
            'tareas' => $tareas,
            'tareas_por_actividad' => $tareasPorActividad,
            'tareas_por_id' => $tareasPorId,
            'requerimientos' => $requerimientos,
            'reqs_por_actividad' => $reqsPorActividad,
            'reqs_por_tarea' => $reqsPorTarea,
            'auditoria' => $auditoria,
            'kpis' => $this->kpis($actividades),
            'hoy' => hoy()->format('Y-m-d'),
            'hoy_largo' => fecha_larga(hoy()),
        ];
    }

    public function procesarPost(array $entrada): string
    {
        $volver = url_volver($entrada);
        if (!csrf_valido($entrada['csrf'] ?? null)) {
            $this->flash('error', 'La solicitud no pudo validarse. Vuelva a intentar.');

            return $volver;
        }

        $accion = texto_post($entrada, 'accion') ?? '';
        $usuario = $this->usuarioActual();
        $resultado = match ($accion) {
            'perfil' => $this->cambiarPerfil($entrada),
            'restaurar' => $this->restaurarSesion(),
            'tarea' => $this->actualizarTarea($usuario, $entrada),
            'requerimiento_crear' => $this->crearRequerimiento($usuario, $entrada),
            'requerimiento_estado' => $this->actualizarRequerimiento($usuario, $entrada),
            default => ['tipo' => 'error', 'texto' => 'La acción no está disponible.'],
        };
        $this->flash($resultado['tipo'], $resultado['texto']);

        if ($accion === 'perfil') {
            return $volver;
        }

        return $volver;
    }

    public function actualizarTarea(array $usuario, array $entrada): array
    {
        $id = texto_post($entrada, 'tarea_id') ?? '';
        $catalogo = $this->catalogo();
        $tarea = $catalogo['tareas_por_id'][$id] ?? null;
        if ($tarea === null) {
            return ['tipo' => 'error', 'texto' => 'La tarea no existe.'];
        }
        if (!Politica::editaTarea($usuario, $tarea)) {
            return ['tipo' => 'error', 'texto' => 'No tiene permiso para actualizar esta tarea.'];
        }

        $estado = texto_post($entrada, 'estado') ?? '';
        if (!in_array($estado, estados_tarea(), true)) {
            return ['tipo' => 'error', 'texto' => 'El estado de la tarea no es válido.'];
        }
        $avance = filter_var($entrada['avance'] ?? null, FILTER_VALIDATE_INT);
        if ($avance === false || $avance < 0 || $avance > 100) {
            return ['tipo' => 'error', 'texto' => 'El avance debe ser un entero entre 0 y 100.'];
        }
        if ($estado === 'Finalizada') {
            $avance = 100;
        }
        $observaciones = texto_post($entrada, 'observaciones') ?? '';
        if (longitud($observaciones) > 500) {
            return ['tipo' => 'error', 'texto' => 'Las observaciones admiten hasta 500 caracteres.'];
        }

        $cambios = [];
        if ($tarea['estado'] !== $estado) {
            $cambios[] = ['Estado', $tarea['estado'], $estado];
        }
        if ((int) $tarea['avance'] !== $avance) {
            $cambios[] = ['Porcentaje de avance', $tarea['avance'] . ' %', $avance . ' %'];
        }
        if ((string) $tarea['observaciones'] !== $observaciones) {
            $cambios[] = ['Observaciones', $this->mostrar($tarea['observaciones']), $this->mostrar($observaciones)];
        }
        if ($cambios === []) {
            return ['tipo' => 'info', 'texto' => 'No hubo cambios que registrar en ' . $id . '.'];
        }

        $_SESSION['tareas'][$id] = [
            'estado' => $estado,
            'avance' => $avance,
            'observaciones' => $observaciones,
        ];
        foreach ($cambios as $cambio) {
            $this->auditar($usuario, $tarea['instituto_id'], 'Tarea ' . $id, $cambio[0], $cambio[1], $cambio[2]);
        }

        return ['tipo' => 'ok', 'texto' => 'La tarea ' . $id . ' se actualizó en esta sesión.'];
    }

    public function crearRequerimiento(array $usuario, array $entrada): array
    {
        if (!Politica::creaRequerimiento($usuario)) {
            return ['tipo' => 'error', 'texto' => 'Su perfil no puede registrar requerimientos.'];
        }

        $catalogo = $this->catalogo();
        $actividadId = texto_post($entrada, 'actividad_id') ?? '';
        $actividad = $catalogo['actividades_por_id'][$actividadId] ?? null;
        if ($actividad === null || !Politica::veActividad($usuario, $actividad)) {
            return ['tipo' => 'error', 'texto' => 'La actividad no está disponible en su ámbito.'];
        }

        $tipo = texto_post($entrada, 'tipo') ?? '';
        if (!in_array($tipo, tipos_requerimiento(), true)) {
            return ['tipo' => 'error', 'texto' => 'El tipo de requerimiento no es válido.'];
        }
        $descripcion = texto_post($entrada, 'descripcion') ?? '';
        if (longitud($descripcion) < 8 || longitud($descripcion) > 500) {
            return ['tipo' => 'error', 'texto' => 'La descripción debe tener entre 8 y 500 caracteres.'];
        }

        $solicitante = $usuario['perfil'] === 'admin'
            ? (texto_post($entrada, 'instituto_solicitante') ?? '')
            : $usuario['instituto_id'];
        $destino = texto_post($entrada, 'instituto_destino') ?? '';
        if (!isset($catalogo['institutos'][$solicitante]) || !isset($catalogo['institutos'][$destino])) {
            return ['tipo' => 'error', 'texto' => 'El instituto solicitante o el destino no son válidos.'];
        }
        if ($solicitante === $destino) {
            return ['tipo' => 'error', 'texto' => 'El destino debe ser un instituto distinto del solicitante.'];
        }

        $tareaId = texto_post($entrada, 'tarea_id') ?? '';
        $tareaId = $tareaId === '' ? null : $tareaId;
        if ($tareaId !== null) {
            $tarea = $catalogo['tareas_por_id'][$tareaId] ?? null;
            if ($tarea === null || $tarea['actividad_id'] !== $actividadId) {
                return ['tipo' => 'error', 'texto' => 'La tarea elegida no pertenece a la actividad.'];
            }
            if ($usuario['perfil'] === 'responsable' && $tarea['instituto_id'] !== $usuario['instituto_id']) {
                return ['tipo' => 'error', 'texto' => 'Solo puede vincular tareas de su instituto.'];
            }
        }

        $_SESSION['seq_req'] = (int) ($_SESSION['seq_req'] ?? 0) + 1;
        $id = sprintf('REQ-S-%03d', $_SESSION['seq_req']);
        $_SESSION['requerimientos'][$id] = [
            'id' => $id,
            'actividad_id' => $actividadId,
            'tarea_id' => $tareaId,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'instituto_solicitante' => $solicitante,
            'instituto_destino' => $destino,
            'responsable_id' => $this->responsableDe($destino, $catalogo['usuarios']),
            'estado' => 'Pendiente',
            'creado' => hoy()->format('Y-m-d'),
            'observaciones' => '',
        ];
        $this->auditar($usuario, $solicitante, 'Requerimiento ' . $id, 'Alta de requerimiento', '—', $tipo);

        return ['tipo' => 'ok', 'texto' => 'Se registró el requerimiento ' . $id . ' en esta sesión.'];
    }

    public function actualizarRequerimiento(array $usuario, array $entrada): array
    {
        $id = texto_post($entrada, 'requerimiento_id') ?? '';
        $catalogo = $this->catalogo();
        $requerimiento = $catalogo['requerimientos_por_id'][$id] ?? null;
        if ($requerimiento === null) {
            return ['tipo' => 'error', 'texto' => 'El requerimiento no existe.'];
        }
        if (!Politica::editaRequerimiento($usuario, $requerimiento)) {
            return ['tipo' => 'error', 'texto' => 'No tiene permiso para actualizar este requerimiento.'];
        }

        $estado = texto_post($entrada, 'estado') ?? '';
        if (!in_array($estado, estados_requerimiento(), true)) {
            return ['tipo' => 'error', 'texto' => 'El estado de resolución no es válido.'];
        }
        $observaciones = texto_post($entrada, 'observaciones') ?? '';
        if (longitud($observaciones) > 500) {
            return ['tipo' => 'error', 'texto' => 'Las observaciones admiten hasta 500 caracteres.'];
        }

        $cambios = [];
        if ($requerimiento['estado'] !== $estado) {
            $cambios[] = ['Estado de resolución', $requerimiento['estado'], $estado];
        }
        if ((string) $requerimiento['observaciones'] !== $observaciones) {
            $cambios[] = ['Observaciones', $this->mostrar($requerimiento['observaciones']), $this->mostrar($observaciones)];
        }
        if ($cambios === []) {
            return ['tipo' => 'info', 'texto' => 'No hubo cambios que registrar en ' . $id . '.'];
        }

        $_SESSION['requerimientos_estado'][$id] = [
            'estado' => $estado,
            'observaciones' => $observaciones,
        ];
        foreach ($cambios as $cambio) {
            $this->auditar(
                $usuario,
                (string) $requerimiento['instituto_destino'],
                'Requerimiento ' . $id,
                $cambio[0],
                $cambio[1],
                $cambio[2]
            );
        }

        return ['tipo' => 'ok', 'texto' => 'El requerimiento ' . $id . ' se actualizó en esta sesión.'];
    }

    public function tomarFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        if (!is_array($flash) || !isset($flash['tipo'], $flash['texto'])) {
            return null;
        }
        if (!in_array($flash['tipo'], ['ok', 'error', 'info'], true)) {
            return null;
        }

        return ['tipo' => $flash['tipo'], 'texto' => (string) $flash['texto']];
    }

    private function cambiarPerfil(array $entrada): array
    {
        $id = texto_post($entrada, 'user_id') ?? '';
        $usuarios = $this->indice($this->repositorio->usuarios());
        if (!isset($usuarios[$id])) {
            return ['tipo' => 'error', 'texto' => 'El perfil elegido no existe.'];
        }
        $_SESSION['user_id'] = $id;
        $usuario = $usuarios[$id];

        return [
            'tipo' => 'ok',
            'texto' => 'Perfil de demostración: ' . etiqueta_perfil($usuario['perfil']) . ', ' . $usuario['nombre'] . '.',
        ];
    }

    private function restaurarSesion(): array
    {
        unset(
            $_SESSION['tareas'],
            $_SESSION['requerimientos'],
            $_SESSION['requerimientos_estado'],
            $_SESSION['auditoria'],
            $_SESSION['seq_req'],
            $_SESSION['seq_aud']
        );

        return ['tipo' => 'ok', 'texto' => 'Se descartaron los cambios de esta sesión. Los datos de ejemplo volvieron a su estado inicial.'];
    }

    private function catalogo(): array
    {
        $institutos = $this->indice($this->repositorio->institutos());
        $usuarios = $this->indice($this->repositorio->usuarios());
        $tareas = $this->fusionarTareas($this->repositorio->tareas());
        $requerimientos = $this->fusionarRequerimientos($this->repositorio->requerimientos());

        $tareasPorActividad = [];
        $tareasPorId = [];
        foreach ($tareas as $tarea) {
            $tareasPorActividad[$tarea['actividad_id']][] = $tarea;
            $tareasPorId[$tarea['id']] = $tarea;
        }

        $reqsPorActividad = [];
        $requerimientosPorId = [];
        foreach ($requerimientos as $requerimiento) {
            $reqsPorActividad[$requerimiento['actividad_id']][] = $requerimiento;
            $requerimientosPorId[$requerimiento['id']] = $requerimiento;
        }

        $actividades = [];
        foreach ($this->repositorio->actividades() as $actividad) {
            $grupo = $tareasPorActividad[$actividad['id']] ?? [];
            $actividad['estado'] = estado_desde_tareas($grupo);
            $actividad['avance'] = avance_desde_tareas($grupo);
            $actividad['semaforo'] = semaforo($actividad['fecha'], $actividad['estado']);
            $actividad['vencida'] = $actividad['semaforo']['clave'] === 'rojo';
            $pendientes = 0;
            foreach ($reqsPorActividad[$actividad['id']] ?? [] as $requerimiento) {
                if ($requerimiento['estado'] !== 'Resuelto') {
                    $pendientes++;
                }
            }
            $actividad['reqs_pendientes'] = $pendientes;
            $actividades[] = $actividad;
        }

        usort($actividades, static function (array $a, array $b): int {
            if ($a['vencida'] !== $b['vencida']) {
                return $a['vencida'] ? -1 : 1;
            }
            $fecha = strcmp($a['fecha'], $b['fecha']);
            if ($fecha !== 0) {
                return $fecha;
            }

            return strcmp($a['id'], $b['id']);
        });

        $actividadesPorId = [];
        foreach ($actividades as $actividad) {
            $actividadesPorId[$actividad['id']] = $actividad;
        }

        $auditoria = array_merge($this->repositorio->auditoria(), $this->auditoriaSesion());
        usort($auditoria, static function (array $a, array $b): int {
            $cuando = strcmp($b['cuando'], $a['cuando']);
            if ($cuando !== 0) {
                return $cuando;
            }

            return strcmp($b['id'], $a['id']);
        });

        return [
            'institutos' => $institutos,
            'usuarios' => $usuarios,
            'actividades' => $actividades,
            'actividades_por_id' => $actividadesPorId,
            'tareas' => $tareas,
            'tareas_por_id' => $tareasPorId,
            'requerimientos' => $requerimientos,
            'requerimientos_por_id' => $requerimientosPorId,
            'auditoria' => $auditoria,
        ];
    }

    private function fusionarTareas(array $tareas): array
    {
        $cambios = is_array($_SESSION['tareas'] ?? null) ? $_SESSION['tareas'] : [];
        foreach ($tareas as &$tarea) {
            $cambio = $cambios[$tarea['id']] ?? null;
            if (!is_array($cambio)) {
                continue;
            }
            if (isset($cambio['estado']) && in_array($cambio['estado'], estados_tarea(), true)) {
                $tarea['estado'] = $cambio['estado'];
            }
            if (isset($cambio['avance']) && is_int($cambio['avance'])) {
                $tarea['avance'] = max(0, min(100, $cambio['avance']));
            }
            if (isset($cambio['observaciones']) && is_string($cambio['observaciones'])) {
                $tarea['observaciones'] = $cambio['observaciones'];
            }
            if ($tarea['estado'] === 'Finalizada') {
                $tarea['avance'] = 100;
            }
        }
        unset($tarea);

        return $tareas;
    }

    private function fusionarRequerimientos(array $requerimientos): array
    {
        $extras = is_array($_SESSION['requerimientos'] ?? null) ? $_SESSION['requerimientos'] : [];
        foreach ($extras as $extra) {
            if (is_array($extra) && isset($extra['id'])) {
                $requerimientos[] = $extra;
            }
        }

        $cambios = is_array($_SESSION['requerimientos_estado'] ?? null) ? $_SESSION['requerimientos_estado'] : [];
        foreach ($requerimientos as &$requerimiento) {
            $cambio = $cambios[$requerimiento['id']] ?? null;
            if (!is_array($cambio)) {
                continue;
            }
            if (isset($cambio['estado']) && in_array($cambio['estado'], estados_requerimiento(), true)) {
                $requerimiento['estado'] = $cambio['estado'];
            }
            if (isset($cambio['observaciones']) && is_string($cambio['observaciones'])) {
                $requerimiento['observaciones'] = $cambio['observaciones'];
            }
        }
        unset($requerimiento);

        return $requerimientos;
    }

    private function auditoriaSesion(): array
    {
        $filas = is_array($_SESSION['auditoria'] ?? null) ? $_SESSION['auditoria'] : [];
        $validas = [];
        foreach ($filas as $fila) {
            if (is_array($fila) && isset($fila['id'], $fila['cuando'])) {
                $validas[] = $fila;
            }
        }

        return $validas;
    }

    private function auditar(
        array $usuario,
        string $ambitoId,
        string $entidad,
        string $campo,
        string $anterior,
        string $nuevo
    ): void {
        $_SESSION['seq_aud'] = (int) ($_SESSION['seq_aud'] ?? 0) + 1;
        $_SESSION['auditoria'][] = [
            'id' => sprintf('AUD-S-%03d', $_SESSION['seq_aud']),
            'usuario_id' => $usuario['id'],
            'instituto_id' => $usuario['instituto_id'],
            'ambito_id' => $ambitoId,
            'cuando' => (new DateTimeImmutable('now'))->format('Y-m-d H:i'),
            'entidad' => $entidad,
            'campo' => $campo,
            'anterior' => $anterior,
            'nuevo' => $nuevo,
        ];
    }

    private function responsableDe(string $institutoId, array $usuarios): string
    {
        foreach ($usuarios as $usuario) {
            if ($institutoId === 'DIGEDUC' && $usuario['perfil'] === 'admin') {
                return $usuario['id'];
            }
            if ($usuario['instituto_id'] === $institutoId && $usuario['perfil'] === 'responsable') {
                return $usuario['id'];
            }
        }

        return 'u-quelvo';
    }

    private function kpis(array $actividades): array
    {
        $kpis = [
            'total' => count($actividades),
            'proximas' => 0,
            'vencidas' => 0,
            'finalizadas' => 0,
            'con_requerimientos' => 0,
            'avance_promedio' => 0,
            'plazo' => ['rojo' => 0, 'amarillo' => 0, 'verde' => 0, 'cumplida' => 0],
        ];
        $avance = 0;
        foreach ($actividades as $actividad) {
            $clave = $actividad['semaforo']['clave'];
            $kpis['plazo'][$clave] = ($kpis['plazo'][$clave] ?? 0) + 1;
            if ($clave === 'rojo') {
                $kpis['vencidas']++;
            } elseif ($clave === 'amarillo') {
                $kpis['proximas']++;
            }
            if ($actividad['estado'] === 'Finalizada') {
                $kpis['finalizadas']++;
            }
            if ((int) $actividad['reqs_pendientes'] > 0) {
                $kpis['con_requerimientos']++;
            }
            $avance += (int) $actividad['avance'];
        }
        $kpis['avance_promedio'] = $actividades === [] ? 0 : (int) round($avance / count($actividades));

        return $kpis;
    }

    private function indice(array $filas): array
    {
        $indice = [];
        foreach ($filas as $fila) {
            $indice[$fila['id']] = $fila;
        }

        return $indice;
    }

    private function mostrar(mixed $valor): string
    {
        $texto = trim((string) $valor);

        return $texto === '' ? '—' : $texto;
    }

    private function flash(string $tipo, string $texto): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
    }
}
