<?php

declare(strict_types=1);

/**
 * Contrato del origen de datos.
 * RepositorioFicticio resuelve el prototipo.
 * Una implementación PDO puede reemplazarlo para MySQL 8 o MariaDB 10.11
 * sin cambiar las vistas: debe devolver los mismos arreglos.
 */
interface RepositorioOperativo
{
    /** @return list<array<string, mixed>> */
    public function institutos(): array;

    /** @return list<array<string, mixed>> */
    public function usuarios(): array;

    /** @return list<array<string, mixed>> */
    public function actividades(): array;

    /** @return list<array<string, mixed>> */
    public function tareas(): array;

    /** @return list<array<string, mixed>> */
    public function requerimientos(): array;

    /** @return list<array<string, mixed>> */
    public function auditoria(): array;
}

final class RepositorioFicticio implements RepositorioOperativo
{
    public function institutos(): array
    {
        return [
            [
                'id' => 'DIGEDUC',
                'sigla' => 'DIGEDUC',
                'nombre' => 'Dirección General de Educación',
                'tipo' => 'autoridad',
                'sede' => 'Sede central ficticia',
                'descripcion' => 'Autoridad central de esta demostración. Concentra la vista de todos los institutos dependientes.',
            ],
            [
                'id' => 'IFAL',
                'sigla' => 'IFAL',
                'nombre' => 'Instituto de Formación Aeronáutica del Litoral',
                'tipo' => 'instituto',
                'sede' => 'Sede Litoral',
                'descripcion' => 'Instituto ficticio de formación aeronáutica.',
            ],
            [
                'id' => 'ISEL',
                'sigla' => 'ISEL',
                'nombre' => 'Instituto Superior de Estudios Logísticos',
                'tipo' => 'instituto',
                'sede' => 'Sede Central Norte',
                'descripcion' => 'Instituto ficticio de estudios logísticos.',
            ],
            [
                'id' => 'CCTA',
                'sigla' => 'CCTA',
                'nombre' => 'Centro de Capacitación Técnica Austral',
                'tipo' => 'instituto',
                'sede' => 'Sede Austral',
                'descripcion' => 'Centro ficticio de capacitación técnica.',
            ],
            [
                'id' => 'IGAN',
                'sigla' => 'IGAN',
                'nombre' => 'Instituto de Gestión Académica del Norte',
                'tipo' => 'instituto',
                'sede' => 'Sede Norte',
                'descripcion' => 'Instituto ficticio de gestión académica.',
            ],
            [
                'id' => 'EEOP',
                'sigla' => 'EEOP',
                'nombre' => 'Escuela de Especialidades Operativas',
                'tipo' => 'instituto',
                'sede' => 'Sede Este',
                'descripcion' => 'Escuela ficticia de especialidades operativas.',
            ],
        ];
    }

    public function usuarios(): array
    {
        return [
            ['id' => 'u-quelvo', 'nombre' => 'Mariana Quelvo', 'perfil' => 'admin', 'instituto_id' => 'DIGEDUC', 'cargo' => 'Coordinación central de seguimiento'],
            ['id' => 'u-herrera', 'nombre' => 'Tomás Herrera', 'perfil' => 'responsable', 'instituto_id' => 'IFAL', 'cargo' => 'Responsable de instituto'],
            ['id' => 'u-benitez', 'nombre' => 'Lucía Benítez', 'perfil' => 'responsable', 'instituto_id' => 'ISEL', 'cargo' => 'Responsable de instituto'],
            ['id' => 'u-almada', 'nombre' => 'Pedro Almada', 'perfil' => 'responsable', 'instituto_id' => 'CCTA', 'cargo' => 'Responsable de instituto'],
            ['id' => 'u-rivas', 'nombre' => 'Sofía Rivas', 'perfil' => 'responsable', 'instituto_id' => 'IGAN', 'cargo' => 'Responsable de instituto'],
            ['id' => 'u-molina', 'nombre' => 'Andrés Molina', 'perfil' => 'responsable', 'instituto_id' => 'EEOP', 'cargo' => 'Responsable de instituto'],
            ['id' => 'u-duarte', 'nombre' => 'Elena Duarte', 'perfil' => 'consulta', 'instituto_id' => 'DIGEDUC', 'cargo' => 'Consulta institucional'],
        ];
    }

    public function actividades(): array
    {
        $filas = [
            ['ACT-2026-001', 'Acto conmemorativo de la sede central', 'Ceremonia ya realizada. Queda como antecedente cerrado de la demostración.', 'Ceremonias y actos oficiales', -25, 'u-quelvo', ['IFAL', 'ISEL', 'CCTA', 'IGAN', 'EEOP'], 'Actividad cerrada. El semáforo no la trata como vencida pendiente.'],
            ['ACT-2026-002', 'Simulación integrada de evaluación', 'Ejercicio académico futuro, todavía sin tareas iniciadas.', 'Ejercicios y actividades académicas', 21, 'u-almada', ['CCTA', 'EEOP', 'IGAN'], ''],
            ['ACT-2026-003', 'Revisión presencial de planes de estudio', 'Comisión de dos institutos con fechas de tarea distintas de la fecha de la actividad.', 'Comisiones y desplazamientos', 4, 'u-benitez', ['IFAL', 'ISEL'], 'La fecha de la comisión y los vencimientos de cada instituto no coinciden.'],
            ['ACT-2026-004', 'Enlace operativo con la autoridad central', 'Comisión de coordinación entre la escuela operativa, el centro austral y la autoridad central.', 'Comisiones y desplazamientos', 28, 'u-molina', ['EEOP', 'CCTA'], ''],
            ['ACT-2026-005', 'Desplazamiento de auditoría académica', 'Traslado terrestre ficticio para una ronda de entrevistas académicas.', 'Comisiones y desplazamientos', 6, 'u-rivas', ['ISEL', 'IGAN'], 'La salida depende de una autorización que sigue pendiente.'],
            ['ACT-2026-006', 'Jornada de metodologías de enseñanza', 'Jornada de trabajo entre el centro austral y el instituto del litoral.', 'Ejercicios y actividades académicas', 11, 'u-almada', ['CCTA', 'IFAL'], ''],
            ['ACT-2026-007', 'Traslado aéreo de comisión académica', 'Vuelo ficticio. Una tarea ya se cerró y la otra sigue abierta después de la fecha.', 'Vuelos programados', -3, 'u-molina', ['EEOP', 'IFAL'], 'La recepción académica está cerrada. El tramo aéreo sigue pendiente.'],
            ['ACT-2026-008', 'Inspección aérea de sedes formativas', 'Recorrido planificado, aún sin ejecución.', 'Vuelos programados', 9, 'u-molina', ['EEOP'], ''],
            ['ACT-2026-009', 'Regularización de legajos docentes', 'Actualización administrativa de legajos ficticios en dos institutos.', 'Requerimientos administrativos', -12, 'u-rivas', ['IGAN', 'ISEL'], 'La actividad superó su fecha y conserva tareas abiertas.'],
            ['ACT-2026-010', 'Adquisición de material didáctico', 'Compra interna detenida por un impedimento de la tarea.', 'Requerimientos administrativos', -6, 'u-benitez', ['ISEL'], 'No puede avanzar hasta resolver el impedimento registrado.'],
            ['ACT-2026-011', 'Coordinación mensual de institutos', 'Reunión de hoy con tareas en distinto plazo y distinto estado.', 'Reuniones institucionales', 0, 'u-quelvo', ['IFAL', 'ISEL', 'CCTA', 'IGAN', 'EEOP'], 'Incluye una tarea bloqueada y vencida junto con otras en plazo o ya cerradas.'],
            ['ACT-2026-012', 'Cierre de indicadores del trimestre', 'Reunión de cierre cuya fecha de realización ya pasó.', 'Reuniones institucionales', -1, 'u-quelvo', ['IFAL', 'ISEL', 'CCTA', 'IGAN', 'EEOP'], 'Parte de los institutos ya entregó su indicador y otros no.'],
            ['ACT-2026-013', 'Actualización del calendario operativo', 'Ajuste del calendario del instituto norte con dos vencimientos propios.', 'Otras actividades operativas', 2, 'u-rivas', ['IGAN'], ''],
            ['ACT-2026-014', 'Apertura del ciclo lectivo institucional', 'Acto de apertura con tres institutos y una solicitud central ya resuelta.', 'Ceremonias y actos oficiales', 18, 'u-quelvo', ['IFAL', 'CCTA', 'EEOP'], ''],
            ['ACT-2026-015', 'Ceremonia de egreso de la cohorte', 'Acto futuro, todavía en preparación.', 'Ceremonias y actos oficiales', 40, 'u-herrera', ['IFAL', 'CCTA'], ''],
            ['ACT-2026-016', 'Informe de lecciones de la jornada técnica', 'Informe cerrado del centro austral.', 'Otras actividades operativas', -18, 'u-almada', ['CCTA'], 'Actividad finalizada. Sirve para distinguir el cierre del atraso.'],
        ];

        $actividades = [];
        foreach ($filas as $fila) {
            $actividades[] = [
                'id' => $fila[0],
                'titulo' => $fila[1],
                'descripcion' => $fila[2],
                'tipo' => $fila[3],
                'fecha' => fecha_desde_hoy($fila[4]),
                'responsable_id' => $fila[5],
                'institutos' => $fila[6],
                'observaciones' => $fila[7],
            ];
        }

        return $actividades;
    }

    public function tareas(): array
    {
        $filas = [
            ['TAR-001-01', 'ACT-2026-001', 'IFAL', 'u-herrera', 'Protocolo de la delegación del Litoral.', -28, 'Finalizada', 100, 'Cerrada sin observaciones.'],
            ['TAR-001-02', 'ACT-2026-001', 'ISEL', 'u-benitez', 'Montaje logístico del acto.', -27, 'Finalizada', 100, ''],
            ['TAR-001-03', 'ACT-2026-001', 'CCTA', 'u-almada', 'Apoyo técnico de sonido.', -26, 'Finalizada', 100, ''],
            ['TAR-001-04', 'ACT-2026-001', 'IGAN', 'u-rivas', 'Lista de asistentes institucionales.', -30, 'Finalizada', 100, ''],
            ['TAR-001-05', 'ACT-2026-001', 'EEOP', 'u-molina', 'Coordinación de traslados de la ceremonia.', -26, 'Finalizada', 100, ''],

            ['TAR-002-01', 'ACT-2026-002', 'CCTA', 'u-almada', 'Diseño del escenario de simulación.', 18, 'Pendiente', 0, ''],
            ['TAR-002-02', 'ACT-2026-002', 'EEOP', 'u-molina', 'Guion de desplazamiento del ejercicio.', 16, 'Pendiente', 0, ''],
            ['TAR-002-03', 'ACT-2026-002', 'IGAN', 'u-rivas', 'Criterios de evaluación académica.', 20, 'Pendiente', 0, ''],

            ['TAR-003-01', 'ACT-2026-003', 'IFAL', 'u-herrera', 'Informe de observaciones del plan del Litoral.', 3, 'En curso', 70, 'Borrador en revisión.'],
            ['TAR-003-02', 'ACT-2026-003', 'ISEL', 'u-benitez', 'Carpeta de comparación de planes.', 6, 'Pendiente', 15, 'Falta el material impreso.'],

            ['TAR-004-01', 'ACT-2026-004', 'EEOP', 'u-molina', 'Agenda de la comisión de enlace.', 25, 'En curso', 40, ''],
            ['TAR-004-02', 'ACT-2026-004', 'CCTA', 'u-almada', 'Relevamiento de aulas en la sede austral.', 27, 'Pendiente', 0, ''],

            ['TAR-005-01', 'ACT-2026-005', 'ISEL', 'u-benitez', 'Logística del traslado terrestre.', 5, 'En curso', 50, ''],
            ['TAR-005-02', 'ACT-2026-005', 'IGAN', 'u-rivas', 'Programa de entrevistas académicas.', 6, 'En curso', 35, 'A la espera de la autorización central.'],

            ['TAR-006-01', 'ACT-2026-006', 'CCTA', 'u-almada', 'Programa de la jornada y de los talleres.', 9, 'En curso', 45, ''],
            ['TAR-006-02', 'ACT-2026-006', 'IFAL', 'u-herrera', 'Confirmación de docentes participantes.', 8, 'En curso', 30, ''],

            ['TAR-007-01', 'ACT-2026-007', 'EEOP', 'u-molina', 'Confirmación operativa del vuelo ficticio.', -2, 'Pendiente', 40, 'El cupo sigue sin cerrar.'],
            ['TAR-007-02', 'ACT-2026-007', 'IFAL', 'u-herrera', 'Recepción académica en la sede de destino.', -5, 'Finalizada', 100, 'Cerrada aunque su fecha ya pasó.'],

            ['TAR-008-01', 'ACT-2026-008', 'EEOP', 'u-molina', 'Plan de ruta de la inspección.', 9, 'Pendiente', 0, ''],

            ['TAR-009-01', 'ACT-2026-009', 'IGAN', 'u-rivas', 'Carga de legajos de la sede norte.', -8, 'En curso', 60, 'Falta la constancia de carga.'],
            ['TAR-009-02', 'ACT-2026-009', 'ISEL', 'u-benitez', 'Revisión cruzada de la documentación.', -3, 'Pendiente', 10, ''],

            ['TAR-010-01', 'ACT-2026-010', 'ISEL', 'u-benitez', 'Comparación de presupuestos y pedido interno.', -4, 'Bloqueada', 35, 'Bloqueada por falta del presupuesto comparativo.'],

            ['TAR-011-01', 'ACT-2026-011', 'IFAL', 'u-herrera', 'Novedades académicas del Litoral.', 1, 'En curso', 50, ''],
            ['TAR-011-02', 'ACT-2026-011', 'ISEL', 'u-benitez', 'Informe logístico mensual.', -1, 'Bloqueada', 20, 'Bloqueada: falta un dato de depósito.'],
            ['TAR-011-03', 'ACT-2026-011', 'CCTA', 'u-almada', 'Novedades de capacitación austral.', 10, 'Pendiente', 0, ''],
            ['TAR-011-04', 'ACT-2026-011', 'IGAN', 'u-rivas', 'Indicadores de gestión del mes.', 0, 'En curso', 60, ''],
            ['TAR-011-05', 'ACT-2026-011', 'EEOP', 'u-molina', 'Parte de traslados del mes anterior.', -8, 'Finalizada', 100, ''],

            ['TAR-012-01', 'ACT-2026-012', 'IFAL', 'u-herrera', 'Indicadores académicos del Litoral.', -2, 'Finalizada', 100, ''],
            ['TAR-012-02', 'ACT-2026-012', 'ISEL', 'u-benitez', 'Indicadores logísticos.', -1, 'En curso', 80, ''],
            ['TAR-012-03', 'ACT-2026-012', 'CCTA', 'u-almada', 'Indicadores de capacitación.', 1, 'Pendiente', 0, ''],
            ['TAR-012-04', 'ACT-2026-012', 'IGAN', 'u-rivas', 'Indicadores de gestión.', -1, 'En curso', 40, ''],
            ['TAR-012-05', 'ACT-2026-012', 'EEOP', 'u-molina', 'Parte de movimientos del trimestre.', -1, 'Pendiente', 0, ''],

            ['TAR-013-01', 'ACT-2026-013', 'IGAN', 'u-rivas', 'Versión consolidada del calendario.', 2, 'En curso', 65, ''],
            ['TAR-013-02', 'ACT-2026-013', 'IGAN', 'u-rivas', 'Compatibilización con el calendario central.', 4, 'Pendiente', 0, ''],

            ['TAR-014-01', 'ACT-2026-014', 'IFAL', 'u-herrera', 'Guion del acto y lista de oradores.', 12, 'En curso', 55, ''],
            ['TAR-014-02', 'ACT-2026-014', 'CCTA', 'u-almada', 'Apoyo técnico de la sede austral.', 16, 'Pendiente', 0, ''],
            ['TAR-014-03', 'ACT-2026-014', 'EEOP', 'u-molina', 'Esquema de traslados de autoridades.', 10, 'En curso', 25, ''],

            ['TAR-015-01', 'ACT-2026-015', 'IFAL', 'u-herrera', 'Padrón ficticio de egresados.', 35, 'Pendiente', 0, ''],
            ['TAR-015-02', 'ACT-2026-015', 'CCTA', 'u-almada', 'Protocolo de la sede que recibe el acto.', 30, 'Pendiente', 0, ''],

            ['TAR-016-01', 'ACT-2026-016', 'CCTA', 'u-almada', 'Relatoría de la jornada.', -20, 'Finalizada', 100, ''],
            ['TAR-016-02', 'ACT-2026-016', 'CCTA', 'u-almada', 'Recomendaciones para el próximo ciclo.', -18, 'Finalizada', 100, ''],
        ];

        $tareas = [];
        foreach ($filas as $fila) {
            $estado = $fila[6];
            $avance = $estado === 'Finalizada' ? 100 : $fila[7];
            $tareas[] = [
                'id' => $fila[0],
                'actividad_id' => $fila[1],
                'instituto_id' => $fila[2],
                'usuario_id' => $fila[3],
                'descripcion' => $fila[4],
                'fecha_limite' => fecha_desde_hoy($fila[5]),
                'estado' => $estado,
                'avance' => $avance,
                'observaciones' => $fila[8],
            ];
        }

        return $tareas;
    }

    public function requerimientos(): array
    {
        $filas = [
            ['REQ-001', 'ACT-2026-009', 'TAR-009-01', 'Necesidad de recursos', 'Hace falta un lote ficticio de formularios para completar la carga de legajos.', 'IGAN', 'DIGEDUC', 'u-quelvo', 'Pendiente', -4, ''],
            ['REQ-002', 'ACT-2026-005', 'TAR-005-02', 'Solicitud a la autoridad central', 'Se solicita autorización central para el desplazamiento de la comisión de auditoría.', 'IGAN', 'DIGEDUC', 'u-quelvo', 'Pendiente', -1, ''],
            ['REQ-003', 'ACT-2026-004', 'TAR-004-01', 'Requerimiento a otro instituto', 'Se pide a Estudios Logísticos el cuadro de aulas disponibles para la comisión de enlace.', 'EEOP', 'ISEL', 'u-benitez', 'Pendiente', -2, ''],
            ['REQ-004', 'ACT-2026-010', 'TAR-010-01', 'Impedimento', 'La tarea está bloqueada porque el proveedor ficticio no entregó el presupuesto comparativo.', 'ISEL', 'DIGEDUC', 'u-quelvo', 'Pendiente', -3, 'Sin fecha de entrega confirmada.'],
            ['REQ-005', 'ACT-2026-001', 'TAR-001-03', 'Necesidad de recursos', 'Sonido y protocolo para el acto conmemorativo.', 'CCTA', 'DIGEDUC', 'u-quelvo', 'Resuelto', -30, 'Cubierto con medios de la sede central.'],
            ['REQ-006', 'ACT-2026-014', 'TAR-014-01', 'Solicitud a la autoridad central', 'Confirmación del esquema de oradores de la apertura.', 'IFAL', 'DIGEDUC', 'u-quelvo', 'Resuelto', -6, 'Esquema confirmado.'],
            ['REQ-007', 'ACT-2026-007', 'TAR-007-02', 'Impedimento', 'La sede de destino no tenía confirmada la recepción. Luego se resolvió y la tarea se cerró.', 'IFAL', 'DIGEDUC', 'u-quelvo', 'Resuelto', -8, 'Recepción confirmada.'],
            ['REQ-008', 'ACT-2026-006', 'TAR-006-01', 'Requerimiento a otro instituto', 'Se solicita al Instituto del Litoral el listado de docentes que participarán en la jornada.', 'CCTA', 'IFAL', 'u-herrera', 'En atención', -2, 'El listado está en preparación.'],
            ['REQ-009', 'ACT-2026-003', 'TAR-003-02', 'Necesidad de recursos', 'Se necesita material de trabajo impreso para la mesa de revisión de planes.', 'ISEL', 'DIGEDUC', 'u-quelvo', 'En atención', -1, ''],
        ];

        $requerimientos = [];
        foreach ($filas as $fila) {
            $requerimientos[] = [
                'id' => $fila[0],
                'actividad_id' => $fila[1],
                'tarea_id' => $fila[2],
                'tipo' => $fila[3],
                'descripcion' => $fila[4],
                'instituto_solicitante' => $fila[5],
                'instituto_destino' => $fila[6],
                'responsable_id' => $fila[7],
                'estado' => $fila[8],
                'creado' => fecha_desde_hoy($fila[9]),
                'observaciones' => $fila[10],
            ];
        }

        return $requerimientos;
    }

    public function auditoria(): array
    {
        $filas = [
            ['AUD-001', 'u-herrera', 'IFAL', 'IFAL', -1, '09:40', 'Tarea TAR-014-01', 'Porcentaje de avance', '40 %', '55 %'],
            ['AUD-002', 'u-benitez', 'ISEL', 'ISEL', -2, '16:05', 'Tarea TAR-010-01', 'Estado', 'En curso', 'Bloqueada'],
            ['AUD-003', 'u-quelvo', 'DIGEDUC', 'IFAL', -4, '11:10', 'Requerimiento REQ-006', 'Estado de resolución', 'Pendiente', 'Resuelto'],
            ['AUD-004', 'u-rivas', 'IGAN', 'IGAN', -1, '08:15', 'Tarea TAR-009-01', 'Observaciones', '—', 'Falta la constancia de carga.'],
            ['AUD-005', 'u-molina', 'EEOP', 'EEOP', -1, '18:22', 'Tarea TAR-007-01', 'Porcentaje de avance', '20 %', '40 %'],
            ['AUD-006', 'u-almada', 'CCTA', 'CCTA', -18, '13:00', 'Tarea TAR-016-01', 'Estado', 'En curso', 'Finalizada'],
            ['AUD-007', 'u-quelvo', 'DIGEDUC', 'ISEL', 0, '08:05', 'Tarea TAR-012-02', 'Porcentaje de avance', '70 %', '80 %'],
            ['AUD-008', 'u-benitez', 'ISEL', 'ISEL', -3, '10:12', 'Requerimiento REQ-004', 'Alta de requerimiento', '—', 'Impedimento registrado'],
            ['AUD-009', 'u-molina', 'EEOP', 'EEOP', -6, '12:40', 'Tarea TAR-011-05', 'Estado', 'En curso', 'Finalizada'],
            ['AUD-010', 'u-herrera', 'IFAL', 'IFAL', -2, '15:18', 'Requerimiento REQ-008', 'Estado de resolución', 'Pendiente', 'En atención'],
        ];

        $auditoria = [];
        foreach ($filas as $fila) {
            $auditoria[] = [
                'id' => $fila[0],
                'usuario_id' => $fila[1],
                'instituto_id' => $fila[2],
                'ambito_id' => $fila[3],
                'cuando' => momento_desde_hoy($fila[4], $fila[5]),
                'entidad' => $fila[6],
                'campo' => $fila[7],
                'anterior' => $fila[8],
                'nuevo' => $fila[9],
            ];
        }

        return $auditoria;
    }
}
