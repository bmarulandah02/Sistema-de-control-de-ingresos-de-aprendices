<?php
// ──────────────────────────────────────────────
//  models/IngresoModel.php — Gestión de Asistencias e Ingresos
// ──────────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';

class IngresoModel {

    /**
     * Obtiene métricas del día actual (total ingresos, puntuales, retardos) filtrando opcionalmente por Ficha o Instructor
     */
    public static function obtenerEstadisticasHoy(?int $idFicha = null, ?int $instructorId = null): array {
        $stats = ['total' => 0, 'puntuales' => 0, 'retardos' => 0];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $whereClause = "WHERE i.fecha_registro = CURDATE()";
                $params = [];

                if ($idFicha && $idFicha > 0) {
                    $whereClause .= " AND a.fk_ficha = :ficha";
                    $params[':ficha'] = $idFicha;
                } else if ($instructorId && $instructorId > 0) {
                    $whereClause .= " AND f.fk_usuario = :instructor";
                    $params[':instructor'] = $instructorId;
                }

                // Total ingresos hoy
                $sqlTotal = "SELECT COUNT(*) FROM ingresos i 
                             JOIN aprendiz a ON i.fk_aprendiz = a.id_aprendiz 
                             LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha 
                             {$whereClause}";
                $stmt = $conexion->prepare($sqlTotal);
                $stmt->execute($params);
                $stats['total'] = (int) $stmt->fetchColumn();

                // Puntuales hoy (coincidencia con 'Puntual%')
                $sqlPuntuales = "SELECT COUNT(*) FROM ingresos i 
                                 JOIN aprendiz a ON i.fk_aprendiz = a.id_aprendiz 
                                 LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha 
                                 {$whereClause} AND i.estado_asistencia LIKE 'Puntual%'";
                $stmt = $conexion->prepare($sqlPuntuales);
                $stmt->execute($params);
                $stats['puntuales'] = (int) $stmt->fetchColumn();

                // Retardos hoy (coincidencia con 'Retardo%')
                $sqlRetardos = "SELECT COUNT(*) FROM ingresos i 
                                JOIN aprendiz a ON i.fk_aprendiz = a.id_aprendiz 
                                LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha 
                                {$whereClause} AND i.estado_asistencia LIKE 'Retardo%'";
                $stmt = $conexion->prepare($sqlRetardos);
                $stmt->execute($params);
                $stats['retardos'] = (int) $stmt->fetchColumn();
            }
        } catch (Exception $e) {
            // Silencioso
        }

        return $stats;
    }

    /**
     * Obtiene los últimos movimientos del día para el Dashboard filtrando opcionalmente por Ficha o Instructor
     */
    public static function obtenerUltimosMovimientos(int $limite = 8, ?int $idFicha = null, ?int $instructorId = null): array {
        $registros = [];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $where = [];
                $params = [];

                if ($idFicha && $idFicha > 0) {
                    $where[] = "f.id_ficha = :idFicha";
                    $params[':idFicha'] = $idFicha;
                } else if ($instructorId && $instructorId > 0) {
                    $where[] = "f.fk_usuario = :instructorId";
                    $params[':instructorId'] = $instructorId;
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
                        ORDER BY i.entrada DESC
                        LIMIT :limite";

                $stmt = $conexion->prepare($sql);
                $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
                foreach ($params as $paramKey => $paramVal) {
                    $stmt->bindValue($paramKey, $paramVal, PDO::PARAM_INT);
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

                if (!empty($filtros['ficha_id'])) {
                    $where[] = "f.id_ficha = :ficha_id";
                    $params[':ficha_id'] = (int) $filtros['ficha_id'];
                }

                if (!empty($filtros['instructor_id'])) {
                    $where[] = "f.fk_usuario = :instructor_id";
                    $params[':instructor_id'] = (int) $filtros['instructor_id'];
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

    public function verificarIngreso($identificadorAprendiz,$fechaActual)
    {
        try{
            $mysql= new MySQL();
            $mysql->conectarBD();
            $conexion=$mysql->getConexion();
            if($conexion)
                {
                    $consulta="select id_ingresos,entrada,salida,estado_asistencia from ingresos where fk_aprendiz=:idA and fecha_registro=:FA limit 1";
                    $stmt=$conexion->prepare($consulta);
                    $stmt->bindParam(':idA',$identificadorAprendiz,PDO::PARAM_INT);
                    $stmt->bindParam(':FA',$fechaActual,PDO::PARAM_STR);
                    $stmt->execute();
                    return $stmt->fetch(PDO::FETCH_ASSOC);
                }
            
        }catch(PDOException $e)
        {
            error_log("Error en IngresoModel ". $e->getMessage());
            return false;

        }

    } 
    //funcion para insertar las filas al marcar una entrada 
    public function registrarEntrada($fechaActual,$horaActual,$estadoAsistencia,$identificadorAprendiz)
    {
        try{
            $mysql= new MySQL();
            $mysql->conectarBD();
            $conexion=$mysql->getConexion();
            if($conexion)
                {
                    // Asegurar que la columna salida permita valores NULOS al registrar únicamente la entrada
                    try {
                        $conexion->exec("ALTER TABLE ingresos MODIFY COLUMN salida DATETIME NULL DEFAULT NULL");
                    } catch (Exception $e) {}

                    $consulta="INSERT INTO ingresos (fecha_registro, entrada, salida, estado_asistencia, fk_aprendiz) VALUES (:FA, :HA, NULL, :EA, :IA)";
                    $stmt=$conexion->prepare($consulta);
                    $stmt->bindParam(':FA',$fechaActual,PDO::PARAM_STR);
                    $stmt->bindParam(':HA',$horaActual,PDO::PARAM_STR);
                    $stmt->bindParam(':EA',$estadoAsistencia,PDO::PARAM_STR);
                    $stmt->bindParam(':IA',$identificadorAprendiz,PDO::PARAM_INT);
                    return $stmt->execute();
                }
        }catch(PDOException $e)
        {
            error_log("Error al insertar la entrada ". $e->getMessage());
            return false;

        }
    }

    ////funcion para actualizar la fila de la tabla para marcar la salida 

    public function registrarSalida($id_ingreso,$horaActual,$estadoAsistencia)
    {
        try{
            $mysql= new MySQL();
            $mysql->conectarBD();
            $conexion=$mysql->getConexion();
            if($conexion)
                {
                    $consulta="update ingresos set salida=:HA,estado_asistencia=:EA where id_ingresos=:ID";
                    $stmt=$conexion->prepare($consulta);
                    $stmt->bindParam(':HA',$horaActual,PDO::PARAM_STR);
                    $stmt->bindParam(':EA',$estadoAsistencia,PDO::PARAM_STR);
                    $stmt->bindParam(':ID',$id_ingreso,PDO::PARAM_INT);
                    return $stmt->execute();
                }

        }catch(PDOException $e)
        {
            error_log("Error al marca salida: ". $e->getMessage());
            return false;

        }
    }
    //obtener el historial privado de cada aprendiz
    public static function HistorialAprendiz(int $idAprendiz): array {
        $registros=[];
         $idAprendiz = (int) $idAprendiz;
        if ($idAprendiz <= 0) {
            return $registros;
        }
        try{
            $mysql= new MySQL();
            $mysql->conectarBD();
            $conexion=$mysql->getConexion();
            if($conexion)
                {
                    $consulta="SELECT ingresos.id_ingresos,ingresos.fecha_registro,ingresos.entrada,ingresos.salida, ingresos.estado_asistencia AS estado
                    FROM ingresos where ingresos.fk_aprendiz=:idAprendiz
                    ORDER BY ingresos.fecha_registro desc, ingresos.entrada";
                    $stmt=$conexion->prepare($consulta);
                    $stmt->bindValue(':idAprendiz',$idAprendiz,PDO::PARAM_INT);
                    $stmt->execute();
                    while ($row=$stmt->fetch(PDO::FETCH_ASSOC))
                        {
                            $registros[]=[
                                'id'           => (int)$row['id_ingresos'],
                                'fecha'        =>htmlspecialchars( $row['fecha_registro']?? '',ENT_QUOTES,'UTF-8'),
                                'hora_entrada' => $row['entrada'] ? date('H:i:s', strtotime($row['entrada'])) : '—',
                                'hora_salida'  => ($row['salida'] && $row['salida'] !== '0000-00-00 00:00:00') ? date('H:i:s', strtotime($row['salida'])) : null,
                                'estado'       => htmlspecialchars($row['estado']?? '',ENT_QUOTES,'UTF-8')


                            ];
                        }
                }

        }catch(PDOException $e)
        {
            error_log("Error en obtenerHistorialPorAprendiz: ". $e->getMessage());

        }
        return $registros;
    }
    //eliminare los registros que se cumplieron perfectamente del dia de hoy
    public function BorrarRegistros(?string $fecha = null): array
    {
        try{
            $mysql= new MySQL();
            $mysql->conectarBD();
            $conexion= $mysql->getConexion();
            if($conexion)
                {
                    $estadoABorrar= "Puntual/Salio a la hora correspondiente";
                    //si no se da una fecha utilizo la de hoy
                    //los dos signos ?? significan que si no hay una fecha es decir si es nula 
                    //se utilizara la fecha actual
                    $fechaCierre= $fecha ?? date('Y-m-d'); 
                    $consulta="DELETE FROM ingresos where estado_asistencia =:estadoABorrar and fecha_registro=:fechaCierre";
                    $stmt=$conexion->prepare($consulta);
                    $stmt->bindParam(':estadoABorrar',$estadoABorrar,PDO::PARAM_STR);
                    $stmt->bindParam(':fechaCierre',$fechaCierre,PDO::PARAM_STR);
                    $stmt->execute();
                    $filasEliminadas=$stmt->rowCount();
                    return ['success'=>true,'eliminados'=>$filasEliminadas,'fecha'=>$fechaCierre];
                }
                  return ['success'=>false,'eliminados'=>0];

        }catch(PDOException $e)
        {
            error_log("Error al eliminar aprendices con cumplimienro en el horario ". $e->getMessage());
            return ['success'=>false,'eliminados'=>0];

        }

    } 
}
?>