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
                'sigla' => 'CENTRAL',
                'nombre' => 'Organismo central',
                'tipo' => 'autoridad',
                'sede' => 'Sede central ficticia',
                'descripcion' => 'Autoridad central de esta demostración. Concentra la vista de todos los institutos dependientes.',
            ],
            [
                'id' => 'FACFA',
                'sigla' => 'FACFA',
                'nombre' => 'Facultad de la Fuerza Aérea',
                'tipo' => 'instituto',
                'sede' => '',
                'descripcion' => '',
            ],
            [
                'id' => 'EAM',
                'sigla' => 'EAM',
                'nombre' => 'Escuela de Aviación Militar',
                'tipo' => 'instituto',
                'sede' => '',
                'descripcion' => '',
            ],
            [
                'id' => 'ESFAC',
                'sigla' => 'ESFAC',
                'nombre' => 'Escuela de Suboficiales de la Fuerza Aérea Córdoba',
                'tipo' => 'instituto',
                'sede' => '',
                'descripcion' => '',
            ],
            [
                'id' => 'ESFAE',
                'sigla' => 'ESFAE',
                'nombre' => 'Escuela de Suboficiales de la Fuerza Aérea Ezeiza',
                'tipo' => 'instituto',
                'sede' => '',
                'descripcion' => '',
            ],
            [
                'id' => 'LAM',
                'sigla' => 'LAM',
                'nombre' => 'Liceo Aeronáutico Militar',
                'tipo' => 'instituto',
                'sede' => '',
                'descripcion' => '',
            ],
            [
                'id' => 'ESGA',
                'sigla' => 'ESGA',
                'nombre' => 'Escuela Superior de Guerra Aérea',
                'tipo' => 'instituto',
                'sede' => '',
                'descripcion' => '',
            ],
            [
                'id' => 'IUA',
                'sigla' => 'IUA',
                'nombre' => 'Centro Regional Universitario Córdoba',
                'tipo' => 'instituto',
                'sede' => '',
                'descripcion' => '',
            ],
            [
                'id' => 'INDAE',
                'sigla' => 'INDAE',
                'nombre' => 'Instituto Nacional de Derecho Aeronáutico y Espacial',
                'tipo' => 'instituto',
                'sede' => '',
                'descripcion' => '',
            ],
            [
                'id' => 'CIIFA',
                'sigla' => 'CIIFA',
                'nombre' => 'Centro de Instrucción de Idiomas',
                'tipo' => 'instituto',
                'sede' => '',
                'descripcion' => '',
            ],
        ];
    }

    public function usuarios(): array
    {
        return [
            ['id' => 'u-quelvo', 'nombre' => 'Mariana Quelvo', 'perfil' => 'admin', 'instituto_id' => 'DIGEDUC', 'cargo' => 'Coordinación central de seguimiento'],
            ['id' => 'u-herrera', 'nombre' => 'Tomás Herrera', 'perfil' => 'responsable', 'instituto_id' => 'FACFA', 'cargo' => 'Responsable de instituto'],
            ['id' => 'u-benitez', 'nombre' => 'Lucía Benítez', 'perfil' => 'responsable', 'instituto_id' => 'EAM', 'cargo' => 'Responsable de instituto'],
            ['id' => 'u-almada', 'nombre' => 'Pedro Almada', 'perfil' => 'responsable', 'instituto_id' => 'ESGA', 'cargo' => 'Responsable de instituto'],
            ['id' => 'u-rivas', 'nombre' => 'Sofía Rivas', 'perfil' => 'responsable', 'instituto_id' => 'IUA', 'cargo' => 'Responsable de instituto'],
            ['id' => 'u-molina', 'nombre' => 'Andrés Molina', 'perfil' => 'responsable', 'instituto_id' => 'CIIFA', 'cargo' => 'Responsable de instituto'],
            ['id' => 'u-duarte', 'nombre' => 'Elena Duarte', 'perfil' => 'consulta', 'instituto_id' => 'DIGEDUC', 'cargo' => 'Consulta institucional'],
        ];
    }

    public function actividades(): array
    {
        $filas = [
            ['ACT-2026-001', 'Acto conmemorativo de la sede central', 'Ceremonia ya realizada. Queda como antecedente cerrado de la demostración.', 'Ceremonias y actos oficiales', -25, 'u-quelvo', ['FACFA', 'EAM', 'ESGA', 'IUA', 'CIIFA'], 'Actividad cerrada. El semáforo no la trata como vencida pendiente.'],
            ['ACT-2026-002', 'Ejercicio de simulación integrada', 'Ejercicio futuro, todavía sin tareas iniciadas.', 'Ejercicios y actividades académicas', 21, 'u-almada', ['ESGA', 'CIIFA', 'IUA'], ''],
            ['ACT-2026-003', 'Revisión presencial de planes de estudio', 'Comisión de dos institutos con fechas de tarea distintas de la fecha de la actividad.', 'Comisiones y desplazamientos', 4, 'u-benitez', ['FACFA', 'EAM'], 'La fecha de la comisión y los vencimientos de cada instituto no coinciden.'],
            ['ACT-2026-004', 'Enlace operativo con la autoridad central', 'Comisión de coordinación entre la escuela operativa, el centro austral y la autoridad central.', 'Comisiones y desplazamientos', 28, 'u-molina', ['CIIFA', 'ESGA'], ''],
            ['ACT-2026-005', 'Comisión de revisión académica', 'Traslado terrestre ficticio para entrevistas de formación.', 'Comisiones y desplazamientos', 6, 'u-rivas', ['EAM', 'IUA'], 'La salida depende de una autorización que sigue pendiente.'],
            ['ACT-2026-006', 'Jornada de metodologías de enseñanza', 'Jornada de trabajo entre el centro austral y el instituto del litoral.', 'Ejercicios y actividades académicas', 11, 'u-almada', ['ESGA', 'FACFA'], ''],
            ['ACT-2026-007', 'Traslado aéreo de comisión académica', 'Vuelo ficticio. Una tarea ya se cerró y la otra sigue abierta después de la fecha.', 'Vuelos programados', -3, 'u-molina', ['CIIFA', 'FACFA'], 'La recepción académica está cerrada. El tramo aéreo sigue pendiente.'],
            ['ACT-2026-008', 'Inspección aérea de sedes formativas', 'Recorrido planificado, aún sin ejecución.', 'Vuelos programados', 9, 'u-molina', ['CIIFA'], ''],
            ['ACT-2026-009', 'Regularización de legajos docentes', 'Actualización administrativa de legajos ficticios en dos institutos.', 'Requerimientos administrativos', -12, 'u-rivas', ['IUA', 'EAM'], 'La actividad superó su fecha y conserva tareas abiertas.'],
            ['ACT-2026-010', 'Requerimiento de material de instrucción', 'Pedido interno detenido por un impedimento de la tarea.', 'Requerimientos administrativos', -6, 'u-benitez', ['EAM'], 'No puede avanzar hasta resolver el impedimento registrado.'],
            ['ACT-2026-011', 'Coordinación mensual de institutos', 'Reunión de hoy con tareas en distinto plazo y distinto estado.', 'Reuniones institucionales', 0, 'u-quelvo', ['FACFA', 'EAM', 'ESGA', 'IUA', 'CIIFA'], 'Incluye una tarea bloqueada y vencida junto con otras en plazo o ya cerradas.'],
            ['ACT-2026-012', 'Parte de situación del período', 'Coordinación cuya fecha ya pasó y todavía tiene novedades pendientes.', 'Reuniones institucionales', -1, 'u-quelvo', ['FACFA', 'EAM', 'ESGA', 'IUA', 'CIIFA'], 'Parte de los organismos ya presentó su novedad y otros no.'],
            ['ACT-2026-013', 'Actualización del calendario operativo', 'Ajuste del calendario del instituto norte con dos vencimientos propios.', 'Otras actividades operativas', 2, 'u-rivas', ['IUA'], ''],
            ['ACT-2026-014', 'Apertura del ciclo lectivo institucional', 'Acto de apertura con tres institutos y una solicitud central ya resuelta.', 'Ceremonias y actos oficiales', 18, 'u-quelvo', ['FACFA', 'ESGA', 'CIIFA'], ''],
            ['ACT-2026-015', 'Ceremonia de egreso de la cohorte', 'Acto futuro, todavía en preparación.', 'Ceremonias y actos oficiales', 40, 'u-herrera', ['FACFA', 'ESGA'], ''],
            ['ACT-2026-016', 'Informe de lecciones de la jornada técnica', 'Informe cerrado del centro austral.', 'Otras actividades operativas', -18, 'u-almada', ['ESGA'], 'Actividad finalizada. Sirve para distinguir el cierre del atraso.'],
        ];

        $actividades = [];
        foreach ($filas as $fila) {
            $actividades[] = [
                'id' => $fila[0],
                'titulo' => $fila[1],
                'descripcion' => $fila[2],
                'tipo' => $fila[3],
                'fecha_inicio' => fecha_desde_hoy($fila[4] - 14),
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
            ['TAR-001-01', 'ACT-2026-001', 'FACFA', 'u-herrera', 'Protocolo de la delegación del Litoral.', -28, 'Finalizada', 100, 'Cerrada sin observaciones.'],
            ['TAR-001-02', 'ACT-2026-001', 'EAM', 'u-benitez', 'Montaje logístico del acto.', -27, 'Finalizada', 100, ''],
            ['TAR-001-03', 'ACT-2026-001', 'ESGA', 'u-almada', 'Apoyo técnico de sonido.', -26, 'Finalizada', 100, ''],
            ['TAR-001-04', 'ACT-2026-001', 'IUA', 'u-rivas', 'Lista de asistentes institucionales.', -30, 'Finalizada', 100, ''],
            ['TAR-001-05', 'ACT-2026-001', 'CIIFA', 'u-molina', 'Coordinación de traslados de la ceremonia.', -26, 'Finalizada', 100, ''],

            ['TAR-002-01', 'ACT-2026-002', 'ESGA', 'u-almada', 'Diseño del escenario de simulación.', 18, 'Pendiente', 0, ''],
            ['TAR-002-02', 'ACT-2026-002', 'CIIFA', 'u-molina', 'Guion de desplazamiento del ejercicio.', 16, 'Pendiente', 0, ''],
            ['TAR-002-03', 'ACT-2026-002', 'IUA', 'u-rivas', 'Criterios de evaluación académica.', 20, 'Pendiente', 0, ''],

            ['TAR-003-01', 'ACT-2026-003', 'FACFA', 'u-herrera', 'Informe de observaciones del plan del Litoral.', 3, 'En curso', 70, 'Borrador en revisión.'],
            ['TAR-003-02', 'ACT-2026-003', 'EAM', 'u-benitez', 'Carpeta de comparación de planes.', 6, 'Pendiente', 15, 'Falta el material impreso.'],

            ['TAR-004-01', 'ACT-2026-004', 'CIIFA', 'u-molina', 'Agenda de la comisión de enlace.', 25, 'En curso', 40, ''],
            ['TAR-004-02', 'ACT-2026-004', 'ESGA', 'u-almada', 'Relevamiento de aulas en la sede austral.', 27, 'Pendiente', 0, ''],

            ['TAR-005-01', 'ACT-2026-005', 'EAM', 'u-benitez', 'Logística del traslado terrestre.', 5, 'En curso', 50, ''],
            ['TAR-005-02', 'ACT-2026-005', 'IUA', 'u-rivas', 'Programa de entrevistas académicas.', 6, 'En curso', 35, 'A la espera de la autorización central.'],

            ['TAR-006-01', 'ACT-2026-006', 'ESGA', 'u-almada', 'Programa de la jornada y de los talleres.', 9, 'En curso', 45, ''],
            ['TAR-006-02', 'ACT-2026-006', 'FACFA', 'u-herrera', 'Confirmación de docentes participantes.', 8, 'En curso', 30, ''],

            ['TAR-007-01', 'ACT-2026-007', 'CIIFA', 'u-molina', 'Confirmación operativa del vuelo ficticio.', -2, 'Pendiente', 40, 'El cupo sigue sin cerrar.'],
            ['TAR-007-02', 'ACT-2026-007', 'FACFA', 'u-herrera', 'Recepción académica en la sede de destino.', -5, 'Finalizada', 100, 'Cerrada aunque su fecha ya pasó.'],

            ['TAR-008-01', 'ACT-2026-008', 'CIIFA', 'u-molina', 'Plan de ruta de la inspección.', 9, 'Pendiente', 0, ''],

            ['TAR-009-01', 'ACT-2026-009', 'IUA', 'u-rivas', 'Carga de legajos de la sede norte.', -8, 'En curso', 60, 'Falta la constancia de carga.'],
            ['TAR-009-02', 'ACT-2026-009', 'EAM', 'u-benitez', 'Revisión cruzada de la documentación.', -3, 'Pendiente', 10, ''],

            ['TAR-010-01', 'ACT-2026-010', 'EAM', 'u-benitez', 'Pedido de material de instrucción.', -4, 'Bloqueada', 35, 'Bloqueada: falta la confirmación del material solicitado.'],

            ['TAR-011-01', 'ACT-2026-011', 'FACFA', 'u-herrera', 'Novedades académicas del Litoral.', 1, 'En curso', 50, ''],
            ['TAR-011-02', 'ACT-2026-011', 'EAM', 'u-benitez', 'Informe logístico mensual.', -1, 'Bloqueada', 20, 'Bloqueada: falta un dato de depósito.'],
            ['TAR-011-03', 'ACT-2026-011', 'ESGA', 'u-almada', 'Novedades de capacitación austral.', 10, 'Pendiente', 0, ''],
            ['TAR-011-04', 'ACT-2026-011', 'IUA', 'u-rivas', 'Parte de situación del mes.', 0, 'En curso', 60, ''],
            ['TAR-011-05', 'ACT-2026-011', 'CIIFA', 'u-molina', 'Parte de traslados del mes anterior.', -8, 'Finalizada', 100, ''],

            ['TAR-012-01', 'ACT-2026-012', 'FACFA', 'u-herrera', 'Novedades de instrucción del Litoral.', -2, 'Finalizada', 100, ''],
            ['TAR-012-02', 'ACT-2026-012', 'EAM', 'u-benitez', 'Parte logístico del organismo.', -1, 'En curso', 80, ''],
            ['TAR-012-03', 'ACT-2026-012', 'ESGA', 'u-almada', 'Novedades de adiestramiento.', 1, 'Pendiente', 0, ''],
            ['TAR-012-04', 'ACT-2026-012', 'IUA', 'u-rivas', 'Parte de situación del organismo.', -1, 'En curso', 40, ''],
            ['TAR-012-05', 'ACT-2026-012', 'CIIFA', 'u-molina', 'Parte de movimientos del período.', -1, 'Pendiente', 0, ''],

            ['TAR-013-01', 'ACT-2026-013', 'IUA', 'u-rivas', 'Versión consolidada del calendario.', 2, 'En curso', 65, ''],
            ['TAR-013-02', 'ACT-2026-013', 'IUA', 'u-rivas', 'Compatibilización con el calendario central.', 4, 'Pendiente', 0, ''],

            ['TAR-014-01', 'ACT-2026-014', 'FACFA', 'u-herrera', 'Guion del acto y lista de oradores.', 12, 'En curso', 55, ''],
            ['TAR-014-02', 'ACT-2026-014', 'ESGA', 'u-almada', 'Apoyo técnico de la sede austral.', 16, 'Pendiente', 0, ''],
            ['TAR-014-03', 'ACT-2026-014', 'CIIFA', 'u-molina', 'Esquema de traslados de autoridades.', 10, 'En curso', 25, ''],

            ['TAR-015-01', 'ACT-2026-015', 'FACFA', 'u-herrera', 'Padrón ficticio de egresados.', 35, 'Pendiente', 0, ''],
            ['TAR-015-02', 'ACT-2026-015', 'ESGA', 'u-almada', 'Protocolo de la sede que recibe el acto.', 30, 'Pendiente', 0, ''],

            ['TAR-016-01', 'ACT-2026-016', 'ESGA', 'u-almada', 'Relatoría de la jornada.', -20, 'Finalizada', 100, ''],
            ['TAR-016-02', 'ACT-2026-016', 'ESGA', 'u-almada', 'Recomendaciones para el próximo ciclo.', -18, 'Finalizada', 100, ''],
        ];

        $tareas = [];
        foreach ($filas as $fila) {
            $estado = $fila[6];
            $avance = $estado === 'Finalizada' ? 100 : $fila[7];
            $limite = fecha_desde_hoy($fila[5]);
            $tareas[] = [
                'id' => $fila[0],
                'actividad_id' => $fila[1],
                'instituto_id' => $fila[2],
                'usuario_id' => $fila[3],
                'descripcion' => $fila[4],
                'fecha_inicio' => fecha_desde_hoy($fila[5] - 21),
                'fecha_limite' => $limite,
                'fecha_vencimiento_original' => $limite,
                'estado' => $estado,
                'avance' => $avance,
                'observaciones' => $fila[8],
                'reprogramaciones' => [],
                'fecha_cumplimiento' => null,
                'cumplida_dentro_plazo_original' => null,
            ];
        }

        return $tareas;
    }

    public function requerimientos(): array
    {
        $filas = [
            ['REQ-001', 'ACT-2026-009', 'TAR-009-01', 'Necesidad de recursos', 'Hace falta un lote ficticio de formularios para completar la carga de legajos.', 'IUA', 'DIGEDUC', 'u-quelvo', 'Pendiente', -4, ''],
            ['REQ-002', 'ACT-2026-005', 'TAR-005-02', 'Solicitud a la autoridad central', 'Se solicita autorización central para el desplazamiento de la comisión de revisión.', 'IUA', 'DIGEDUC', 'u-quelvo', 'Pendiente', -1, ''],
            ['REQ-003', 'ACT-2026-004', 'TAR-004-01', 'Requerimiento a otro instituto', 'Se pide a Estudios Logísticos el cuadro de aulas disponibles para la comisión de enlace.', 'CIIFA', 'EAM', 'u-benitez', 'Pendiente', -2, ''],
            ['REQ-004', 'ACT-2026-010', 'TAR-010-01', 'Impedimento', 'La tarea está bloqueada porque no se confirmó la disponibilidad del material de instrucción.', 'EAM', 'DIGEDUC', 'u-quelvo', 'Pendiente', -3, 'Sin fecha de entrega confirmada.'],
            ['REQ-005', 'ACT-2026-001', 'TAR-001-03', 'Necesidad de recursos', 'Sonido y protocolo para el acto conmemorativo.', 'ESGA', 'DIGEDUC', 'u-quelvo', 'Resuelto', -30, 'Cubierto con medios de la sede central.'],
            ['REQ-006', 'ACT-2026-014', 'TAR-014-01', 'Solicitud a la autoridad central', 'Confirmación del esquema de oradores de la apertura.', 'FACFA', 'DIGEDUC', 'u-quelvo', 'Resuelto', -6, 'Esquema confirmado.'],
            ['REQ-007', 'ACT-2026-007', 'TAR-007-02', 'Impedimento', 'La sede de destino no tenía confirmada la recepción. Luego se resolvió y la tarea se cerró.', 'FACFA', 'DIGEDUC', 'u-quelvo', 'Resuelto', -8, 'Recepción confirmada.'],
            ['REQ-008', 'ACT-2026-006', 'TAR-006-01', 'Requerimiento a otro instituto', 'Se solicita al Instituto del Litoral el listado de docentes que participarán en la jornada.', 'ESGA', 'FACFA', 'u-herrera', 'En atención', -2, 'El listado está en preparación.'],
            ['REQ-009', 'ACT-2026-003', 'TAR-003-02', 'Necesidad de recursos', 'Se necesita material de trabajo impreso para la mesa de revisión de planes.', 'EAM', 'DIGEDUC', 'u-quelvo', 'En atención', -1, ''],
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
            ['AUD-001', 'u-herrera', 'FACFA', 'FACFA', -1, '09:40', 'Tarea TAR-014-01', 'Porcentaje de avance', '40 %', '55 %'],
            ['AUD-002', 'u-benitez', 'EAM', 'EAM', -2, '16:05', 'Tarea TAR-010-01', 'Estado', 'En curso', 'Bloqueada'],
            ['AUD-003', 'u-quelvo', 'DIGEDUC', 'FACFA', -4, '11:10', 'Requerimiento REQ-006', 'Estado de resolución', 'Pendiente', 'Resuelto'],
            ['AUD-004', 'u-rivas', 'IUA', 'IUA', -1, '08:15', 'Tarea TAR-009-01', 'Observaciones', '—', 'Falta la constancia de carga.'],
            ['AUD-005', 'u-molina', 'CIIFA', 'CIIFA', -1, '18:22', 'Tarea TAR-007-01', 'Porcentaje de avance', '20 %', '40 %'],
            ['AUD-006', 'u-almada', 'ESGA', 'ESGA', -18, '13:00', 'Tarea TAR-016-01', 'Estado', 'En curso', 'Finalizada'],
            ['AUD-007', 'u-quelvo', 'DIGEDUC', 'EAM', 0, '08:05', 'Tarea TAR-012-02', 'Porcentaje de avance', '70 %', '80 %'],
            ['AUD-008', 'u-benitez', 'EAM', 'EAM', -3, '10:12', 'Requerimiento REQ-004', 'Alta de requerimiento', '—', 'Impedimento registrado'],
            ['AUD-009', 'u-molina', 'CIIFA', 'CIIFA', -6, '12:40', 'Tarea TAR-011-05', 'Estado', 'En curso', 'Finalizada'],
            ['AUD-010', 'u-herrera', 'FACFA', 'FACFA', -2, '15:18', 'Requerimiento REQ-008', 'Estado de resolución', 'Pendiente', 'En atención'],
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
