<?php
// ──────────────────────────────────────────────
//  models/AprendizModel.php — Modelo de Aprendices
// ──────────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';

class AprendizModel {

    /**
     * Cuenta el total de aprendices registrados en la BD (opcionalmente filtrando por ficha o por instructor)
     */
    public static function contarActivos(?int $idFicha = null, ?int $instructorId = null): int {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                if ($idFicha && $idFicha > 0) {
                    $stmt = $conexion->prepare("SELECT COUNT(*) FROM aprendiz WHERE fk_ficha = :ficha");
                    $stmt->execute([':ficha' => $idFicha]);
                    return (int) $stmt->fetchColumn();
                } else if ($instructorId && $instructorId > 0) {
                    $stmt = $conexion->prepare("SELECT COUNT(*) FROM aprendiz a JOIN ficha f ON a.fk_ficha = f.id_ficha WHERE (
                        f.fk_usuario = :instructor 
                        OR f.id_ficha IN (SELECT fk_ficha FROM ficha_instructor WHERE fk_usuario = :instructor)
                        OR f.id_ficha IN (SELECT fk_ficha FROM ficha_asignatura WHERE fk_usuario_instructor = :instructor)
                        OR f.id_ficha IN (SELECT fk_ficha FROM horario_bloque WHERE fk_usuario_instructor = :instructor)
                    )");
                    $stmt->execute([':instructor' => $instructorId]);
                    return (int) $stmt->fetchColumn();
                }
                return (int) $conexion->query("SELECT COUNT(*) FROM aprendiz")->fetchColumn();
            }
        } catch (Exception $e) {
            // Devuelve 0 si falla la conexión
        }

        return 0;
    }

    /**
     * Verifica si un código RFID ya está asignado a otro aprendiz en la base de datos
     */
    public static function existeCodigoRfid(?string $codigoRfid, ?int $excluirUsuarioId = null): bool {
        $codigoRfid = trim((string)$codigoRfid);
        if (empty($codigoRfid)) return false;

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                $sql = "SELECT COUNT(*) FROM aprendiz WHERE LOWER(TRIM(codigo_rfid)) = LOWER(:rfid)";
                $params = [':rfid' => $codigoRfid];
                if ($excluirUsuarioId && $excluirUsuarioId > 0) {
                    $sql .= " AND fk_usuario != :excluirUsuarioId";
                    $params[':excluirUsuarioId'] = $excluirUsuarioId;
                }
                $stmt = $conexion->prepare($sql);
                $stmt->execute($params);
                return ((int)$stmt->fetchColumn()) > 0;
            }
        } catch (Exception $e) {}
        return false;
    }

    /**
     * Inserta un nuevo registro en la tabla aprendiz asociando código RFID, ficha y usuario
     */
    public static function crearAprendiz(?string $codigoRfid, int $fkFicha, int $fkUsuario, string $estado = 'Activo'): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $codigoRfid = (!empty(trim((string)$codigoRfid))) ? trim((string)$codigoRfid) : null;
                $sql = "INSERT INTO aprendiz (codigo_rfid, fk_ficha, fk_usuario, estado) VALUES (:rfid, :ficha, :usuario, :estado)";
                $stmt = $conexion->prepare($sql);
                return $stmt->execute([
                    ':rfid'    => $codigoRfid,
                    ':ficha'   => $fkFicha,
                    ':usuario' => $fkUsuario,
                    ':estado'  => $estado
                ]);
            }
        } catch (Exception $e) {
            // Silencioso
        }

        return false;
    }

    /**
     * Actualiza o crea los datos de un aprendiz (RFID, Ficha y Estado)
     */
    public static function actualizarAprendiz(?string $codigoRfid, int $fkFicha, int $fkUsuario, string $estado = 'Activo'): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $codigoRfid = (!empty(trim((string)$codigoRfid))) ? trim((string)$codigoRfid) : null;
                $check = (int) $conexion->query("SELECT COUNT(*) FROM aprendiz WHERE fk_usuario = " . (int)$fkUsuario)->fetchColumn();
                if ($check > 0) {
                    $sql = "UPDATE aprendiz SET codigo_rfid = :rfid, fk_ficha = :ficha, estado = :estado WHERE fk_usuario = :usuario";
                } else {
                    $sql = "INSERT INTO aprendiz (codigo_rfid, fk_ficha, fk_usuario, estado) VALUES (:rfid, :ficha, :usuario, :estado)";
                }
                $stmt = $conexion->prepare($sql);
                return $stmt->execute([
                    ':rfid'    => $codigoRfid,
                    ':ficha'   => $fkFicha,
                    ':usuario' => $fkUsuario,
                    ':estado'  => $estado
                ]);
            }
        } catch (Exception $e) {
            // Silencioso
        }

        return false;
    }

    /**
     * Obtiene la información del perfil del aprendiz por su fk_usuario
     */
    public static function obtenerPorUsuarioId(int $usuarioId): ?array {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                $sql = "SELECT a.id_aprendiz, a.codigo_rfid, a.fk_ficha, a.estado AS estado_aprendiz,
                               u.id_usuario, u.nombre, u.apellido, u.identificacion, u.telefono, u.nombre_usuario AS correo,
                               f.id_ficha AS numero_ficha, f.nombre_programa AS programa
                        FROM aprendiz a
                        JOIN usuario u ON a.fk_usuario = u.id_usuario
                        LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha
                        WHERE a.fk_usuario = :id LIMIT 1";

                $stmt = $conexion->prepare($sql);
                $stmt->execute([':id' => $usuarioId]);
                $datos = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($datos) {
                    return [
                        'id_aprendiz'   => $datos['id_aprendiz'],
                        'nombre'        => trim($datos['nombre'] . ' ' . $datos['apellido']),
                        'documento'     => $datos['identificacion'],
                        'telefono'      => $datos['telefono'],
                        'correo'        => $datos['correo'],
                        'numero_ficha'  => $datos['numero_ficha'] ?? 'N/A',
                        'programa'      => $datos['programa'] ?? 'Sin programa',
                        'estado'        => !empty($datos['estado_aprendiz']) ? $datos['estado_aprendiz'] : 'Activo'
                    ];
                }
            }
        } catch (Exception $e) {
            // Devuelve null si falla
        }

        return null;
    }

    public function obtenerAprendiz($codigo)
    {
        try{
            $mysql= new MySQL();
            $mysql->conectarBD();
            $conexion=$mysql->getConexion();
            if($conexion)
                {
                    $consulta="SELECT a.id_aprendiz, a.fk_ficha, u.nombre, u.apellido, f.nombre_programa 
                               FROM aprendiz a
                               LEFT JOIN usuario u ON a.fk_usuario = u.id_usuario
                               LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha
                               WHERE a.codigo_rfid = :codigo OR u.identificacion = :codigo OR a.id_aprendiz = :codigo 
                               LIMIT 1";
                    $stmt=$conexion->prepare($consulta);
                    $stmt->bindParam(':codigo',$codigo,pdo::PARAM_STR);
                    $stmt->execute();
                    return $stmt->fetch(PDO::FETCH_ASSOC);
                }

        }
        catch(PDOException $excepcion_error){
            error_log("Error en aprendiz Model: ". $excepcion_error->getMessage());
            return false;
        }
        return false;
    }

    public function obtenerAprendizPorId($id_aprendiz_manual)
    {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            
            if ($conexion) {
                $consulta = "SELECT a.id_aprendiz, a.fk_ficha, u.nombre, u.apellido, f.nombre_programa 
                             FROM aprendiz a
                             LEFT JOIN usuario u ON a.fk_usuario = u.id_usuario
                             LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha
                             WHERE a.id_aprendiz = :id OR u.identificacion = :id OR a.codigo_rfid = :id 
                             LIMIT 1";
                $stmt = $conexion->prepare($consulta);
                $stmt->bindParam(':id', $id_aprendiz_manual, PDO::PARAM_STR);
                $stmt->execute();
                
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $excepcion_error) {
            error_log("Error al buscar aprendiz por ID: " . $excepcion_error->getMessage());
            return false;
        }
        return false;
    }

    /**
     * Importación Masiva de Aprendices desde Excel
     * Registra el usuario con clave predeterminada 'sena2025', rol Aprendiz,
     * deja el RFID en NULL para asignación administrativa y maneja datos vacíos como NULL.
     */
    public static function importarAprendices(array $aprendices, int $idFicha): array {
        $resultado = [
            'exito' => false,
            'mensaje' => '',
            'total_procesados' => 0,
            'nuevos_creados' => 0,
            'existentes_omitidos' => 0,
            'incompletos_omitidos' => 0,
            'con_datos_null' => 0,
            'detalles' => []
        ];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $pdo = $mysql->getConexion();
            if (!$pdo) {
                $resultado['mensaje'] = 'Error al conectar con la base de datos MySQL.';
                return $resultado;
            }

            // Asegurar que la columna 'direccion' exista en la tabla usuario
            try {
                $chkDir = $pdo->query("SHOW COLUMNS FROM usuario LIKE 'direccion'")->fetchAll();
                if (empty($chkDir)) {
                    $pdo->exec("ALTER TABLE usuario ADD COLUMN direccion VARCHAR(150) NULL DEFAULT NULL AFTER telefono");
                }
                $pdo->exec("ALTER TABLE usuario MODIFY telefono VARCHAR(45) NULL DEFAULT NULL");
            } catch (Exception $e) {}

            // Validar que la ficha exista
            $stmtFicha = $pdo->prepare("SELECT id_ficha, nombre_programa FROM ficha WHERE id_ficha = :id LIMIT 1");
            $stmtFicha->execute([':id' => $idFicha]);
            $fichaData = $stmtFicha->fetch(PDO::FETCH_ASSOC);
            if (!$fichaData) {
                $resultado['mensaje'] = "La ficha seleccionada (#{$idFicha}) no existe en el sistema.";
                return $resultado;
            }

            $pdo->beginTransaction();

            $hashDefault = password_hash('sena2025', PASSWORD_BCRYPT);
            $rolAprendizId = 3;

            $stmtBuscar = $pdo->prepare("SELECT id_usuario, nombre_usuario, identificacion FROM usuario WHERE identificacion = :ident OR nombre_usuario = :correo LIMIT 1");
            $stmtCheckAprendiz = $pdo->prepare("SELECT id_aprendiz, fk_ficha FROM aprendiz WHERE fk_usuario = :uid LIMIT 1");
            $stmtInsertUser = $pdo->prepare("INSERT INTO usuario (nombre_usuario, contrasena, nombre, apellido, identificacion, telefono, direccion, fk_rol)
                                             VALUES (:correo, :pass, :nombre, :apellido, :ident, :tel, :dir, :rol)");
            $stmtInsertAprendiz = $pdo->prepare("INSERT INTO aprendiz (codigo_rfid, fk_ficha, fk_usuario, estado) VALUES (NULL, :ficha, :uid, 'Activo')");
            $stmtUpdateFicha = $pdo->prepare("UPDATE aprendiz SET fk_ficha = :ficha WHERE fk_usuario = :uid");

            foreach ($aprendices as $idx => $a) {
                $filaNum = $idx + 1;
                $ident = trim((string)($a['identificacion'] ?? $a['documento'] ?? ''));
                $nombres = trim((string)($a['nombres'] ?? $a['nombre'] ?? ''));
                $apellidos = trim((string)($a['apellidos'] ?? $a['apellido'] ?? ''));
                $correo = trim(mb_strtolower((string)($a['correo'] ?? $a['correo_electronico'] ?? $a['email'] ?? '')));

                // Campos opcionales: si están vacíos se asignan como NULL explícito
                $rawTel = trim((string)($a['telefono'] ?? ''));
                $rawDir = trim((string)($a['direccion'] ?? ''));
                $telefono = (!empty($rawTel)) ? $rawTel : null;
                $direccion = (!empty($rawDir)) ? $rawDir : null;

                if ($telefono === null || $direccion === null) {
                    $resultado['con_datos_null']++;
                }

                // Validación de campos requeridos
                if (empty($ident) || empty($nombres) || empty($apellidos) || empty($correo)) {
                    $resultado['incompletos_omitidos']++;
                    $resultado['detalles'][] = [
                        'fila' => $filaNum,
                        'identificacion' => $ident ?: '—',
                        'nombre_completo' => trim("{$nombres} {$apellidos}") ?: '—',
                        'correo' => $correo ?: '—',
                        'telefono' => $telefono,
                        'direccion' => $direccion,
                        'estado' => 'Incompleto',
                        'mensaje' => 'Omitido: falta identificación, nombres, apellidos o correo electrónico'
                    ];
                    continue;
                }

                // Verificar si ya existe en la base de datos
                $stmtBuscar->execute([':ident' => $ident, ':correo' => $correo]);
                $userExistente = $stmtBuscar->fetch(PDO::FETCH_ASSOC);

                if ($userExistente) {
                    $uid = (int)$userExistente['id_usuario'];
                    $stmtCheckAprendiz->execute([':uid' => $uid]);
                    $aprendizExistente = $stmtCheckAprendiz->fetch(PDO::FETCH_ASSOC);

                    if ($aprendizExistente) {
                        if ((int)$aprendizExistente['fk_ficha'] === $idFicha) {
                            $resultado['existentes_omitidos']++;
                            $resultado['detalles'][] = [
                                'fila' => $filaNum,
                                'identificacion' => $ident,
                                'nombre_completo' => "{$nombres} {$apellidos}",
                                'correo' => $correo,
                                'telefono' => $telefono,
                                'direccion' => $direccion,
                                'estado' => 'Existente',
                                'mensaje' => "Ya registrado en la Ficha #{$idFicha}"
                            ];
                        } else {
                            $stmtUpdateFicha->execute([':ficha' => $idFicha, ':uid' => $uid]);
                            $resultado['nuevos_creados']++;
                            $resultado['detalles'][] = [
                                'fila' => $filaNum,
                                'identificacion' => $ident,
                                'nombre_completo' => "{$nombres} {$apellidos}",
                                'correo' => $correo,
                                'telefono' => $telefono,
                                'direccion' => $direccion,
                                'estado' => 'Actualizado',
                                'mensaje' => "Aprendiz reasignado a esta Ficha #{$idFicha}"
                            ];
                        }
                    } else {
                        $stmtInsertAprendiz->execute([':ficha' => $idFicha, ':uid' => $uid]);
                        $resultado['nuevos_creados']++;
                        $resultado['detalles'][] = [
                            'fila' => $filaNum,
                            'identificacion' => $ident,
                            'nombre_completo' => "{$nombres} {$apellidos}",
                            'correo' => $correo,
                            'telefono' => $telefono,
                            'direccion' => $direccion,
                            'estado' => 'Vinculado',
                            'mensaje' => "Usuario vinculado como aprendiz en la Ficha #{$idFicha}"
                        ];
                    }
                } else {
                    // Crear nuevo usuario con contraseña predeterminada sena2025
                    $stmtInsertUser->execute([
                        ':correo'   => $correo,
                        ':pass'     => $hashDefault,
                        ':nombre'   => $nombres,
                        ':apellido' => $apellidos,
                        ':ident'    => $ident,
                        ':tel'      => $telefono,
                        ':dir'      => $direccion,
                        ':rol'      => $rolAprendizId
                    ]);
                    $newUid = (int)$pdo->lastInsertId();

                    // Insertar en aprendiz con RFID en NULL (para que el admin lo ingrese)
                    $stmtInsertAprendiz->execute([
                        ':ficha' => $idFicha,
                        ':uid'   => $newUid
                    ]);

                    $resultado['nuevos_creados']++;
                    $resultado['detalles'][] = [
                        'fila' => $filaNum,
                        'identificacion' => $ident,
                        'nombre_completo' => "{$nombres} {$apellidos}",
                        'correo' => $correo,
                        'telefono' => $telefono,
                        'direccion' => $direccion,
                        'estado' => 'Creado',
                        'mensaje' => "Perfil creado con clave 'sena2025' (RFID pendiente)"
                    ];
                }

                $resultado['total_procesados']++;
            }

            $pdo->commit();
            $resultado['exito'] = true;
            $resultado['mensaje'] = "Importación completada: {$resultado['nuevos_creados']} aprendices registrados/vinculados, {$resultado['existentes_omitidos']} ya existían en la ficha.";

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $resultado['exito'] = false;
            $resultado['mensaje'] = 'Error al procesar la importación: ' . $e->getMessage();
        }

        return $resultado;
    }
}
?>
