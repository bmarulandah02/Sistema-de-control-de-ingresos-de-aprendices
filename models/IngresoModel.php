<?php
// ──────────────────────────────────────────────
//  models/IngresoModel.php — Gestión de Asistencias e Ingresos
// ──────────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';

class IngresoModel {

    /**
     * Obtiene métricas del día actual (total ingresos, puntuales, retardos) filtrando opcionalmente por Ficha
     */
    public static function obtenerEstadisticasHoy(?int $idFicha = null): array {
        $stats = ['total' => 0, 'puntuales' => 0, 'retardos' => 0];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                if ($idFicha && $idFicha > 0) {
                    $sqlTotal = "SELECT COUNT(*) FROM ingresos i 
                                 JOIN aprendiz a ON i.fk_aprendiz = a.id_aprendiz 
                                 WHERE i.fecha_registro = CURDATE() AND a.fk_ficha = :ficha";
                    $stmt = $conexion->prepare($sqlTotal);
                    $stmt->execute([':ficha' => $idFicha]);
                    $stats['total'] = (int) $stmt->fetchColumn();

                    $sqlPuntual = "SELECT COUNT(*) FROM ingresos i 
                                   JOIN aprendiz a ON i.fk_aprendiz = a.id_aprendiz 
                                   WHERE i.fecha_registro = CURDATE() AND i.estado_asistencia = 'Puntual' AND a.fk_ficha = :ficha";
                    $stmt = $conexion->prepare($sqlPuntual);
                    $stmt->execute([':ficha' => $idFicha]);
                    $stats['puntuales'] = (int) $stmt->fetchColumn();

                    $sqlRetardos = "SELECT COUNT(*) FROM ingresos i 
                                    JOIN aprendiz a ON i.fk_aprendiz = a.id_aprendiz 
                                    WHERE i.fecha_registro = CURDATE() AND i.estado_asistencia = 'Retardo' AND a.fk_ficha = :ficha";
                    $stmt = $conexion->prepare($sqlRetardos);
                    $stmt->execute([':ficha' => $idFicha]);
                    $stats['retardos'] = (int) $stmt->fetchColumn();
                } else {
                    $stats['total'] = (int) $conexion->query("SELECT COUNT(*) FROM ingresos WHERE fecha_registro = CURDATE()")->fetchColumn();
                    $stats['puntuales'] = (int) $conexion->query("SELECT COUNT(*) FROM ingresos WHERE fecha_registro = CURDATE() AND estado_asistencia = 'Puntual'")->fetchColumn();
                    $stats['retardos'] = (int) $conexion->query("SELECT COUNT(*) FROM ingresos WHERE fecha_registro = CURDATE() AND estado_asistencia = 'Retardo'")->fetchColumn();
                }
            }
        } catch (Exception $e) {
            // Silencioso
        }

        return $stats;
    }

    /**
     * Obtiene los últimos movimientos del día para el Dashboard filtrando opcionalmente por Ficha
     */
    public static function obtenerUltimosMovimientos(int $limite = 8, ?int $idFicha = null): array {
        $registros = [];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $where = "";
                if ($idFicha && $idFicha > 0) {
                    $where = "WHERE f.id_ficha = :idFicha";
                }

                $sql = "SELECT i.id_ingresos, i.fecha_registro, i.entrada, i.salida, i.estado_asistencia AS estado,
                               CONCAT(u.nombre, ' ', u.apellido) AS aprendiz, u.identificacion AS documento,
                               f.id_ficha AS numero_ficha, f.nombre_programa AS programa
                        FROM ingresos i
                        JOIN aprendiz a ON i.fk_aprendiz = a.id_aprendiz
                        JOIN usuario u ON a.fk_usuario = u.id_usuario
                        LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha
                        {$where}
                        ORDER BY i.entrada DESC
                        LIMIT :limite";

                $stmt = $conexion->prepare($sql);
                $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
                if ($idFicha && $idFicha > 0) {
                    $stmt->bindValue(':idFicha', $idFicha, PDO::PARAM_INT);
                }
                $stmt->execute();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $registros[] = [
                        'id'           => $row['id_ingresos'],
                        'aprendiz'     => !empty(trim($row['aprendiz'])) ? $row['aprendiz'] : $row['documento'],
                        'documento'    => $row['documento'],
                        'numero_ficha' => $row['numero_ficha'] ?? 'N/A',
                        'programa'     => $row['programa'] ?? 'Sin programa',
                        'fecha'        => $row['fecha_registro'],
                        'hora_entrada' => $row['entrada'] ? date('H:i:s', strtotime($row['entrada'])) : '—',
                        'hora_salida'  => ($row['salida'] && $row['salida'] !== '0000-00-00 00:00:00') ? date('H:i:s', strtotime($row['salida'])) : null,
                        'estado'       => $row['estado']
                    ];
                }
            }
        } catch (Exception $e) {
            // Devuelve arreglo vacío si falla
        }

        return $registros;
    }

    /**
     * Obtiene historial de asistencias con filtros dinámicos por rango de fecha y estado
     */
    public static function obtenerHistorialConFiltros(array $filtros): array {
        $registros = [];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $where = [];
                $params = [];

                if (!empty($filtros['fecha_inicio'])) {
                    $where[] = "i.fecha_registro >= :fecha_inicio";
                    $params[':fecha_inicio'] = $filtros['fecha_inicio'];
                }

                if (!empty($filtros['fecha_fin'])) {
                    $where[] = "i.fecha_registro <= :fecha_fin";
                    $params[':fecha_fin'] = $filtros['fecha_fin'];
                }

                if (!empty($filtros['estado'])) {
                    $where[] = "i.estado_asistencia = :estado";
                    $params[':estado'] = $filtros['estado'];
                }

                $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

                $sql = "SELECT i.id_ingresos, i.fecha_registro, i.entrada, i.salida, i.estado_asistencia AS estado,
                               CONCAT(u.nombre, ' ', u.apellido) AS aprendiz, u.identificacion AS documento,
                               f.id_ficha AS numero_ficha, f.nombre_programa AS programa
                        FROM ingresos i
                        JOIN aprendiz a ON i.fk_aprendiz = a.id_aprendiz
                        JOIN usuario u ON a.fk_usuario = u.id_usuario
                        LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha
                        {$whereSql}
                        ORDER BY i.fecha_registro DESC, i.entrada DESC";

                $stmt = $conexion->prepare($sql);
                $stmt->execute($params);

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $registros[] = [
                        'id'           => $row['id_ingresos'],
                        'fecha'        => $row['fecha_registro'],
                        'aprendiz'     => !empty(trim($row['aprendiz'])) ? $row['aprendiz'] : $row['documento'],
                        'documento'    => $row['documento'],
                        'numero_ficha' => $row['numero_ficha'] ?? 'N/A',
                        'programa'     => $row['programa'] ?? 'Sin programa',
                        'hora_entrada' => $row['entrada'] ? date('H:i:s', strtotime($row['entrada'])) : '—',
                        'hora_salida'  => ($row['salida'] && $row['salida'] !== '0000-00-00 00:00:00') ? date('H:i:s', strtotime($row['salida'])) : null,
                        'estado'       => $row['estado']
                    ];
                }
            }
        } catch (Exception $e) {
            // Devuelve arreglo vacío si falla
        }

        return $registros;
    }
}
