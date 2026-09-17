<?php
// ──────────────────────────────────────────────
//  models/ExcusaModel.php — Modelo de Excusas Médicas
// ──────────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';

class ExcusaModel {
    //esta funcion la estoy utilizando para agregar columnas y modificarlas en las tablas de excusas directamente en la base de datos 
    //si algo ya existe lo ignora y sigue gi
    private static function asegurarColumnas($conexion): void {
    try { $conexion->exec("ALTER TABLE excusa MODIFY COLUMN observacion VARCHAR(500) NULL"); } catch (Exception $e) {}
    try { $conexion->exec("ALTER TABLE excusa ADD COLUMN fecha_inicio DATE NULL AFTER observacion"); } catch (Exception $e) {}
    try { $conexion->exec("ALTER TABLE excusa ADD COLUMN fecha_fin DATE NULL AFTER fecha_inicio"); } catch (Exception $e) {}
    try { $conexion->exec("ALTER TABLE excusa ADD COLUMN fk_aprendiz INT NULL AFTER fk_ingreso"); } catch (Exception $e) {}
    // ...
}

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
                //con esto garantizo que las columnas de la tabla existan si no existen llamo la funcion para crear las que no 
                 self::asegurarColumnas($conexion);  //aqui digo que la funcion vive en esa misma clase 
                $sql = "SELECT e.id_excusa, e.documento, e.observacion, e.fecha_inicio, e.fecha_fin, e.estado,
                 CONCAT(u.nombre, ' ', u.apellido) AS aprendiz, u.identificacion AS documento_aprendiz,
                 a.fk_ficha AS numero_ficha
                 FROM excusa e
                 JOIN aprendiz a ON e.fk_aprendiz = a.id_aprendiz
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
                     'fecha_inicio' => $row['fecha_inicio'],
                     'fecha_fin'    => $row['fecha_fin'],
                     'archivo'      => $row['documento'] ?? '',
                     'estado'       => $row['estado'] ?? 'Pendiente',
                     'created_at'   => $row['fecha_inicio']
                 ];
                }
            }
        } catch (Exception $e) {
            // Devuelve arreglo vacío si falla
        }

        return $excusas;
    }
}
