-- Esquema de destino para MySQL 8 o MariaDB 10.11.
-- Este prototipo no lo ejecuta. RepositorioFicticio devuelve los mismos campos.
-- Motor InnoDB y utf8mb4. No usa PostgreSQL.

CREATE TABLE institutos (
    id VARCHAR(16) NOT NULL,
    sigla VARCHAR(16) NOT NULL,
    nombre VARCHAR(160) NOT NULL,
    tipo ENUM('autoridad', 'instituto') NOT NULL,
    sede VARCHAR(120) NOT NULL,
    descripcion VARCHAR(500) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
    id VARCHAR(32) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    perfil ENUM('admin', 'responsable', 'consulta') NOT NULL,
    instituto_id VARCHAR(16) NOT NULL,
    cargo VARCHAR(120) NOT NULL,
    clave_hash VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_usuarios_instituto (instituto_id),
    CONSTRAINT fk_usuarios_instituto FOREIGN KEY (instituto_id) REFERENCES institutos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE actividades (
    id VARCHAR(20) NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    descripcion VARCHAR(1000) NOT NULL,
    tipo VARCHAR(80) NOT NULL,
    fecha DATE NOT NULL,
    responsable_id VARCHAR(32) NOT NULL,
    observaciones VARCHAR(1000) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY idx_actividades_fecha (fecha),
    CONSTRAINT fk_actividades_responsable FOREIGN KEY (responsable_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE actividad_instituto (
    actividad_id VARCHAR(20) NOT NULL,
    instituto_id VARCHAR(16) NOT NULL,
    PRIMARY KEY (actividad_id, instituto_id),
    CONSTRAINT fk_ai_actividad FOREIGN KEY (actividad_id) REFERENCES actividades (id),
    CONSTRAINT fk_ai_instituto FOREIGN KEY (instituto_id) REFERENCES institutos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tareas (
    id VARCHAR(20) NOT NULL,
    actividad_id VARCHAR(20) NOT NULL,
    instituto_id VARCHAR(16) NOT NULL,
    usuario_id VARCHAR(32) NOT NULL,
    descripcion VARCHAR(500) NOT NULL,
    fecha_limite DATE NOT NULL,
    estado ENUM('Pendiente', 'En curso', 'Bloqueada', 'Finalizada') NOT NULL,
    avance TINYINT UNSIGNED NOT NULL,
    observaciones VARCHAR(500) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY idx_tareas_actividad (actividad_id),
    KEY idx_tareas_fecha (fecha_limite),
    CONSTRAINT fk_tareas_actividad FOREIGN KEY (actividad_id) REFERENCES actividades (id),
    CONSTRAINT fk_tareas_instituto FOREIGN KEY (instituto_id) REFERENCES institutos (id),
    CONSTRAINT fk_tareas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE requerimientos (
    id VARCHAR(20) NOT NULL,
    actividad_id VARCHAR(20) NOT NULL,
    tarea_id VARCHAR(20) NULL,
    tipo VARCHAR(80) NOT NULL,
    descripcion VARCHAR(500) NOT NULL,
    instituto_solicitante VARCHAR(16) NOT NULL,
    instituto_destino VARCHAR(16) NOT NULL,
    responsable_id VARCHAR(32) NOT NULL,
    estado ENUM('Pendiente', 'En atención', 'Resuelto') NOT NULL,
    creado DATE NOT NULL,
    observaciones VARCHAR(500) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY idx_req_actividad (actividad_id),
    KEY idx_req_estado (estado),
    CONSTRAINT fk_req_actividad FOREIGN KEY (actividad_id) REFERENCES actividades (id),
    CONSTRAINT fk_req_tarea FOREIGN KEY (tarea_id) REFERENCES tareas (id),
    CONSTRAINT fk_req_solicitante FOREIGN KEY (instituto_solicitante) REFERENCES institutos (id),
    CONSTRAINT fk_req_destino FOREIGN KEY (instituto_destino) REFERENCES institutos (id),
    CONSTRAINT fk_req_responsable FOREIGN KEY (responsable_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- El usuario de la aplicación definitiva solo debería poder INSERT en esta tabla.
-- REVOKE UPDATE, DELETE ON seguimiento.auditoria FROM 'seguimiento_app'@'localhost';
CREATE TABLE auditoria (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id VARCHAR(32) NOT NULL,
    instituto_id VARCHAR(16) NOT NULL,
    ambito_id VARCHAR(16) NOT NULL,
    ocurrido_en DATETIME NOT NULL,
    entidad VARCHAR(80) NOT NULL,
    campo VARCHAR(80) NOT NULL,
    valor_anterior VARCHAR(500) NOT NULL,
    valor_nuevo VARCHAR(500) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_auditoria_cuando (ocurrido_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
