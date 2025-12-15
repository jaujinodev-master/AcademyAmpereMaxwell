-- ============================================
-- Base de Datos: Academia Ampere Maxwell
-- Sistema de Gestión Educativa
-- ============================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS academia_ampere_maxwell
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE academia_ampere_maxwell;

-- ============================================
-- TABLA: roles
-- Almacena los diferentes roles de usuario
-- ============================================
CREATE TABLE IF NOT EXISTS roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    permisos JSON,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: usuarios
-- Almacena información de todos los usuarios
-- ============================================
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    dni VARCHAR(8) UNIQUE,
    telefono VARCHAR(15),
    direccion TEXT,
    fecha_nacimiento DATE,
    foto_perfil VARCHAR(255),
    estado ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo',
    ultimo_acceso TIMESTAMP NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE RESTRICT,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_dni (dni),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: ciclos_academicos
-- Almacena los ciclos académicos
-- ============================================
CREATE TABLE IF NOT EXISTS ciclos_academicos (
    id_ciclo INT AUTO_INCREMENT PRIMARY KEY,
    nombre_ciclo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    modalidad ENUM('presencial', 'virtual', 'hibrido') NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    costo DECIMAL(10, 2),
    cupos_disponibles INT DEFAULT 0,
    estado ENUM('activo', 'inactivo', 'finalizado') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_estado (estado),
    INDEX idx_fechas (fecha_inicio, fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: cursos
-- Almacena información de los cursos
-- ============================================
CREATE TABLE IF NOT EXISTS cursos (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    id_ciclo INT NOT NULL,
    nombre_curso VARCHAR(100) NOT NULL,
    descripcion TEXT,
    codigo_curso VARCHAR(20) UNIQUE,
    creditos INT DEFAULT 1,
    horas_semanales INT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_ciclo) REFERENCES ciclos_academicos(id_ciclo) ON DELETE CASCADE,
    INDEX idx_codigo (codigo_curso),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: profesor_curso
-- Relación entre profesores y cursos
-- ============================================
CREATE TABLE IF NOT EXISTS profesor_curso (
    id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
    id_profesor INT NOT NULL,
    id_curso INT NOT NULL,
    fecha_asignacion DATE NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_profesor) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_curso) REFERENCES cursos(id_curso) ON DELETE CASCADE,
    UNIQUE KEY unique_profesor_curso (id_profesor, id_curso),
    INDEX idx_profesor (id_profesor),
    INDEX idx_curso (id_curso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: matriculas
-- Almacena las matrículas de alumnos
-- ============================================
CREATE TABLE IF NOT EXISTS matriculas (
    id_matricula INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT NOT NULL,
    id_ciclo INT NOT NULL,
    fecha_matricula DATE NOT NULL,
    monto_pagado DECIMAL(10, 2),
    estado_pago ENUM('pendiente', 'pagado', 'parcial') DEFAULT 'pendiente',
    estado_matricula ENUM('activo', 'retirado', 'finalizado') DEFAULT 'activo',
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_alumno) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_ciclo) REFERENCES ciclos_academicos(id_ciclo) ON DELETE CASCADE,
    UNIQUE KEY unique_alumno_ciclo (id_alumno, id_ciclo),
    INDEX idx_alumno (id_alumno),
    INDEX idx_ciclo (id_ciclo),
    INDEX idx_estado (estado_matricula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: inscripciones_curso
-- Inscripciones de alumnos a cursos específicos
-- ============================================
CREATE TABLE IF NOT EXISTS inscripciones_curso (
    id_inscripcion INT AUTO_INCREMENT PRIMARY KEY,
    id_matricula INT NOT NULL,
    id_curso INT NOT NULL,
    fecha_inscripcion DATE NOT NULL,
    estado ENUM('activo', 'retirado') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_matricula) REFERENCES matriculas(id_matricula) ON DELETE CASCADE,
    FOREIGN KEY (id_curso) REFERENCES cursos(id_curso) ON DELETE CASCADE,
    UNIQUE KEY unique_matricula_curso (id_matricula, id_curso),
    INDEX idx_matricula (id_matricula),
    INDEX idx_curso (id_curso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: calificaciones
-- Almacena las calificaciones de los alumnos
-- ============================================
CREATE TABLE IF NOT EXISTS calificaciones (
    id_calificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_inscripcion INT NOT NULL,
    tipo_evaluacion ENUM('examen', 'practica', 'tarea', 'proyecto', 'participacion') NOT NULL,
    descripcion VARCHAR(200),
    nota DECIMAL(5, 2) NOT NULL,
    peso DECIMAL(5, 2) DEFAULT 1.00,
    fecha_evaluacion DATE NOT NULL,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_inscripcion) REFERENCES inscripciones_curso(id_inscripcion) ON DELETE CASCADE,
    INDEX idx_inscripcion (id_inscripcion),
    INDEX idx_tipo (tipo_evaluacion),
    CHECK (nota >= 0 AND nota <= 20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: asistencias
-- Registro de asistencias
-- ============================================
CREATE TABLE IF NOT EXISTS asistencias (
    id_asistencia INT AUTO_INCREMENT PRIMARY KEY,
    id_inscripcion INT NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('presente', 'ausente', 'tardanza', 'justificado') NOT NULL,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_inscripcion) REFERENCES inscripciones_curso(id_inscripcion) ON DELETE CASCADE,
    UNIQUE KEY unique_inscripcion_fecha (id_inscripcion, fecha),
    INDEX idx_inscripcion (id_inscripcion),
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: materiales_educativos
-- Almacena materiales subidos por profesores
-- ============================================
CREATE TABLE IF NOT EXISTS materiales_educativos (
    id_material INT AUTO_INCREMENT PRIMARY KEY,
    id_curso INT NOT NULL,
    id_profesor INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    tipo_material ENUM('pdf', 'video', 'presentacion', 'documento', 'enlace', 'otro') NOT NULL,
    ruta_archivo VARCHAR(255),
    url_enlace VARCHAR(500),
    tamanio_archivo BIGINT,
    fecha_publicacion DATE NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_curso) REFERENCES cursos(id_curso) ON DELETE CASCADE,
    FOREIGN KEY (id_profesor) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_curso (id_curso),
    INDEX idx_profesor (id_profesor),
    INDEX idx_tipo (tipo_material)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: tareas
-- Almacena tareas asignadas
-- ============================================
CREATE TABLE IF NOT EXISTS tareas (
    id_tarea INT AUTO_INCREMENT PRIMARY KEY,
    id_curso INT NOT NULL,
    id_profesor INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    fecha_asignacion DATE NOT NULL,
    fecha_entrega DATE NOT NULL,
    puntaje_maximo DECIMAL(5, 2) DEFAULT 20.00,
    estado ENUM('activo', 'cerrado') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_curso) REFERENCES cursos(id_curso) ON DELETE CASCADE,
    FOREIGN KEY (id_profesor) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_curso (id_curso),
    INDEX idx_fecha_entrega (fecha_entrega)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: entregas_tareas
-- Almacena las entregas de tareas de alumnos
-- ============================================
CREATE TABLE IF NOT EXISTS entregas_tareas (
    id_entrega INT AUTO_INCREMENT PRIMARY KEY,
    id_tarea INT NOT NULL,
    id_alumno INT NOT NULL,
    ruta_archivo VARCHAR(255),
    comentario TEXT,
    fecha_entrega TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    calificacion DECIMAL(5, 2),
    retroalimentacion TEXT,
    estado ENUM('entregado', 'calificado', 'retrasado') DEFAULT 'entregado',
    FOREIGN KEY (id_tarea) REFERENCES tareas(id_tarea) ON DELETE CASCADE,
    FOREIGN KEY (id_alumno) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    UNIQUE KEY unique_tarea_alumno (id_tarea, id_alumno),
    INDEX idx_tarea (id_tarea),
    INDEX idx_alumno (id_alumno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: horarios
-- Almacena los horarios de clases
-- ============================================
CREATE TABLE IF NOT EXISTS horarios (
    id_horario INT AUTO_INCREMENT PRIMARY KEY,
    id_curso INT NOT NULL,
    dia_semana ENUM('lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    aula VARCHAR(50),
    modalidad ENUM('presencial', 'virtual') NOT NULL,
    enlace_virtual VARCHAR(500),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_curso) REFERENCES cursos(id_curso) ON DELETE CASCADE,
    INDEX idx_curso (id_curso),
    INDEX idx_dia (dia_semana)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERTAR DATOS INICIALES
-- ============================================

-- Insertar roles
INSERT INTO roles (nombre_rol, descripcion, permisos) VALUES
('Administrador', 'Acceso total al sistema', '{"all": true}'),
('Profesor', 'Gestión de cursos, calificaciones y materiales', '{"cursos": true, "calificaciones": true, "materiales": true, "asistencias": true}'),
('Alumno', 'Acceso a cursos, materiales y calificaciones', '{"ver_cursos": true, "ver_materiales": true, "ver_calificaciones": true, "entregar_tareas": true}'),
('Servicios', 'Personal de servicios y soporte', '{"soporte": true}');

-- Insertar usuario administrador por defecto
-- Usuario: admin | Contraseña: admin123
-- IMPORTANTE: Este hash fue generado con password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO usuarios (id_rol, username, email, password_hash, nombres, apellidos, dni, estado) VALUES
(1, 'admin', 'admin@ampere-maxwell.edu.pe', '$2y$10$xQR14p6530YJabLNLbfCVe3yFistXIb2/RuY/JCF11hgqhvQvD11u', 'Administrador', 'Sistema', '00000000', 'activo');

-- Insertar un ciclo académico de ejemplo
INSERT INTO ciclos_academicos (nombre_ciclo, descripcion, modalidad, fecha_inicio, fecha_fin, costo, cupos_disponibles, estado) VALUES
('Ciclo Verano 2025', 'Ciclo intensivo de verano para preparación universitaria', 'hibrido', '2025-01-15', '2025-03-31', 500.00, 50, 'activo');

-- ============================================
-- VISTAS ÚTILES
-- ============================================

-- Vista: Resumen de alumnos por ciclo
CREATE OR REPLACE VIEW vista_alumnos_ciclo AS
SELECT 
    c.id_ciclo,
    c.nombre_ciclo,
    COUNT(m.id_alumno) as total_alumnos,
    SUM(CASE WHEN m.estado_matricula = 'activo' THEN 1 ELSE 0 END) as alumnos_activos,
    SUM(CASE WHEN m.estado_pago = 'pagado' THEN 1 ELSE 0 END) as pagos_completos
FROM ciclos_academicos c
LEFT JOIN matriculas m ON c.id_ciclo = m.id_ciclo
GROUP BY c.id_ciclo, c.nombre_ciclo;

-- Vista: Promedio de calificaciones por alumno
CREATE OR REPLACE VIEW vista_promedios_alumnos AS
SELECT 
    u.id_usuario,
    u.nombres,
    u.apellidos,
    cu.id_curso,
    cu.nombre_curso,
    AVG(cal.nota) as promedio
FROM usuarios u
INNER JOIN matriculas m ON u.id_usuario = m.id_alumno
INNER JOIN inscripciones_curso ic ON m.id_matricula = ic.id_matricula
INNER JOIN cursos cu ON ic.id_curso = cu.id_curso
LEFT JOIN calificaciones cal ON ic.id_inscripcion = cal.id_inscripcion
WHERE u.id_rol = 3
GROUP BY u.id_usuario, u.nombres, u.apellidos, cu.id_curso, cu.nombre_curso;

-- ============================================
-- PROCEDIMIENTOS ALMACENADOS
-- ============================================

DELIMITER //

-- Procedimiento: Calcular promedio final de un alumno en un curso
CREATE PROCEDURE sp_calcular_promedio_curso(
    IN p_id_inscripcion INT,
    OUT p_promedio DECIMAL(5,2)
)
BEGIN
    SELECT AVG(nota) INTO p_promedio
    FROM calificaciones
    WHERE id_inscripcion = p_id_inscripcion;
END //

-- Procedimiento: Obtener porcentaje de asistencia
CREATE PROCEDURE sp_porcentaje_asistencia(
    IN p_id_inscripcion INT,
    OUT p_porcentaje DECIMAL(5,2)
)
BEGIN
    DECLARE total_clases INT;
    DECLARE asistencias_presentes INT;
    
    SELECT COUNT(*) INTO total_clases
    FROM asistencias
    WHERE id_inscripcion = p_id_inscripcion;
    
    SELECT COUNT(*) INTO asistencias_presentes
    FROM asistencias
    WHERE id_inscripcion = p_id_inscripcion 
    AND estado IN ('presente', 'tardanza');
    
    IF total_clases > 0 THEN
        SET p_porcentaje = (asistencias_presentes / total_clases) * 100;
    ELSE
        SET p_porcentaje = 0;
    END IF;
END //

DELIMITER ;

-- ============================================
-- FIN DEL SCRIPT
-- ============================================
