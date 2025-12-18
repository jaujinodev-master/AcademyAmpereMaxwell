<?php
/**
 * AcademicHelper.php
 * Clase auxiliar para operaciones académicas comunes
 */

class AcademicHelper {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * Obtener listado de alumnos de un curso
     * @param int $id_curso
     * @return array
     */
    public function getAlumnosCurso($id_curso) {
        $sql = "SELECT 
                    u.id_usuario, 
                    u.nombres, 
                    u.apellidos, 
                    u.foto_perfil,
                    ic.id_inscripcion 
                FROM inscripciones_curso ic
                INNER JOIN matriculas m ON ic.id_matricula = m.id_matricula
                INNER JOIN usuarios u ON m.id_alumno = u.id_usuario
                WHERE ic.id_curso = :curso AND ic.estado = 'activo'
                ORDER BY u.apellidos ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':curso' => $id_curso]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener calificaciones existentes de un curso
     * @param int $id_curso
     * @return array Agrupado por id_inscripcion
     */
    public function getCalificacionesCurso($id_curso) {
        $sql = "SELECT 
                    c.id_calificacion,
                    c.id_inscripcion,
                    c.tipo_evaluacion,
                    c.descripcion,
                    c.nota,
                    c.fecha_evaluacion
                FROM calificaciones c
                INNER JOIN inscripciones_curso ic ON c.id_inscripcion = ic.id_inscripcion
                WHERE ic.id_curso = :curso
                ORDER BY c.fecha_evaluacion ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':curso' => $id_curso]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar por inscripción para fácil acceso en frontend
        $result = [];
        foreach ($rows as $row) {
            $result[$row['id_inscripcion']][] = $row;
        }
        return $result;
    }

    /**
     * Obtener todas las calificaciones de un alumno
     * @param int $id_alumno
     * @return array Agrupado por curso
     */
    public function getNotasAlumno($id_alumno) {
        $sql = "SELECT 
                    c.nombre_curso,
                    ca.nombre_ciclo,
                    cal.tipo_evaluacion,
                    cal.descripcion,
                    cal.nota,
                    cal.fecha_evaluacion
                FROM calificaciones cal
                INNER JOIN inscripciones_curso ic ON cal.id_inscripcion = ic.id_inscripcion
                INNER JOIN matrix m ON ic.id_matricula = m.id_matricula -- Error here, table is matriculas not matrix. Wait, let me check schema.
                -- Correction: The schema uses `matriculas`.
                INNER JOIN matriculas mat ON ic.id_matricula = mat.id_matricula
                INNER JOIN cursos c ON ic.id_curso = c.id_curso
                INNER JOIN ciclos_academicos ca ON c.id_ciclo = ca.id_ciclo
                WHERE mat.id_alumno = :alumno
                ORDER BY ca.fecha_inicio DESC, c.nombre_curso, cal.fecha_evaluacion";
        
        // Let's rewrite the query carefully to match schema
        $sql = "SELECT 
                    c.id_curso,
                    c.nombre_curso,
                    c.codigo_curso,
                    ca.nombre_ciclo,
                    cal.tipo_evaluacion,
                    cal.descripcion,
                    cal.nota,
                    cal.fecha_evaluacion
                FROM calificaciones cal
                INNER JOIN inscripciones_curso ic ON cal.id_inscripcion = ic.id_inscripcion
                INNER JOIN matriculas m ON ic.id_matricula = m.id_matricula
                INNER JOIN cursos c ON ic.id_curso = c.id_curso
                INNER JOIN ciclos_academicos ca ON c.id_ciclo = ca.id_ciclo
                WHERE m.id_alumno = :alumno
                ORDER BY ca.fecha_inicio DESC, c.nombre_curso, cal.fecha_evaluacion";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':alumno' => $id_alumno]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar por curso
        $result = [];
        foreach ($rows as $row) {
            $cursoKey = $row['nombre_curso'] . ' (' . $row['nombre_ciclo'] . ')';
            $result[$cursoKey]['info'] = [
                'nombre' => $row['nombre_curso'],
                'codigo' => $row['codigo_curso'],
                'ciclo' => $row['nombre_ciclo']
            ];
            $result[$cursoKey]['notas'][] = $row;
        }
        return $result;
    }

    /**
     * Obtener asistencia de un curso y fecha
     */
    public function getAsistenciaFecha($id_curso, $fecha) {
        $sql = "SELECT 
                   ic.id_inscripcion,
                   a.estado,
                   a.observaciones
                FROM inscripciones_curso ic
                LEFT JOIN asistencias a ON ic.id_inscripcion = a.id_inscripcion AND a.fecha = :fecha
                WHERE ic.id_curso = :curso AND ic.estado = 'activo'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':curso' => $id_curso, ':fecha' => $fecha]);
        
        $result = [];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['id_inscripcion']] = $row;
        }
        return $result;
    }

    /**
     * Obtener resumen de asistencia de un alumno
     */
    public function getAsistenciaAlumno($id_alumno) {
        $sql = "SELECT 
                    c.nombre_curso,
                    count(*) as total_clases,
                    sum(case when a.estado = 'presente' then 1 else 0 end) as presentes,
                    sum(case when a.estado = 'tardanza' then 1 else 0 end) as tardanzas,
                    sum(case when a.estado = 'ausente' then 1 else 0 end) as faltas,
                    sum(case when a.estado = 'justificado' then 1 else 0 end) as justificados
                FROM asistencias a
                INNER JOIN inscripciones_curso ic ON a.id_inscripcion = ic.id_inscripcion
                INNER JOIN matriculas m ON ic.id_matricula = m.id_matricula
                INNER JOIN cursos c ON ic.id_curso = c.id_curso
                WHERE m.id_alumno = :alumno
                GROUP BY c.id_curso, c.nombre_curso";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':alumno' => $id_alumno]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener horario del alumno
     */
    public function getHorarioAlumno($id_alumno) {
        $sql = "SELECT 
                    h.dia_semana,
                    h.hora_inicio,
                    h.hora_fin,
                    h.aula,
                    c.nombre_curso,
                    u.nombres as nom_prof,
                    u.apellidos as ape_prof
                FROM horarios h
                INNER JOIN cursos c ON h.id_curso = c.id_curso
                INNER JOIN inscripciones_curso ic ON c.id_curso = ic.id_curso
                INNER JOIN matriculas m ON ic.id_matricula = m.id_matricula
                LEFT JOIN profesor_curso pc ON c.id_curso = pc.id_curso AND pc.estado = 'activo'
                LEFT JOIN usuarios u ON pc.id_profesor = u.id_usuario
                WHERE m.id_alumno = :alumno AND h.estado = 'activo'
                ORDER BY h.hora_inicio ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':alumno' => $id_alumno]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
