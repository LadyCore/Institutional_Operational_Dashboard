<?php

declare(strict_types=1);

/**
 * Arma el tablero y aplica los cambios de la sesión.
 * Los cambios y el historial de esta etapa viven en la sesión PHP.
 * Sirven para demostrar el circuito y se pierden al cerrarla o al
 * restablecer la demostración. No son una auditoría permanente.
 * La persistencia futura prevista es MySQL, no MariaDB.
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
        if (!id_actividad_valido($id)) {
            return false;
        }

        return isset($this->catalogo()['actividades_por_id'][$id]);
    }

    public function sinDependenciasVigentes(string $id): bool
    {
        $catalogo = $this->catalogo();
        $actividad = $catalogo['actividades_por_id'][$id] ?? null;
        if (!is_array($actividad) || registro_baja($actividad)) {
            return false;
        }
        foreach ($actividad['institutos'] as $institutoId) {
            if (is_string($institutoId) && $this->dependenciaVigente($catalogo['institutos'], $institutoId)) {
                return false;
            }
        }

        return true;
    }

    public function contexto(array $usuario): array
    {
        $completo = $this->catalogo();
        $actividades = array_values(array_filter(
            $completo['actividades'],
            static fn (array $actividad): bool => Politica::veActividad($usuario, $actividad) && !registro_baja($actividad)
        ));
        $actividadesPorId = [];
        foreach ($completo['actividades'] as $actividadCompleta) {
            $visible = Politica::veActividad($usuario, $actividadCompleta) && !registro_baja($actividadCompleta);
            $antecedente = Politica::administraOperaciones($usuario) && registro_baja($actividadCompleta);
            if ($visible || $antecedente) {
                $actividadesPorId[$actividadCompleta['id']] = $actividadCompleta;
            }
        }
        $ids = array_keys($actividadesPorId);
        $tareas = array_values(array_filter(
            $completo['tareas'],
            fn (array $tarea): bool => in_array($tarea['actividad_id'], $ids, true)
                && $this->dependenciaVigente($completo['institutos'], (string) $tarea['instituto_id'])
        ));
        $requerimientos = array_values(array_filter(
            $completo['requerimientos'],
            fn (array $requerimiento): bool => in_array($requerimiento['actividad_id'], $ids, true)
                && $this->vinculoVigente($completo['institutos'], $requerimiento)
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
        $presentar = fn (array $actividad): array => $this->presentarActividad(
            $actividad,
            $tareasPorActividad[$actividad['id']] ?? [],
            $reqsPorActividad[$actividad['id']] ?? [],
            $completo['institutos']
        );
        foreach ($actividades as &$actividadVisible) {
            $actividadVisible = $presentar($actividadVisible);
        }
        unset($actividadVisible);
        foreach ($actividadesPorId as $idActividad => $actividadGuardada) {
            $actividadesPorId[$idActividad] = $presentar($actividadGuardada);
        }
        $actividades = array_values(array_filter(
            $actividades,
            static fn (array $actividad): bool => $actividad['institutos'] !== []
        ));
        foreach (array_keys($actividadesPorId) as $idActividad) {
            if ($actividadesPorId[$idActividad]['institutos'] === [] && !registro_baja($actividadesPorId[$idActividad])) {
                unset($actividadesPorId[$idActividad]);
            }
        }
        $tareas = array_values(array_filter(
            $tareas,
            static fn (array $tarea): bool => isset($actividadesPorId[$tarea['actividad_id']])
        ));
        $requerimientos = array_values(array_filter(
            $requerimientos,
            static fn (array $requerimiento): bool => isset($actividadesPorId[$requerimiento['actividad_id']])
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

        return [
            'usuario' => $usuario,
            'usuarios' => $completo['usuarios'],
            'directorio' => array_values($completo['usuarios']),
            'institutos' => $completo['institutos'],
            'institutos_dependientes' => array_values(array_filter(
                $completo['institutos'],
                static fn (array $instituto): bool => $instituto['tipo'] === 'instituto' && !registro_baja($instituto)
            )),
            'institutos_asignables' => array_values(array_filter(
                $completo['institutos'],
                static fn (array $instituto): bool => $instituto['tipo'] === 'instituto' && instituto_activo($instituto) && !registro_baja($instituto)
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
            'historial_demo' => true,
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
            'actividad_crear' => $this->crearActividad($usuario, $entrada),
            'actividad_editar' => $this->editarActividad($usuario, $entrada),
            'tarea_crear' => $this->crearTareas($usuario, $entrada),
            'tarea_asignacion' => $this->editarAsignacionTarea($usuario, $entrada),
            'tarea_reprogramar' => $this->reprogramarTarea($usuario, $entrada),
            'organismo_crear' => $this->crearOrganismo($usuario, $entrada),
            'organismo_editar' => $this->editarOrganismo($usuario, $entrada),
            'organismo_baja' => $this->darDeBajaOrganismo($usuario, $entrada),
            'actividad_baja' => $this->darDeBajaActividad($usuario, $entrada),
            'tarea_baja' => $this->darDeBajaTarea($usuario, $entrada),
            'requerimiento_crear' => $this->crearRequerimiento($usuario, $entrada),
            'requerimiento_estado' => $this->actualizarRequerimiento($usuario, $entrada),
            default => ['tipo' => 'error', 'texto' => 'La acción no está disponible.'],
        };
        $this->flash($resultado['tipo'], $resultado['texto']);

        if (($resultado['tipo'] ?? '') !== 'error' && isset($resultado['destino']) && is_string($resultado['destino'])) {
            return $resultado['destino'];
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
        if ($respuesta = $this->rechazarSiBaja($catalogo, $tarea)) {
            return $respuesta;
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
        $cumplimiento = $tarea['fecha_cumplimiento'];
        $dentroPlazo = $tarea['cumplida_dentro_plazo_original'];
        if ($tarea['estado'] !== 'Finalizada' && $estado === 'Finalizada') {
            $cumplimiento = hoy()->format('Y-m-d');
            $original = (string) $tarea['fecha_vencimiento_original'];
            $dentroPlazo = fecha_valida($original) ? $cumplimiento <= $original : null;
            $lectura = match ($dentroPlazo) {
                true => 'Dentro del plazo original',
                false => 'Después del plazo original',
                default => 'Sin plazo original válido',
            };
            $cambios[] = ['Cumplimentación', '—', $lectura];
        } elseif ($estado !== 'Finalizada' && $tarea['estado'] === 'Finalizada') {
            $cumplimiento = null;
            $dentroPlazo = null;
        }

        if ($cambios === []) {
            return ['tipo' => 'info', 'texto' => 'No hubo cambios que registrar en ' . $id . '.'];
        }

        $this->guardarSesionTarea($id, array_merge($this->sesionTarea($id), [
            'estado' => $estado,
            'avance' => $avance,
            'observaciones' => $observaciones,
            'fecha_cumplimiento' => $cumplimiento,
            'cumplida_dentro_plazo_original' => $dentroPlazo,
        ]));
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

    public function crearActividad(array $usuario, array $entrada): array
    {
        if (!Politica::administraOperaciones($usuario)) {
            return ['tipo' => 'error', 'texto' => 'Solo el administrador principal puede crear actividades.'];
        }

        $datos = $this->leerActividad($entrada);
        if (isset($datos['error'])) {
            return ['tipo' => 'error', 'texto' => $datos['error']];
        }

        $id = $this->siguienteIdentificador('seq_act', 'ACT-S');
        if (!isset($_SESSION['actividades_alta']) || !is_array($_SESSION['actividades_alta'])) {
            $_SESSION['actividades_alta'] = [];
        }
        $_SESSION['actividades_alta'][$id] = [
            'id' => $id,
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'],
            'tipo' => $datos['tipo'],
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha' => $datos['fecha'],
            'responsable_id' => $datos['responsable_id'],
            'institutos' => $datos['institutos'],
            'observaciones' => $datos['observaciones'],
        ];
        $this->auditar($usuario, 'DIGEDUC', 'Actividad ' . $id, 'Alta de actividad', '—', $datos['titulo']);

        return [
            'tipo' => 'ok',
            'texto' => 'Se creó la actividad «' . $datos['titulo'] . '».' . $this->avisoPlazo($datos['fecha_inicio'], $datos['fecha']),
            'destino' => url_de('actividad', ['id' => $id]),
        ];
    }

    public function editarActividad(array $usuario, array $entrada): array
    {
        if (!Politica::administraOperaciones($usuario)) {
            return ['tipo' => 'error', 'texto' => 'Solo el administrador principal puede modificar actividades.'];
        }

        $id = texto_post($entrada, 'actividad_id') ?? '';
        $catalogo = $this->catalogo();
        $actual = $catalogo['actividades_por_id'][$id] ?? null;
        if ($actual === null) {
            return ['tipo' => 'error', 'texto' => 'La actividad no existe.'];
        }
        if (registro_baja($actual)) {
            return ['tipo' => 'error', 'texto' => 'La actividad está dada de baja y no puede modificarse.'];
        }

        $datos = $this->leerActividad($entrada, $actual);
        if (isset($datos['error'])) {
            return ['tipo' => 'error', 'texto' => $datos['error']];
        }

        $patch = [];
        $cambios = [];
        $this->compararCampo($cambios, $patch, 'titulo', 'Nombre', $actual['titulo'], $datos['titulo']);
        $this->compararCampo($cambios, $patch, 'descripcion', 'Descripción', $actual['descripcion'], $datos['descripcion']);
        $this->compararCampo($cambios, $patch, 'tipo', 'Categoría', $actual['tipo'], $datos['tipo']);
        $this->compararCampo($cambios, $patch, 'fecha_inicio', 'Fecha de inicio', $actual['fecha_inicio'], $datos['fecha_inicio'], fecha_es($actual['fecha_inicio']), fecha_es($datos['fecha_inicio']));
        $this->compararCampo($cambios, $patch, 'fecha', 'Finalización prevista', $actual['fecha'], $datos['fecha'], fecha_es($actual['fecha']), fecha_es($datos['fecha']));
        $this->compararCampo(
            $cambios,
            $patch,
            'responsable_id',
            'Responsable',
            $actual['responsable_id'],
            $datos['responsable_id'],
            nombre_usuario($catalogo['usuarios'], $actual['responsable_id']),
            nombre_usuario($catalogo['usuarios'], $datos['responsable_id'])
        );
        $this->compararCampo(
            $cambios,
            $patch,
            'observaciones',
            'Observaciones',
            (string) $actual['observaciones'],
            $datos['observaciones'],
            $this->mostrar($actual['observaciones']),
            $this->mostrar($datos['observaciones'])
        );
        if (!$this->mismosIds($actual['institutos'], $datos['institutos'])) {
            $patch['institutos'] = $datos['institutos'];
            $cambios[] = ['Organismos participantes', implode(', ', $actual['institutos']), implode(', ', $datos['institutos'])];
        }
        if ($cambios === []) {
            return ['tipo' => 'info', 'texto' => 'No hubo cambios que registrar en la actividad.'];
        }

        if (!isset($_SESSION['actividades_cambio']) || !is_array($_SESSION['actividades_cambio'])) {
            $_SESSION['actividades_cambio'] = [];
        }
        $previo = is_array($_SESSION['actividades_cambio'][$id] ?? null) ? $_SESSION['actividades_cambio'][$id] : [];
        $_SESSION['actividades_cambio'][$id] = array_merge($previo, $patch);
        foreach ($cambios as $cambio) {
            $this->auditar($usuario, 'DIGEDUC', 'Actividad ' . $id, $cambio[0], $cambio[1], $cambio[2]);
        }

        return [
            'tipo' => 'ok',
            'texto' => 'La actividad «' . $datos['titulo'] . '» se actualizó.' . $this->avisoPlazo($datos['fecha_inicio'], $datos['fecha']),
            'destino' => url_de('actividad', ['id' => $id]),
        ];
    }

    public function crearTareas(array $usuario, array $entrada): array
    {
        if (!Politica::administraOperaciones($usuario)) {
            return ['tipo' => 'error', 'texto' => 'Solo el administrador principal puede crear tareas.'];
        }

        $catalogo = $this->catalogo();
        $actividadId = texto_post($entrada, 'actividad_id') ?? '';
        $actividad = $catalogo['actividades_por_id'][$actividadId] ?? null;
        if ($actividad === null) {
            return ['tipo' => 'error', 'texto' => 'La actividad no existe.'];
        }
        if (registro_baja($actividad)) {
            return ['tipo' => 'error', 'texto' => 'No se pueden asignar tareas a una actividad dada de baja.'];
        }

        $descripcion = texto_post($entrada, 'descripcion') ?? '';
        if (longitud($descripcion) < 3 || longitud($descripcion) > 500) {
            return ['tipo' => 'error', 'texto' => 'La descripción de la tarea debe tener entre 3 y 500 caracteres.'];
        }
        $inicio = texto_post($entrada, 'fecha_inicio') ?? '';
        $limite = texto_post($entrada, 'fecha_limite') ?? '';
        if (!fecha_valida($inicio) || !fecha_valida($limite)) {
            return ['tipo' => 'error', 'texto' => 'Las fechas de la tarea deben tener el formato AAAA-MM-DD.'];
        }

        $elegidos = $this->institutosElegidos($entrada, $catalogo['institutos']);
        if (isset($elegidos['error'])) {
            return ['tipo' => 'error', 'texto' => $elegidos['error']];
        }
        foreach ($elegidos['ids'] as $institutoId) {
            if (!in_array($institutoId, $actividad['institutos'], true)) {
                return ['tipo' => 'error', 'texto' => 'Solo se pueden asignar organismos que participan en la actividad.'];
            }
            if (!instituto_activo($catalogo['institutos'][$institutoId]) || registro_baja($catalogo['institutos'][$institutoId])) {
                return ['tipo' => 'error', 'texto' => 'No se pueden asignar tareas nuevas a un organismo desactivado o dado de baja.'];
            }
        }

        $usuarioId = texto_post($entrada, 'usuario_id') ?? '';
        $planes = [];
        if (count($elegidos['ids']) === 1) {
            $institutoId = $elegidos['ids'][0];
            $asignado = $usuarioId === '' ? $this->responsableDe($institutoId, $catalogo['usuarios']) : $usuarioId;
            $persona = $catalogo['usuarios'][$asignado] ?? null;
            if ($persona === null || $persona['instituto_id'] !== $institutoId) {
                return ['tipo' => 'error', 'texto' => 'El responsable debe pertenecer al organismo de la tarea.'];
            }
            $planes[] = [$institutoId, $asignado];
        } else {
            foreach ($elegidos['ids'] as $institutoId) {
                $planes[] = [$institutoId, $this->responsableDe($institutoId, $catalogo['usuarios'])];
            }
        }

        if (!isset($_SESSION['tareas_alta']) || !is_array($_SESSION['tareas_alta'])) {
            $_SESSION['tareas_alta'] = [];
        }
        foreach ($planes as [$institutoId, $asignado]) {
            $id = $this->siguienteIdentificador('seq_tar', 'TAR-S');
            $_SESSION['tareas_alta'][$id] = [
                'id' => $id,
                'actividad_id' => $actividadId,
                'instituto_id' => $institutoId,
                'usuario_id' => $asignado,
                'descripcion' => $descripcion,
                'fecha_inicio' => $inicio,
                'fecha_limite' => $limite,
                'fecha_vencimiento_original' => $limite,
                'estado' => 'Pendiente',
                'avance' => 0,
                'observaciones' => '',
                'reprogramaciones' => [],
                'fecha_cumplimiento' => null,
                'cumplida_dentro_plazo_original' => null,
            ];
            $this->auditar($usuario, $institutoId, 'Tarea ' . $id, 'Alta de tarea', '—', $descripcion);
        }

        $texto = count($planes) === 1
            ? 'Se creó la tarea asignada.'
            : 'Se crearon ' . count($planes) . ' tareas, una por organismo, con estado y vencimiento propios.';

        return [
            'tipo' => 'ok',
            'texto' => $texto . $this->avisoPlazo($inicio, $limite),
            'destino' => url_de('actividad', ['id' => $actividadId]),
        ];
    }

    public function editarAsignacionTarea(array $usuario, array $entrada): array
    {
        if (!Politica::administraOperaciones($usuario)) {
            return ['tipo' => 'error', 'texto' => 'Solo el administrador principal puede reasignar tareas.'];
        }

        $id = texto_post($entrada, 'tarea_id') ?? '';
        $catalogo = $this->catalogo();
        $tarea = $catalogo['tareas_por_id'][$id] ?? null;
        if ($tarea === null) {
            return ['tipo' => 'error', 'texto' => 'La tarea no existe.'];
        }
        $actividad = $catalogo['actividades_por_id'][$tarea['actividad_id']] ?? null;
        if ($actividad === null) {
            return ['tipo' => 'error', 'texto' => 'La actividad de la tarea no existe.'];
        }
        if ($respuesta = $this->rechazarSiBaja($catalogo, $tarea)) {
            return $respuesta;
        }

        $descripcion = texto_post($entrada, 'descripcion') ?? '';
        if (longitud($descripcion) < 3 || longitud($descripcion) > 500) {
            return ['tipo' => 'error', 'texto' => 'La descripción de la tarea debe tener entre 3 y 500 caracteres.'];
        }
        $inicio = texto_post($entrada, 'fecha_inicio') ?? '';
        if (!fecha_valida($inicio)) {
            return ['tipo' => 'error', 'texto' => 'La fecha de inicio debe tener el formato AAAA-MM-DD.'];
        }
        $institutoId = texto_post($entrada, 'instituto_id') ?? '';
        if (!isset($catalogo['institutos'][$institutoId]) || $catalogo['institutos'][$institutoId]['tipo'] !== 'instituto') {
            return ['tipo' => 'error', 'texto' => 'El organismo elegido no es válido.'];
        }
        if (!in_array($institutoId, $actividad['institutos'], true)) {
            return ['tipo' => 'error', 'texto' => 'El organismo debe participar en la actividad. Agreguelo a la actividad antes de reasignar la tarea.'];
        }
        if ((!instituto_activo($catalogo['institutos'][$institutoId]) || registro_baja($catalogo['institutos'][$institutoId])) && $institutoId !== $tarea['instituto_id']) {
            return ['tipo' => 'error', 'texto' => 'No se puede reasignar una tarea a un organismo desactivado o dado de baja.'];
        }
        $asignado = texto_post($entrada, 'usuario_id') ?? '';
        $persona = $catalogo['usuarios'][$asignado] ?? null;
        if ($persona === null || $persona['instituto_id'] !== $institutoId) {
            return ['tipo' => 'error', 'texto' => 'El responsable debe pertenecer al organismo de la tarea.'];
        }

        $cambios = [];
        $patch = [];
        $this->compararCampo($cambios, $patch, 'descripcion', 'Descripción', $tarea['descripcion'], $descripcion);
        $this->compararCampo($cambios, $patch, 'fecha_inicio', 'Fecha de inicio', $tarea['fecha_inicio'], $inicio, fecha_es($tarea['fecha_inicio']), fecha_es($inicio));
        $this->compararCampo(
            $cambios,
            $patch,
            'instituto_id',
            'Organismo',
            $tarea['instituto_id'],
            $institutoId,
            sigla_instituto($catalogo['institutos'], $tarea['instituto_id']),
            sigla_instituto($catalogo['institutos'], $institutoId)
        );
        $this->compararCampo(
            $cambios,
            $patch,
            'usuario_id',
            'Responsable',
            $tarea['usuario_id'],
            $asignado,
            nombre_usuario($catalogo['usuarios'], $tarea['usuario_id']),
            nombre_usuario($catalogo['usuarios'], $asignado)
        );
        if ($cambios === []) {
            return ['tipo' => 'info', 'texto' => 'No hubo cambios que registrar en ' . $id . '.'];
        }

        $this->guardarSesionTarea($id, array_merge($this->sesionTarea($id), $patch));
        foreach ($cambios as $cambio) {
            $this->auditar($usuario, $institutoId, 'Tarea ' . $id, $cambio[0], $cambio[1], $cambio[2]);
        }

        return [
            'tipo' => 'ok',
            'texto' => 'La asignación de la tarea se actualizó. El vencimiento vigente no cambió.',
            'destino' => url_de('actividad', ['id' => $tarea['actividad_id']]),
        ];
    }

    public function reprogramarTarea(array $usuario, array $entrada): array
    {
        if (!Politica::administraOperaciones($usuario)) {
            return ['tipo' => 'error', 'texto' => 'Solo el administrador principal puede reprogramar vencimientos.'];
        }

        $id = texto_post($entrada, 'tarea_id') ?? '';
        $catalogo = $this->catalogo();
        $tarea = $catalogo['tareas_por_id'][$id] ?? null;
        if ($tarea === null) {
            return ['tipo' => 'error', 'texto' => 'La tarea no existe.'];
        }
        if ($respuesta = $this->rechazarSiBaja($catalogo, $tarea)) {
            return $respuesta;
        }

        $nueva = texto_post($entrada, 'fecha_limite') ?? '';
        if (!fecha_valida($nueva)) {
            return ['tipo' => 'error', 'texto' => 'La nueva fecha de vencimiento debe tener el formato AAAA-MM-DD.'];
        }
        if ($nueva === $tarea['fecha_limite']) {
            return ['tipo' => 'error', 'texto' => 'La nueva fecha debe ser distinta de la fecha actualmente exigible.'];
        }
        $motivo = texto_post($entrada, 'motivo') ?? '';
        if (longitud($motivo) < 8 || longitud($motivo) > 500) {
            return ['tipo' => 'error', 'texto' => 'El motivo de la reprogramación debe tener entre 8 y 500 caracteres.'];
        }

        $registro = [
            'fecha_anterior' => $tarea['fecha_limite'],
            'fecha_nueva' => $nueva,
            'motivo' => $motivo,
            'usuario_id' => $usuario['id'],
            'cuando' => (new DateTimeImmutable('now'))->format('Y-m-d H:i'),
        ];
        $previo = $this->sesionTarea($id);
        $lista = is_array($previo['reprogramaciones'] ?? null) ? $previo['reprogramaciones'] : [];
        $lista[] = $registro;
        $previo['reprogramaciones'] = $lista;
        $previo['fecha_limite'] = $nueva;
        $this->guardarSesionTarea($id, $previo);
        $nombre = etiqueta_ordinal_vencimiento(count($lista) + 1);
        $this->auditar($usuario, $tarea['instituto_id'], 'Tarea ' . $id, $nombre, fecha_es($tarea['fecha_limite']), fecha_es($nueva));
        $this->auditar($usuario, $tarea['instituto_id'], 'Tarea ' . $id, 'Motivo de reprogramación', '—', $motivo);

        return [
            'tipo' => 'ok',
            'texto' => 'Se registró el plazo como ' . $nombre . '. La tarea no quedó cumplimentada y la finalización prevista de la actividad no cambió.',
            'destino' => url_de('actividad', ['id' => $tarea['actividad_id']]),
        ];
    }

    public function crearOrganismo(array $usuario, array $entrada): array
    {
        if (!Politica::administraOperaciones($usuario)) {
            return ['tipo' => 'error', 'texto' => 'Solo el administrador principal puede crear organismos.'];
        }

        $datos = $this->leerOrganismo($entrada, true, '', '');
        if (isset($datos['error'])) {
            return ['tipo' => 'error', 'texto' => $datos['error']];
        }
        $catalogo = $this->catalogo();
        if ($this->siglaOcupada($datos['sigla'], $catalogo['institutos'])) {
            return ['tipo' => 'error', 'texto' => 'Ya existe un organismo con esa sigla.'];
        }

        $id = $this->siguienteIdentificador('seq_org', 'ORG-S');
        if (!isset($_SESSION['organismos_alta']) || !is_array($_SESSION['organismos_alta'])) {
            $_SESSION['organismos_alta'] = [];
        }
        $_SESSION['organismos_alta'][$id] = [
            'id' => $id,
            'sigla' => $datos['sigla'],
            'nombre' => $datos['nombre'],
            'tipo' => 'instituto',
            'sede' => $datos['sede'],
            'descripcion' => $datos['descripcion'],
            'activo' => true,
            'baja' => false,
        ];
        $this->asociarResponsable($id, $datos['responsable'], $this->catalogo()['usuarios']);
        $this->auditar($usuario, 'DIGEDUC', 'Organismo ' . $id, 'Alta de organismo', '—', $datos['sigla'] . ' · ' . $datos['nombre'], [
            'operacion' => 'alta',
            'tipo_registro' => 'organismo',
            'registro_id' => $id,
            'registro_nombre' => $datos['nombre'],
        ]);

        return [
            'tipo' => 'ok',
            'texto' => 'Se incorporó ' . $datos['sigla'] . ' («' . $datos['nombre'] . '»). Ya puede participar en actividades y tareas.',
            'destino' => url_de('institutos'),
        ];
    }

    public function editarOrganismo(array $usuario, array $entrada): array
    {
        if (!Politica::administraOperaciones($usuario)) {
            return ['tipo' => 'error', 'texto' => 'Solo el administrador principal puede modificar organismos.'];
        }

        $id = strtoupper(texto_post($entrada, 'organismo_id') ?? '');
        $catalogo = $this->catalogo();
        $actual = $catalogo['institutos'][$id] ?? null;
        if ($actual === null || ($actual['tipo'] ?? '') !== 'instituto') {
            return ['tipo' => 'error', 'texto' => 'El organismo no existe.'];
        }
        if (registro_baja($actual)) {
            return ['tipo' => 'error', 'texto' => 'El organismo está dado de baja y no puede modificarse.'];
        }
        $datos = $this->leerOrganismo($entrada, false, (string) $actual['sigla'], (string) ($actual['sede'] ?? ''));
        if (isset($datos['error'])) {
            return ['tipo' => 'error', 'texto' => $datos['error']];
        }
        if ($this->siglaOcupada($datos['sigla'], $catalogo['institutos'], $id)) {
            return ['tipo' => 'error', 'texto' => 'Ya existe otro organismo con esa sigla.'];
        }

        $activo = ($entrada['activo'] ?? '') === '1';
        $patch = [];
        $cambios = [];
        $this->compararCampo($cambios, $patch, 'sigla', 'Sigla', (string) $actual['sigla'], $datos['sigla']);
        $this->compararCampo($cambios, $patch, 'nombre', 'Nombre', (string) $actual['nombre'], $datos['nombre']);
        $this->compararCampo($cambios, $patch, 'sede', 'Sede', (string) $actual['sede'], $datos['sede']);
        $this->compararCampo($cambios, $patch, 'descripcion', 'Descripción', (string) $actual['descripcion'], $datos['descripcion']);
        if (instituto_activo($actual) !== $activo) {
            $patch['activo'] = $activo;
            $cambios[] = ['Situación', $activo ? 'Desactivado' : 'Activo', $activo ? 'Activo' : 'Desactivado'];
        }
        $responsableActual = $this->nombreResponsable($id, $catalogo['usuarios']);
        $cambioResponsable = $responsableActual !== $datos['responsable'];
        if ($cambios === [] && !$cambioResponsable) {
            return ['tipo' => 'info', 'texto' => 'No hubo cambios que registrar en el organismo.'];
        }
        if ($cambios !== []) {
            if (!isset($_SESSION['organismos_cambio']) || !is_array($_SESSION['organismos_cambio'])) {
                $_SESSION['organismos_cambio'] = [];
            }
            $previo = is_array($_SESSION['organismos_cambio'][$id] ?? null) ? $_SESSION['organismos_cambio'][$id] : [];
            $_SESSION['organismos_cambio'][$id] = array_merge($previo, $patch);
            foreach ($cambios as $cambio) {
                $this->auditar($usuario, $id, 'Organismo ' . $id, $cambio[0], $cambio[1], $cambio[2]);
            }
        }
        if (instituto_activo($actual) && !$activo) {
            $this->retirarInstitutoDeActividades($id);
        }
        if ($cambioResponsable) {
            $this->asociarResponsable($id, $datos['responsable'], $catalogo['usuarios']);
            $this->auditar($usuario, $id, 'Organismo ' . $id, 'Responsable', $this->mostrar($responsableActual), $datos['responsable'], [
                'operacion' => 'reasignacion',
                'tipo_registro' => 'organismo',
                'registro_id' => $id,
                'registro_nombre' => $datos['nombre'],
            ]);
        }

        return [
            'tipo' => 'ok',
            'texto' => $activo
                ? 'El organismo quedó actualizado y disponible para nuevas asignaciones.'
                : 'El organismo quedó desactivado y ya no figura dentro de las actividades. Sus tareas quedan como antecedente.',
            'destino' => url_de('institutos'),
        ];
    }

    public function darDeBajaOrganismo(array $usuario, array $entrada): array
    {
        if (!Politica::administraOperaciones($usuario)) {
            return ['tipo' => 'error', 'texto' => 'Solo el administrador principal puede dar de baja organismos.'];
        }
        $id = strtoupper(texto_post($entrada, 'organismo_id') ?? '');
        $catalogo = $this->catalogo();
        $actual = $catalogo['institutos'][$id] ?? null;
        if ($actual === null || ($actual['tipo'] ?? '') !== 'instituto') {
            return ['tipo' => 'error', 'texto' => 'El organismo no existe.'];
        }
        if (registro_baja($actual)) {
            return ['tipo' => 'error', 'texto' => 'El organismo ya está dado de baja.'];
        }
        $motivo = $this->leerMotivo($entrada);
        if (isset($motivo['error'])) {
            return ['tipo' => 'error', 'texto' => $motivo['error']];
        }

        $this->guardarCambioOrganismo($id, ['baja' => true, 'activo' => false]);
        $this->retirarInstitutoDeActividades($id);
        $this->auditar($usuario, $id, 'Organismo ' . $id, 'Baja', 'Activo en la operación', 'Dado de baja', [
            'operacion' => 'baja',
            'tipo_registro' => 'organismo',
            'registro_id' => $id,
            'registro_nombre' => (string) $actual['nombre'],
            'motivo' => $motivo['motivo'],
        ]);

        return [
            'tipo' => 'ok',
            'texto' => 'El organismo ' . $actual['sigla'] . ' quedó dado de baja y ya no figura entre las dependencias de las actividades. Sus antecedentes siguen en el historial de esta sesión.',
            'destino' => url_de('historial'),
        ];
    }

    private function retirarInstitutoDeActividades(string $institutoId): void
    {
        if (!isset($_SESSION['actividades_cambio']) || !is_array($_SESSION['actividades_cambio'])) {
            $_SESSION['actividades_cambio'] = [];
        }
        foreach ($this->catalogo()['actividades_por_id'] as $actividad) {
            $ids = array_values(array_filter(
                $actividad['institutos'],
                static fn (string $id): bool => $id !== $institutoId
            ));
            if (count($ids) === count($actividad['institutos'])) {
                continue;
            }
            $previo = is_array($_SESSION['actividades_cambio'][$actividad['id']] ?? null)
                ? $_SESSION['actividades_cambio'][$actividad['id']]
                : [];
            $previo['institutos'] = $ids;
            $_SESSION['actividades_cambio'][$actividad['id']] = $previo;
        }
    }

    public function darDeBajaActividad(array $usuario, array $entrada): array
    {
        if (!Politica::administraOperaciones($usuario)) {
            return ['tipo' => 'error', 'texto' => 'Solo el administrador principal puede dar de baja actividades.'];
        }
        $id = texto_post($entrada, 'actividad_id') ?? '';
        $catalogo = $this->catalogo();
        $actual = $catalogo['actividades_por_id'][$id] ?? null;
        if ($actual === null) {
            return ['tipo' => 'error', 'texto' => 'La actividad no existe.'];
        }
        if (registro_baja($actual)) {
            return ['tipo' => 'error', 'texto' => 'La actividad ya está dada de baja.'];
        }
        $motivo = $this->leerMotivo($entrada);
        if (isset($motivo['error'])) {
            return ['tipo' => 'error', 'texto' => $motivo['error']];
        }

        if (!isset($_SESSION['actividades_cambio']) || !is_array($_SESSION['actividades_cambio'])) {
            $_SESSION['actividades_cambio'] = [];
        }
        $previo = is_array($_SESSION['actividades_cambio'][$id] ?? null) ? $_SESSION['actividades_cambio'][$id] : [];
        $previo['baja'] = true;
        $_SESSION['actividades_cambio'][$id] = $previo;
        $tareas = [];
        foreach ($catalogo['tareas'] as $tarea) {
            if ($tarea['actividad_id'] === $id) {
                $tareas[] = $tarea['id'];
            }
        }
        $this->auditar($usuario, 'DIGEDUC', 'Actividad ' . $id, 'Baja', 'En operación', 'Dada de baja', [
            'operacion' => 'baja',
            'tipo_registro' => 'actividad',
            'registro_id' => $id,
            'registro_nombre' => (string) $actual['titulo'],
            'motivo' => $motivo['motivo'],
            'nuevo' => $tareas === [] ? 'Dada de baja. Sin tareas vinculadas.' : 'Dada de baja. Tareas conservadas: ' . implode(', ', $tareas),
        ]);

        return [
            'tipo' => 'ok',
            'texto' => 'La actividad quedó fuera de las vistas operativas. Sus tareas y antecedentes se conservan en esta sesión.',
            'destino' => url_de('historial'),
        ];
    }

    public function darDeBajaTarea(array $usuario, array $entrada): array
    {
        if (!Politica::administraOperaciones($usuario)) {
            return ['tipo' => 'error', 'texto' => 'Solo el administrador principal puede dar de baja tareas.'];
        }
        $id = texto_post($entrada, 'tarea_id') ?? '';
        $catalogo = $this->catalogo();
        $tarea = $catalogo['tareas_por_id'][$id] ?? null;
        if ($tarea === null) {
            return ['tipo' => 'error', 'texto' => 'La tarea no existe.'];
        }
        if (registro_baja($tarea)) {
            return ['tipo' => 'error', 'texto' => 'La tarea ya está dada de baja.'];
        }
        $motivo = $this->leerMotivo($entrada);
        if (isset($motivo['error'])) {
            return ['tipo' => 'error', 'texto' => $motivo['error']];
        }

        $this->guardarSesionTarea($id, array_merge($this->sesionTarea($id), ['baja' => true]));
        $this->auditar($usuario, (string) $tarea['instituto_id'], 'Tarea ' . $id, 'Baja', 'En operación', 'Dada de baja', [
            'operacion' => 'baja',
            'tipo_registro' => 'tarea',
            'registro_id' => $id,
            'registro_nombre' => (string) $tarea['descripcion'],
            'motivo' => $motivo['motivo'],
        ]);

        return [
            'tipo' => 'ok',
            'texto' => 'La tarea quedó fuera del seguimiento activo. Su vencimiento y sus antecedentes se conservan en esta sesión.',
            'destino' => url_de('actividad', ['id' => $tarea['actividad_id']]),
        ];
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
            $_SESSION['tareas_alta'],
            $_SESSION['actividades_alta'],
            $_SESSION['actividades_cambio'],
            $_SESSION['organismos_alta'],
            $_SESSION['organismos_cambio'],
            $_SESSION['usuarios_alta'],
            $_SESSION['usuarios_cambio'],
            $_SESSION['requerimientos'],
            $_SESSION['requerimientos_estado'],
            $_SESSION['auditoria'],
            $_SESSION['seq_req'],
            $_SESSION['seq_act'],
            $_SESSION['seq_tar'],
            $_SESSION['seq_aud'],
            $_SESSION['seq_org']
        );

        return ['tipo' => 'ok', 'texto' => 'Se descartaron los cambios de esta sesión. Los datos de ejemplo volvieron a su estado inicial.'];
    }

    private function dependenciaVigente(array $institutos, string $id): bool
    {
        $instituto = $institutos[$id] ?? null;

        return is_array($instituto)
            && ($instituto['tipo'] ?? '') === 'instituto'
            && instituto_activo($instituto)
            && !registro_baja($instituto);
    }

    private function vinculoVigente(array $institutos, array $requerimiento): bool
    {
        foreach (['instituto_solicitante', 'instituto_destino'] as $clave) {
            $instituto = $institutos[(string) ($requerimiento[$clave] ?? '')] ?? null;
            if (!is_array($instituto)) {
                return false;
            }
            if (($instituto['tipo'] ?? '') === 'instituto' && (!instituto_activo($instituto) || registro_baja($instituto))) {
                return false;
            }
        }

        return true;
    }

    private function presentarActividad(array $actividad, array $tareas, array $requerimientos, array $institutos): array
    {
        $actividad['institutos'] = array_values(array_filter(
            $actividad['institutos'],
            fn (mixed $id): bool => is_string($id) && $this->dependenciaVigente($institutos, $id)
        ));
        $vigentes = array_values(array_filter(
            $tareas,
            static fn (array $tarea): bool => !registro_baja($tarea)
        ));
        $actividad['estado'] = estado_desde_tareas($vigentes);
        $actividad['avance'] = avance_desde_tareas($vigentes);
        $actividad['semaforo'] = semaforo($actividad['fecha'], $actividad['estado']);
        $actividad['vencida'] = $actividad['semaforo']['clave'] === 'rojo';
        $pendientes = 0;
        foreach ($requerimientos as $requerimiento) {
            if ($requerimiento['estado'] !== 'Resuelto') {
                $pendientes++;
            }
        }
        $actividad['reqs_pendientes'] = $pendientes;

        return $actividad;
    }

    private function catalogo(): array
    {
        $institutos = $this->indice($this->fusionarInstitutos($this->repositorio->institutos()));
        $usuarios = $this->indice($this->fusionarUsuarios($this->repositorio->usuarios()));
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
        foreach ($this->fusionarActividades($this->repositorio->actividades()) as $actividad) {
            $grupo = $tareasPorActividad[$actividad['id']] ?? [];
            $operativas = array_values(array_filter(
                $grupo,
                static fn (array $tareaOperativa): bool => !registro_baja($tareaOperativa)
            ));
            $actividad['estado'] = estado_desde_tareas($operativas);
            $actividad['avance'] = avance_desde_tareas($operativas);
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

        $auditoria = array_map(
            static fn (array $fila): array => normalizar_historial($fila),
            array_merge($this->repositorio->auditoria(), $this->auditoriaSesion())
        );
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

    private function fusionarInstitutos(array $institutos): array
    {
        $cambios = is_array($_SESSION['organismos_cambio'] ?? null) ? $_SESSION['organismos_cambio'] : [];
        foreach ($institutos as &$instituto) {
            $instituto = $this->aplicarCambioInstituto($instituto, $cambios[$instituto['id']] ?? null);
        }
        unset($instituto);

        $altas = is_array($_SESSION['organismos_alta'] ?? null) ? $_SESSION['organismos_alta'] : [];
        foreach ($altas as $alta) {
            if (!is_array($alta) || !isset($alta['id']) || !is_string($alta['id'])) {
                continue;
            }
            $institutos[] = $this->aplicarCambioInstituto($alta, $cambios[$alta['id']] ?? null);
        }

        return $institutos;
    }

    private function aplicarCambioInstituto(array $instituto, mixed $cambio): array
    {
        $instituto = normalizar_instituto($instituto);
        if (!is_array($cambio)) {
            return $instituto;
        }
        foreach (['sigla', 'nombre', 'sede', 'descripcion'] as $clave) {
            if (isset($cambio[$clave]) && is_string($cambio[$clave])) {
                $instituto[$clave] = $cambio[$clave];
            }
        }
        if (array_key_exists('activo', $cambio)) {
            $instituto['activo'] = $cambio['activo'] === true;
        }
        if (array_key_exists('baja', $cambio)) {
            $instituto['baja'] = $cambio['baja'] === true;
        }

        return $instituto;
    }

    private function fusionarUsuarios(array $usuarios): array
    {
        $cambios = is_array($_SESSION['usuarios_cambio'] ?? null) ? $_SESSION['usuarios_cambio'] : [];
        foreach ($usuarios as &$usuario) {
            $cambio = $cambios[$usuario['id']] ?? null;
            if (is_array($cambio) && isset($cambio['nombre']) && is_string($cambio['nombre'])) {
                $usuario['nombre'] = $cambio['nombre'];
            }
        }
        unset($usuario);

        $altas = is_array($_SESSION['usuarios_alta'] ?? null) ? $_SESSION['usuarios_alta'] : [];
        foreach ($altas as $alta) {
            if (!is_array($alta) || !isset($alta['id']) || !is_string($alta['id'])) {
                continue;
            }
            $cambio = $cambios[$alta['id']] ?? null;
            if (is_array($cambio) && isset($cambio['nombre']) && is_string($cambio['nombre'])) {
                $alta['nombre'] = $cambio['nombre'];
            }
            $usuarios[] = $alta;
        }

        return $usuarios;
    }

    private function fusionarActividades(array $actividades): array
    {
        $cambios = is_array($_SESSION['actividades_cambio'] ?? null) ? $_SESSION['actividades_cambio'] : [];
        foreach ($actividades as &$actividad) {
            $actividad = $this->aplicarCambioActividad($actividad, $cambios[$actividad['id']] ?? null);
        }
        unset($actividad);

        $altas = is_array($_SESSION['actividades_alta'] ?? null) ? $_SESSION['actividades_alta'] : [];
        foreach ($altas as $alta) {
            if (!is_array($alta) || !isset($alta['id']) || !is_string($alta['id'])) {
                continue;
            }
            $actividades[] = $this->aplicarCambioActividad($alta, $cambios[$alta['id']] ?? null);
        }

        return $actividades;
    }

    private function aplicarCambioActividad(array $actividad, mixed $cambio): array
    {
        $actividad = normalizar_actividad($actividad);
        if (!is_array($cambio)) {
            return $actividad;
        }
        foreach (['titulo', 'descripcion', 'tipo', 'fecha_inicio', 'fecha', 'responsable_id', 'observaciones'] as $clave) {
            if (isset($cambio[$clave]) && is_string($cambio[$clave])) {
                $actividad[$clave] = $cambio[$clave];
            }
        }
        if (array_key_exists('baja', $cambio)) {
            $actividad['baja'] = $cambio['baja'] === true;
        }
        if (isset($cambio['institutos']) && is_array($cambio['institutos'])) {
            $ids = [];
            foreach ($cambio['institutos'] as $idInstituto) {
                if (is_string($idInstituto) && $idInstituto !== '') {
                    $ids[] = $idInstituto;
                }
            }
            $actividad['institutos'] = $ids;
        }

        return $actividad;
    }

    private function fusionarTareas(array $tareas): array
    {
        $altas = is_array($_SESSION['tareas_alta'] ?? null) ? $_SESSION['tareas_alta'] : [];
        foreach ($altas as $alta) {
            if (is_array($alta) && isset($alta['id']) && is_string($alta['id'])) {
                $tareas[] = $alta;
            }
        }

        $cambios = is_array($_SESSION['tareas'] ?? null) ? $_SESSION['tareas'] : [];
        foreach ($tareas as &$tarea) {
            $tarea = normalizar_tarea($tarea);
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
            if (isset($cambio['descripcion']) && is_string($cambio['descripcion'])) {
                $tarea['descripcion'] = $cambio['descripcion'];
            }
            if (array_key_exists('baja', $cambio)) {
                $tarea['baja'] = $cambio['baja'] === true;
            }
            if (isset($cambio['instituto_id']) && is_string($cambio['instituto_id'])) {
                $tarea['instituto_id'] = $cambio['instituto_id'];
            }
            if (isset($cambio['usuario_id']) && is_string($cambio['usuario_id'])) {
                $tarea['usuario_id'] = $cambio['usuario_id'];
            }
            if (isset($cambio['fecha_inicio']) && is_string($cambio['fecha_inicio']) && fecha_valida($cambio['fecha_inicio'])) {
                $tarea['fecha_inicio'] = $cambio['fecha_inicio'];
            }
            if (isset($cambio['fecha_limite']) && is_string($cambio['fecha_limite']) && fecha_valida($cambio['fecha_limite'])) {
                $tarea['fecha_limite'] = $cambio['fecha_limite'];
            }
            if (isset($cambio['reprogramaciones']) && is_array($cambio['reprogramaciones'])) {
                foreach ($cambio['reprogramaciones'] as $entrada) {
                    if (!is_array($entrada) || !isset($entrada['fecha_anterior'], $entrada['fecha_nueva'], $entrada['motivo'], $entrada['usuario_id'], $entrada['cuando'])) {
                        continue;
                    }
                    $tarea['reprogramaciones'][] = $entrada;
                }
            }
            if (array_key_exists('fecha_cumplimiento', $cambio)) {
                $tarea['fecha_cumplimiento'] = is_string($cambio['fecha_cumplimiento']) && fecha_valida($cambio['fecha_cumplimiento'])
                    ? $cambio['fecha_cumplimiento']
                    : null;
            }
            if (array_key_exists('cumplida_dentro_plazo_original', $cambio)) {
                $tarea['cumplida_dentro_plazo_original'] = is_bool($cambio['cumplida_dentro_plazo_original'])
                    ? $cambio['cumplida_dentro_plazo_original']
                    : null;
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

    /**
     * Anota la operación en el historial de la sesión.
     * No existe una operación de la aplicación para corregir o borrar estas filas.
     * Al restablecer la demostración se descarta la sesión completa, incluido este registro.
     *
     * @param array{operacion?: string, tipo_registro?: string, registro_id?: string, registro_nombre?: string, motivo?: string, nuevo?: string} $meta
     */
    private function auditar(
        array $usuario,
        string $ambitoId,
        string $entidad,
        string $campo,
        string $anterior,
        string $nuevo,
        array $meta = []
    ): void {
        if (isset($meta['nuevo']) && is_string($meta['nuevo'])) {
            $nuevo = $meta['nuevo'];
        }
        $_SESSION['seq_aud'] = (int) ($_SESSION['seq_aud'] ?? 0) + 1;
        $_SESSION['auditoria'][] = normalizar_historial([
            'id' => sprintf('AUD-S-%03d', $_SESSION['seq_aud']),
            'usuario_id' => $usuario['id'],
            'instituto_id' => $usuario['instituto_id'],
            'ambito_id' => $ambitoId,
            'cuando' => (new DateTimeImmutable('now'))->format('Y-m-d H:i'),
            'entidad' => $entidad,
            'campo' => $campo,
            'anterior' => $anterior,
            'nuevo' => $nuevo,
            'operacion' => $meta['operacion'] ?? '',
            'tipo_registro' => $meta['tipo_registro'] ?? '',
            'registro_id' => $meta['registro_id'] ?? '',
            'registro_nombre' => $meta['registro_nombre'] ?? '',
            'motivo' => $meta['motivo'] ?? '',
        ]);
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

    private function sesionTarea(string $id): array
    {
        $todas = $_SESSION['tareas'] ?? null;
        if (!is_array($todas) || !isset($todas[$id]) || !is_array($todas[$id])) {
            return [];
        }

        return $todas[$id];
    }

    private function guardarSesionTarea(string $id, array $datos): void
    {
        if (!isset($_SESSION['tareas']) || !is_array($_SESSION['tareas'])) {
            $_SESSION['tareas'] = [];
        }
        $_SESSION['tareas'][$id] = $datos;
    }

    private function siguienteIdentificador(string $secuencia, string $prefijo): string
    {
        do {
            $_SESSION[$secuencia] = (int) ($_SESSION[$secuencia] ?? 0) + 1;
            $id = sprintf('%s-%03d', $prefijo, $_SESSION[$secuencia]);
            $catalogo = $this->catalogo();
            $existe = match ($prefijo) {
                'ACT-S' => isset($catalogo['actividades_por_id'][$id]),
                'ORG-S' => isset($catalogo['institutos'][$id]),
                default => isset($catalogo['tareas_por_id'][$id]),
            };
        } while ($existe && (int) $_SESSION[$secuencia] < 999);

        return $id;
    }

    private function leerActividad(array $entrada, ?array $actual = null): array
    {
        $catalogo = $this->catalogo();
        $titulo = texto_post($entrada, 'titulo') ?? '';
        if (longitud($titulo) < 3 || longitud($titulo) > 180) {
            return ['error' => 'El nombre de la actividad debe tener entre 3 y 180 caracteres.'];
        }
        $descripcion = texto_post($entrada, 'descripcion') ?? '';
        if (longitud($descripcion) > 1000) {
            return ['error' => 'La descripción admite hasta 1000 caracteres.'];
        }
        $observaciones = texto_post($entrada, 'observaciones') ?? '';
        if (longitud($observaciones) > 1000) {
            return ['error' => 'Las observaciones admiten hasta 1000 caracteres.'];
        }
        $tipo = texto_post($entrada, 'tipo') ?? '';
        if (!in_array($tipo, tipos_actividad(), true)) {
            return ['error' => 'La categoría de la actividad no es válida.'];
        }
        $inicio = texto_post($entrada, 'fecha_inicio') ?? '';
        $fin = texto_post($entrada, 'fecha') ?? '';
        if (!fecha_valida($inicio) || !fecha_valida($fin)) {
            return ['error' => 'Las fechas de inicio y de finalización deben tener el formato AAAA-MM-DD.'];
        }
        $responsable = texto_post($entrada, 'responsable_id') ?? '';
        if (!isset($catalogo['usuarios'][$responsable])) {
            return ['error' => 'El responsable elegido no existe.'];
        }
        $institutos = $this->institutosElegidos($entrada, $catalogo['institutos']);
        if (isset($institutos['error'])) {
            return $institutos;
        }
        if ($actual !== null) {
            $quitados = array_diff($actual['institutos'], $institutos['ids']);
            if ($quitados !== []) {
                $ocupados = [];
                foreach ($catalogo['tareas'] as $tarea) {
                    if ($tarea['actividad_id'] === $actual['id']) {
                        $ocupados[$tarea['instituto_id']] = true;
                    }
                }
                foreach ($quitados as $quitado) {
                    $institutoQuitado = $catalogo['institutos'][$quitado] ?? null;
                    if (is_array($institutoQuitado) && (!instituto_activo($institutoQuitado) || registro_baja($institutoQuitado))) {
                        continue;
                    }
                    if (isset($ocupados[$quitado])) {
                        return ['error' => 'No se puede quitar un organismo que todavía tiene tareas en la actividad. Reasigne esas tareas primero.'];
                    }
                }
            }
        }

        foreach ($institutos['ids'] as $institutoId) {
            $instituto = $catalogo['institutos'][$institutoId];
            $yaParticipaba = $actual !== null && in_array($institutoId, $actual['institutos'], true);
            if (!instituto_activo($instituto) && !$yaParticipaba) {
                return ['error' => 'Un organismo desactivado no puede incorporarse a una actividad. Puede conservarse si ya participaba.'];
            }
        }

        return [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'tipo' => $tipo,
            'fecha_inicio' => $inicio,
            'fecha' => $fin,
            'responsable_id' => $responsable,
            'institutos' => $institutos['ids'],
            'observaciones' => $observaciones,
        ];
    }

    private function institutosElegidos(array $entrada, array $institutos): array
    {
        if (!isset($entrada['institutos']) || !is_array($entrada['institutos'])) {
            return ['error' => 'Seleccione al menos un organismo participante.'];
        }
        $pedidos = [];
        foreach ($entrada['institutos'] as $id) {
            if (is_string($id) && $id !== '') {
                $pedidos[$id] = true;
            }
        }
        if ($pedidos === []) {
            return ['error' => 'Seleccione al menos un organismo participante.'];
        }
        $ids = [];
        foreach ($institutos as $id => $instituto) {
            if (!isset($pedidos[$id])) {
                continue;
            }
            if (($instituto['tipo'] ?? '') !== 'instituto') {
                return ['error' => 'Solo se pueden asignar organismos dependientes.'];
            }
            $ids[] = $id;
            unset($pedidos[$id]);
        }
        if ($pedidos !== []) {
            return ['error' => 'Hay un organismo que no existe.'];
        }

        return ['ids' => $ids];
    }

    private function mismosIds(array $izquierda, array $derecha): bool
    {
        $a = array_values($izquierda);
        $b = array_values($derecha);
        sort($a);
        sort($b);

        return $a === $b;
    }

    private function compararCampo(
        array &$cambios,
        array &$patch,
        string $clave,
        string $etiqueta,
        string $anterior,
        string $nuevo,
        ?string $anteriorVisible = null,
        ?string $nuevoVisible = null
    ): void {
        if ($anterior === $nuevo) {
            return;
        }
        $patch[$clave] = $nuevo;
        $cambios[] = [$etiqueta, $anteriorVisible ?? $anterior, $nuevoVisible ?? $nuevo];
    }

    private function leerOrganismo(array $entrada, bool $exigirSigla, string $siglaActual = '', string $sedeActual = ''): array
    {
        $sigla = strtoupper(texto_post($entrada, 'sigla') ?? '');
        if ($sigla === '' && !$exigirSigla) {
            $sigla = strtoupper($siglaActual);
        }
        if (preg_match('/^[A-Z]{3,8}$/', $sigla) !== 1 || $sigla === 'DIGEDUC') {
            return ['error' => 'La sigla debe tener entre 3 y 8 letras.'];
        }
        $nombre = texto_post($entrada, 'nombre') ?? '';
        if (longitud($nombre) < 3 || longitud($nombre) > 160) {
            return ['error' => 'El nombre del organismo debe tener entre 3 y 160 caracteres.'];
        }
        $sede = texto_post($entrada, 'sede');
        if ($sede === null || $sede === '') {
            $sede = $sedeActual;
        }
        if (longitud($sede) > 120) {
            return ['error' => 'La sede debe tener hasta 120 caracteres.'];
        }
        $descripcion = texto_post($entrada, 'descripcion') ?? '';
        if (longitud($descripcion) > 500) {
            return ['error' => 'La descripción admite hasta 500 caracteres.'];
        }
        $responsable = texto_post($entrada, 'responsable') ?? '';
        if (longitud($responsable) < 3 || longitud($responsable) > 120) {
            return ['error' => 'El nombre del responsable debe tener entre 3 y 120 caracteres.'];
        }

        return [
            'sigla' => $sigla,
            'nombre' => $nombre,
            'sede' => $sede,
            'descripcion' => $descripcion,
            'responsable' => $responsable,
        ];
    }

    private function siglaOcupada(string $sigla, array $institutos, string $exceptoId = ''): bool
    {
        foreach ($institutos as $instituto) {
            if ((string) ($instituto['id'] ?? '') === $exceptoId) {
                continue;
            }
            if (strtoupper((string) ($instituto['sigla'] ?? '')) === $sigla) {
                return true;
            }
        }

        return false;
    }

    private function guardarCambioOrganismo(string $id, array $patch): void
    {
        if (!isset($_SESSION['organismos_cambio']) || !is_array($_SESSION['organismos_cambio'])) {
            $_SESSION['organismos_cambio'] = [];
        }
        $previo = is_array($_SESSION['organismos_cambio'][$id] ?? null) ? $_SESSION['organismos_cambio'][$id] : [];
        $_SESSION['organismos_cambio'][$id] = array_merge($previo, $patch);
    }

    private function leerMotivo(array $entrada): array
    {
        $motivo = texto_post($entrada, 'motivo') ?? '';
        if (longitud($motivo) < 8 || longitud($motivo) > 500) {
            return ['error' => 'El motivo debe tener entre 8 y 500 caracteres.'];
        }

        return ['motivo' => $motivo];
    }

    private function rechazarSiBaja(array $catalogo, array $tarea): ?array
    {
        if (registro_baja($tarea)) {
            return ['tipo' => 'error', 'texto' => 'La tarea está dada de baja y no puede modificarse.'];
        }
        $actividad = $catalogo['actividades_por_id'][$tarea['actividad_id']] ?? null;
        if (is_array($actividad) && registro_baja($actividad)) {
            return ['tipo' => 'error', 'texto' => 'La actividad está dada de baja y sus tareas no pueden modificarse.'];
        }

        return null;
    }

    private function nombreResponsable(string $institutoId, array $usuarios): string
    {
        foreach ($usuarios as $usuario) {
            if ($usuario['instituto_id'] === $institutoId && $usuario['perfil'] === 'responsable') {
                return (string) $usuario['nombre'];
            }
        }

        return '';
    }

    private function asociarResponsable(string $institutoId, string $nombre, array $usuarios): void
    {
        foreach ($usuarios as $usuario) {
            if ($usuario['instituto_id'] === $institutoId && $usuario['perfil'] === 'responsable') {
                if (!isset($_SESSION['usuarios_cambio']) || !is_array($_SESSION['usuarios_cambio'])) {
                    $_SESSION['usuarios_cambio'] = [];
                }
                $previo = is_array($_SESSION['usuarios_cambio'][$usuario['id']] ?? null) ? $_SESSION['usuarios_cambio'][$usuario['id']] : [];
                $previo['nombre'] = $nombre;
                $_SESSION['usuarios_cambio'][$usuario['id']] = $previo;

                return;
            }
        }

        $id = 'u-' . strtolower($institutoId);
        if (!isset($_SESSION['usuarios_alta']) || !is_array($_SESSION['usuarios_alta'])) {
            $_SESSION['usuarios_alta'] = [];
        }
        $_SESSION['usuarios_alta'][$id] = [
            'id' => $id,
            'nombre' => $nombre,
            'perfil' => 'responsable',
            'instituto_id' => $institutoId,
            'cargo' => 'Responsable de organismo',
        ];
    }

    private function avisoPlazo(string $inicio, string $fin): string
    {
        if (plazo_coherente($inicio, $fin)) {
            return '';
        }

        return ' Las fechas quedaron guardadas, pero no forman un plazo válido.';
    }
}
