<?php
// ──────────────────────────────────────────────
//  models/HorarioModel.php — Modelo de Fichas y Horarios
// ──────────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';

class HorarioModel {

    /**
     * Obtiene el listado de fichas registradas en la base de datos
     */
    public static function obtenerTodasFichas(): array {
        $fichas = [];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $sql = "SELECT f.id_ficha, f.nombre_programa, f.jornada,
                               CONCAT(u.nombre, ' ', u.apellido) AS instructor,
                               (SELECT COUNT(*) FROM aprendiz a WHERE a.fk_ficha = f.id_ficha) AS total_aprendices
                        FROM ficha f
                        LEFT JOIN usuario u ON f.fk_usuario = u.id_usuario
                        ORDER BY f.id_ficha DESC";

                $stmt = $conexion->query($sql);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $fichas[] = [
                        'id'               => $row['id_ficha'],
                        'numero_ficha'     => $row['id_ficha'],
                        'programa'         => $row['nombre_programa'],
                        'jornada'          => $row['jornada'],
                        'instructor'       => !empty(trim($row['instructor'])) ? $row['instructor'] : 'Por asignar',
                        'total_aprendices' => (int) $row['total_aprendices'],
                        'estado'           => 'Activo'
                    ];
                }
            }
        } catch (Exception $e) {
            // Devuelve arreglo vacío si falla
        }

        return $fichas;
    }
}
