<?php
// ──────────────────────────────────────────────
//  models/ExcusaModel.php — Modelo de Excusas Médicas
// ──────────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';

class ExcusaModel {

    /**
     * Obtiene el listado de excusas médicas registradas
     */
    public static function obtenerTodas(): array {
        $excusas = [];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $sql = "SELECT e.id_excusa, e.documento, e.observacion, e.estado,
                               i.fecha_registro,
                               CONCAT(u.nombre, ' ', u.apellido) AS aprendiz, u.identificacion AS documento_aprendiz,
                               a.fk_ficha AS numero_ficha
                        FROM excusa e
                        JOIN ingresos i ON e.fk_ingreso = i.id_ingresos
                        JOIN aprendiz a ON i.fk_aprendiz = a.id_aprendiz
                        JOIN usuario u ON a.fk_usuario = u.id_usuario
                        ORDER BY e.id_excusa DESC";

                $stmt = $conexion->query($sql);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $excusas[] = [
                        'id'           => $row['id_excusa'],
                        'aprendiz'     => !empty(trim($row['aprendiz'])) ? $row['aprendiz'] : $row['documento_aprendiz'],
                        'documento'    => $row['documento_aprendiz'],
                        'numero_ficha' => $row['numero_ficha'] ?? 'N/A',
                        'motivo'       => $row['observacion'] ?? 'Excusa médica',
                        'fecha_inicio' => $row['fecha_registro'],
                        'fecha_fin'    => $row['fecha_registro'],
                        'archivo'      => $row['documento'] ?? '',
                        'estado'       => $row['estado'] ?? 'Pendiente',
                        'created_at'   => $row['fecha_registro']
                    ];
                }
            }
        } catch (Exception $e) {
            // Devuelve arreglo vacío si falla
        }

        return $excusas;
    }
}
