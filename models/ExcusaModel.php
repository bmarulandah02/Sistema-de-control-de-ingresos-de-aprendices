<?php
// ──────────────────────────────────────────────
//  models/ExcusaModel.php — Modelo de Excusas Médicas
// ──────────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';

class ExcusaModel {

    /**
     * Asegura que existan las columnas y tablas necesarias para la gestión
     * multi-instructor de excusas médicas.
     */
    private static function asegurarColumnas($conexion): void {
        try { $conexion->exec("ALTER TABLE excusa MODIFY COLUMN observacion VARCHAR(500) NULL"); } catch (Exception $e) {}
        try { $conexion->exec("ALTER TABLE excusa ADD COLUMN fecha_inicio DATE NULL AFTER observacion"); } catch (Exception $e) {}
        try { $conexion->exec("ALTER TABLE excusa ADD COLUMN fecha_fin DATE NULL AFTER fecha_inicio"); } catch (Exception $e) {}
        try { $conexion->exec("ALTER TABLE excusa ADD COLUMN motivo_rechazo VARCHAR(500) NULL AFTER fecha_fin"); } catch (Exception $e) {}
        try { $conexion->exec("ALTER TABLE excusa ADD COLUMN fk_aprendiz INT NULL AFTER fk_ingreso"); } catch (Exception $e) {}
        try { $conexion->exec("ALTER TABLE excusa ADD COLUMN fecha_registro DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER fk_usuario_instructor"); } catch (Exception $e) {}
        try { $conexion->exec("ALTER TABLE excusa ADD COLUMN fecha_revision DATETIME NULL AFTER fecha_registro"); } catch (Exception $e) {}
        try { $conexion->exec("ALTER TABLE excusa MODIFY COLUMN fk_ingreso INT NULL"); } catch (Exception $e) {}
        try { $conexion->exec("ALTER TABLE excusa MODIFY COLUMN fk_usuario_instructor INT NULL"); } catch (Exception $e) {}

        // Tabla para asignación y evaluación independiente por cada instructor involucrado en la fecha/bloques
        try {
            $sqlEi = "CREATE TABLE IF NOT EXISTS excusa_instructor (
                id_excusa_instructor INT AUTO_INCREMENT PRIMARY KEY,
                fk_excusa INT NOT NULL,
                fk_usuario_instructor INT NOT NULL,
                detalle_clases VARCHAR(255) NULL,
                estado VARCHAR(30) DEFAULT 'Pendiente',
                motivo_rechazo VARCHAR(500) NULL,
                fecha_decision DATETIME NULL,
                UNIQUE KEY uq_excusa_inst (fk_excusa, fk_usuario_instructor),
                KEY idx_ei_inst (fk_usuario_instructor),
                CONSTRAINT fk_ei_excusa FOREIGN KEY (fk_excusa) REFERENCES excusa (id_excusa) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
            $conexion->exec($sqlEi);
        } catch (Exception $e) {}

        // Sincronizar registros existentes que no tengan asignación en excusa_instructor
        try {
            $sqlSync = "INSERT IGNORE INTO excusa_instructor (fk_excusa, fk_usuario_instructor, detalle_clases, estado, fecha_decision, motivo_rechazo)
                SELECT e.id_excusa,
                       COALESCE(e.fk_usuario_instructor, f.fk_usuario) AS inst_id,
                       'Jornada de formación' AS detalle_clases,
                       COALESCE(e.estado, 'Pendiente') AS estado,
                       e.fecha_revision,
                       e.motivo_rechazo
                FROM excusa e
                JOIN aprendiz a ON e.fk_aprendiz = a.id_aprendiz
                JOIN ficha f ON a.fk_ficha = f.id_ficha
                WHERE COALESCE(e.fk_usuario_instructor, f.fk_usuario) IS NOT NULL
                  AND NOT EXISTS (SELECT 1 FROM excusa_instructor ei WHERE ei.fk_excusa = e.id_excusa);";
            $conexion->exec($sqlSync);
        } catch (Exception $e) {}
    }

    /**
     * Obtiene el listado completo de excusas médicas (Uso administrativo / auditoría)
     */
    public static function obtenerTodas(): array {
        $excusas = [];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                self::asegurarColumnas($conexion);
                $sql = "SELECT e.id_excusa, e.documento, e.observacion, e.fecha_inicio, e.fecha_fin, e.estado, e.motivo_rechazo,
                               CONCAT(u.nombre, ' ', u.apellido) AS aprendiz, u.identificacion AS documento_aprendiz,
                               a.fk_ficha AS numero_ficha, f.nombre_programa
                        FROM excusa e
                        JOIN aprendiz a ON e.fk_aprendiz = a.id_aprendiz
                        JOIN usuario u ON a.fk_usuario = u.id_usuario
                        LEFT JOIN ficha f ON a.fk_ficha = f.id_ficha
                        ORDER BY e.id_excusa DESC";

                $stmt = $conexion->query($sql);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $excusas[] = [
                        'id'             => $row['id_excusa'],
                        'aprendiz'       => !empty(trim($row['aprendiz'])) ? $row['aprendiz'] : $row['documento_aprendiz'],
                        'documento'      => $row['documento_aprendiz'],
                        'numero_ficha'   => $row['numero_ficha'] ?? 'N/A',
                        'nombre_programa'=> $row['nombre_programa'] ?? '',
                        'motivo'         => $row['observacion'] ?? 'Excusa médica',
                        'fecha_inicio'   => $row['fecha_inicio'],
                        'fecha_fin'      => $row['fecha_fin'],
                        'archivo'        => $row['documento'] ?? '',
                        'estado'         => $row['estado'] ?? 'Pendiente',
                        'motivo_rechazo' => $row['motivo_rechazo'] ?? '',
                        'created_at'     => $row['fecha_inicio']
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Error en obtenerTodas: " . $e->getMessage());
        }

        return $excusas;
    }

    /**
     * Obtiene las excusas de un aprendiz con el desglose de decisiones de cada instructor
     */
    public static function obtenerPorAprendiz(int $idUsuarioAprendiz): array {
        $excusas = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                self::asegurarColumnas($conexion);
                $sql = "SELECT e.id_excusa, e.documento, e.observacion, e.fecha_inicio, e.fecha_fin, e.estado,
                               e.motivo_rechazo, e.fecha_registro,
                               CONCAT(u.nombre, ' ', u.apellido) AS aprendiz, u.identificacion AS documento_aprendiz,
                               a.fk_ficha AS numero_ficha
                        FROM excusa e
                        JOIN aprendiz a ON e.fk_aprendiz = a.id_aprendiz
                        JOIN usuario u ON a.fk_usuario = u.id_usuario
                        WHERE u.id_usuario = :idUsuario
                        ORDER BY e.id_excusa DESC";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([':idUsuario' => $idUsuarioAprendiz]);

                $stmtRev = $conexion->prepare("SELECT ei.id_excusa_instructor, ei.fk_usuario_instructor, ei.estado, 
                                                      ei.motivo_rechazo, ei.detalle_clases, ei.fecha_decision,
                                                      CONCAT(ui.nombre, ' ', ui.apellido) AS nombre_instructor
                                               FROM excusa_instructor ei
                                               JOIN usuario ui ON ei.fk_usuario_instructor = ui.id_usuario
                                               WHERE ei.fk_excusa = :idExcusa
                                               ORDER BY ei.id_excusa_instructor ASC");

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $stmtRev->execute([':idExcusa' => $row['id_excusa']]);
                    $revisiones = $stmtRev->fetchAll(PDO::FETCH_ASSOC) ?: [];

                    $excusas[] = [
                        'id'             => $row['id_excusa'],
                        'aprendiz'       => !empty(trim($row['aprendiz'])) ? $row['aprendiz'] : $row['documento_aprendiz'],
                        'documento'      => $row['documento_aprendiz'],
                        'numero_ficha'   => $row['numero_ficha'] ?? 'N/A',
                        'motivo'         => $row['observacion'] ?? 'Excusa médica',
                        'fecha_inicio'   => $row['fecha_inicio'],
                        'fecha_fin'      => $row['fecha_fin'],
                        'archivo'        => $row['documento'] ?? '',
                        'estado'         => $row['estado'] ?? 'Pendiente',
                        'motivo_rechazo' => $row['motivo_rechazo'] ?? '',
                        'revisiones'     => $revisiones,
                        'created_at'     => $row['fecha_registro'] ?? $row['fecha_inicio']
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Error en obtenerPorAprendiz: " . $e->getMessage());
        }
        return $excusas;
    }

    /**
     * Obtiene el listado de instructores asignados a la ficha del aprendiz
     * para que pueda seleccionar a quién va dirigida la excusa.
     */
    public static function obtenerInstructoresPorUsuarioAprendiz(int $idUsuarioAprendiz): array {
        $instructores = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                $stmtF = $conexion->prepare("SELECT fk_ficha FROM aprendiz WHERE fk_usuario = :id LIMIT 1");
                $stmtF->execute([':id' => $idUsuarioAprendiz]);
                $fichaId = $stmtF->fetchColumn();

                $sql = "SELECT DISTINCT u.id_usuario, u.nombre, u.apellido, u.nombre_usuario,
                               CASE WHEN (f.id_ficha IS NOT NULL OR hb.id_horario_bloque IS NOT NULL) THEN 1 ELSE 0 END as es_de_ficha
                        FROM usuario u
                        LEFT JOIN ficha f ON (f.id_ficha = :ficha AND f.fk_usuario = u.id_usuario)
                        LEFT JOIN horario_bloque hb ON (hb.fk_ficha = :ficha AND hb.fk_usuario_instructor = u.id_usuario)
                        WHERE u.fk_rol = 2
                        ORDER BY es_de_ficha DESC, u.nombre ASC, u.apellido ASC";

                $stmt = $conexion->prepare($sql);
                $stmt->execute([':ficha' => $fichaId ?: 0]);
                $instructores = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (Exception $e) {
            error_log("Error en obtenerInstructoresPorUsuarioAprendiz: " . $e->getMessage());
        }
        return $instructores;
    }

    /**
     * Registra una nueva excusa médica.
     * Permite dirigirla a un instructor específico, a todos, o detectar automáticamente
     * según los bloques de horario programados en las fechas indicadas.
     */
    public static function crearExcusa(int $idAprendiz, string $motivo, string $fechaInicio, string $fechaFin, ?string $archivo, $instructorDestino = 'auto'): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if (!$conexion) return false;

            self::asegurarColumnas($conexion);

            // Obtener ficha del aprendiz
            $stmtFicha = $conexion->prepare("SELECT a.fk_ficha, f.fk_usuario AS instructor_ficha 
                                             FROM aprendiz a 
                                             JOIN ficha f ON a.fk_ficha = f.id_ficha 
                                             WHERE a.id_aprendiz = :id LIMIT 1");
            $stmtFicha->execute([':id' => $idAprendiz]);
            $datosFicha = $stmtFicha->fetch(PDO::FETCH_ASSOC);

            $sql = "INSERT INTO excusa (documento, observacion, fecha_inicio, fecha_fin, estado, fk_aprendiz, fecha_registro)
                    VALUES (:documento, :observacion, :fecha_inicio, :fecha_fin, 'Pendiente', :fk_aprendiz, NOW())";
            $stmt = $conexion->prepare($sql);
            $ok = $stmt->execute([
                ':documento'    => $archivo,
                ':observacion'  => $motivo,
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin'    => $fechaFin,
                ':fk_aprendiz'  => $idAprendiz
            ]);

            if (!$ok) return false;

            $idExcusa = (int)$conexion->lastInsertId();

            $instructoresAsignados = [];

            // 1. Caso: El aprendiz seleccionó un instructor específico (ej. Inst. Bladimir Silva)
            if (is_numeric($instructorDestino) && (int)$instructorDestino > 0) {
                $instId = (int)$instructorDestino;
                $stmtInst = $conexion->prepare("SELECT CONCAT(nombre, ' ', apellido) FROM usuario WHERE id_usuario = :id");
                $stmtInst->execute([':id' => $instId]);
                $nom = $stmtInst->fetchColumn() ?: 'Instructor Asignado';

                $instructoresAsignados[$instId] = [
                    'nombre'  => $nom,
                    'bloques' => ["Inasistencia del $fechaInicio al $fechaFin"]
                ];
            } 
            // 2. Caso: Asignar a todos los instructores vinculados a la ficha
            elseif ($instructorDestino === 'todos' && !empty($datosFicha['fk_ficha'])) {
                $stmtTodos = $conexion->prepare("SELECT DISTINCT u.id_usuario, CONCAT(u.nombre, ' ', u.apellido) AS nombre
                                                 FROM usuario u 
                                                 WHERE u.id_usuario IN (
                                                     SELECT fk_usuario FROM ficha WHERE id_ficha = :ficha
                                                     UNION
                                                     SELECT fk_usuario_instructor FROM horario_bloque WHERE fk_ficha = :ficha
                                                 )");
                $stmtTodos->execute([':ficha' => $datosFicha['fk_ficha']]);
                while ($it = $stmtTodos->fetch(PDO::FETCH_ASSOC)) {
                    $instructoresAsignados[(int)$it['id_usuario']] = [
                        'nombre'  => $it['nombre'],
                        'bloques' => ["Inasistencia del $fechaInicio al $fechaFin"]
                    ];
                }
            } 
            // 3. Caso: Detección automática por horario_bloque
            else {
                if (!empty($datosFicha['fk_ficha'])) {
                    $stmtHb = $conexion->prepare("SELECT hb.fecha, hb.bloque, hb.fk_usuario_instructor, hb.materia,
                                                         CONCAT(u.nombre, ' ', u.apellido) AS instructor_nombre
                                                  FROM horario_bloque hb
                                                  JOIN usuario u ON hb.fk_usuario_instructor = u.id_usuario
                                                  WHERE hb.fk_ficha = :ficha
                                                    AND hb.fecha BETWEEN :fIni AND :fFin
                                                  ORDER BY hb.fecha ASC, hb.bloque ASC");
                    $stmtHb->execute([
                        ':ficha' => $datosFicha['fk_ficha'],
                        ':fIni'  => $fechaInicio,
                        ':fFin'  => $fechaFin
                    ]);
                    $filasHb = $stmtHb->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($filasHb as $hb) {
                        $instId = (int)$hb['fk_usuario_instructor'];
                        if (!isset($instructoresAsignados[$instId])) {
                            $instructoresAsignados[$instId] = [
                                'nombre'  => $hb['instructor_nombre'],
                                'bloques' => []
                            ];
                        }
                        $nomBloque = $hb['bloque'];
                        if (str_contains($nomBloque, '1')) $nomBloque = 'Bloque 1';
                        elseif (str_contains($nomBloque, '2')) $nomBloque = 'Bloque 2';
                        $instructoresAsignados[$instId]['bloques'][] = $hb['fecha'] . ' (' . $nomBloque . (!empty($hb['materia']) ? ' - ' . $hb['materia'] : '') . ')';
                    }
                }

                // Si no hay bloques en horario_bloque para esa fecha, asignar al instructor de la ficha
                if (empty($instructoresAsignados)) {
                    $idInstFicha = (int)($datosFicha['instructor_ficha'] ?? 0);
                    if ($idInstFicha > 0) {
                        $instructoresAsignados[$idInstFicha] = [
                            'nombre'  => 'Instructor de Ficha',
                            'bloques' => ['Jornada regular de formación']
                        ];
                    }
                }
            }

            // Registrar cada instructor en excusa_instructor
            $stmtInsEi = $conexion->prepare("INSERT INTO excusa_instructor 
                (fk_excusa, fk_usuario_instructor, detalle_clases, estado) 
                VALUES (:excusa, :instructor, :detalle, 'Pendiente')
                ON DUPLICATE KEY UPDATE detalle_clases = VALUES(detalle_clases)");

            $primerInst = null;
            foreach ($instructoresAsignados as $instId => $info) {
                if ($primerInst === null) $primerInst = $instId;
                $detalle = implode('; ', array_slice($info['bloques'], 0, 4));
                if (count($info['bloques']) > 4) {
                    $detalle .= ' (+ ' . (count($info['bloques']) - 4) . ' clases más)';
                }
                $stmtInsEi->execute([
                    ':excusa'     => $idExcusa,
                    ':instructor' => $instId,
                    ':detalle'    => $detalle
                ]);
            }

            if ($primerInst) {
                try {
                    $conexion->prepare("UPDATE excusa SET fk_usuario_instructor = :inst WHERE id_excusa = :id")->execute([':inst' => $primerInst, ':id' => $idExcusa]);
                } catch (Exception $e) {}
            }

            return true;
        } catch (Exception $e) {
            error_log("Error al crear excusa: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene una excusa por ID
     */
    public static function obtenerPorId(int $idExcusa): ?array {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                self::asegurarColumnas($conexion);

                $sql = "SELECT e.id_excusa, e.observacion, e.fecha_inicio, e.fecha_fin, e.estado, e.motivo_rechazo,
                               a.fk_usuario AS id_usuario_aprendiz, f.fk_usuario AS id_instructor_ficha
                        FROM excusa e
                        JOIN aprendiz a ON e.fk_aprendiz = a.id_aprendiz
                        JOIN ficha f ON a.fk_ficha = f.id_ficha
                        WHERE e.id_excusa = :id
                        LIMIT 1";

                $stmt = $conexion->prepare($sql);
                $stmt->execute([':id' => $idExcusa]);

                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ?: null;
            }
        } catch (Exception $e) {
            error_log("Error al obtener excusa por id: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Permite al aprendiz editar una excusa si aún no ha sido revisada
     */
    public static function editarExcusa(int $idExcusa, string $motivo, string $fechaInicio, string $fechaFin, ?string $nuevoArchivo = null): bool {
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                self::asegurarColumnas($conexion);
                $sql = "UPDATE excusa 
                        SET observacion = :observacion, fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin "
                     . ($nuevoArchivo ? ", documento = :documento " : "")
                     . "WHERE id_excusa = :id AND estado = 'Pendiente'";
                $parametros = [
                    ':observacion'  => $motivo,
                    ':fecha_inicio' => $fechaInicio,
                    ':fecha_fin'    => $fechaFin,
                    ':id'           => $idExcusa
                ];

                if ($nuevoArchivo) {
                    $parametros[':documento'] = $nuevoArchivo;
                }
                $stmt = $conexion->prepare($sql);
                return $stmt->execute($parametros);
            }
        } catch (Exception $e) {
            error_log("Error al editar excusa: " . $e->getMessage());
        }
        return false;
    }

    /**
     * Obtiene únicamente las excusas asignadas al instructor indicado.
     * Si un aprendiz faltó con un instructor en un bloque, SOLO ese instructor la ve.
     * Si hubo dos instructores en un día, cada uno ve su respectiva asignación.
     */
    public static function obtenerPorInstructor(int $idInstructor, ?string $estado = null): array {
        $excusas = [];
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                self::asegurarColumnas($conexion);

                $sql = "SELECT COALESCE(ei.id_excusa_instructor, 0) AS id_excusa_instructor, 
                               COALESCE(ei.estado, e.estado) AS estado_instructor, 
                               COALESCE(ei.motivo_rechazo, e.motivo_rechazo) AS motivo_rechazo_instructor,
                               COALESCE(ei.detalle_clases, 'Clases del período') AS detalle_clases, 
                               ei.fecha_decision,
                               e.id_excusa, e.documento, e.observacion, e.fecha_inicio, e.fecha_fin, 
                               e.estado AS estado_general,
                               CONCAT(u.nombre, ' ', u.apellido) AS aprendiz, u.identificacion AS documento_aprendiz,
                               f.id_ficha AS numero_ficha, f.nombre_programa
                        FROM excusa e
                        LEFT JOIN excusa_instructor ei ON (ei.fk_excusa = e.id_excusa AND ei.fk_usuario_instructor = :idInstructor)
                        JOIN aprendiz a ON e.fk_aprendiz = a.id_aprendiz
                        JOIN usuario u ON a.fk_usuario = u.id_usuario
                        JOIN ficha f ON a.fk_ficha = f.id_ficha
                        WHERE (ei.fk_usuario_instructor = :idInstructor OR e.fk_usuario_instructor = :idInstructor)";

                $parametros = [':idInstructor' => $idInstructor];
                if (!empty($estado)) {
                    $sql .= " AND (ei.estado = :estado OR (ei.estado IS NULL AND e.estado = :estado))";
                    $parametros[':estado'] = $estado;
                }
                $sql .= " ORDER BY e.id_excusa DESC";

                $stmt = $conexion->prepare($sql);
                $stmt->execute($parametros);

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $excusas[] = [
                        'id'             => $row['id_excusa'],
                        'id_revision'    => $row['id_excusa_instructor'],
                        'aprendiz'       => !empty(trim($row['aprendiz'])) ? $row['aprendiz'] : $row['documento_aprendiz'],
                        'documento'      => $row['documento_aprendiz'],
                        'numero_ficha'   => $row['numero_ficha'] ?? 'N/A',
                        'nombre_programa'=> $row['nombre_programa'] ?? '',
                        'motivo'         => $row['observacion'] ?? 'Excusa médica',
                        'fecha_inicio'   => $row['fecha_inicio'],
                        'fecha_fin'      => $row['fecha_fin'],
                        'detalle_clases' => !empty($row['detalle_clases']) ? $row['detalle_clases'] : 'Clases a su cargo',
                        'archivo'        => $row['documento'] ?? '',
                        'estado'         => $row['estado_instructor'] ?? 'Pendiente',
                        'estado_general' => $row['estado_general'] ?? 'Pendiente',
                        'motivo_rechazo' => $row['motivo_rechazo_instructor'] ?? '',
                        'created_at'     => $row['fecha_inicio']
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Error en obtenerPorInstructor: " . $e->getMessage());
        }
        return $excusas;
    }

    /**
     * Actualiza la decisión de un instructor (Aprobar o Rechazar) para su sesión.
     * Si rechaza, guarda el motivo_rechazo.
     * Luego recalcula el estado global de la excusa.
     */
    public static function actualizarEstado(int $idExcusa, string $estado, int $idInstructor, ?string $motivoRechazo = null): bool {
        if (!in_array($estado, ['Aprobada', 'Rechazada'], true)) {
            return false;
        }
        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();
            if ($conexion) {
                self::asegurarColumnas($conexion);

                // 1. Actualizar el registro del instructor específico
                $stmtEi = $conexion->prepare("UPDATE excusa_instructor 
                                              SET estado = :estado, 
                                                  motivo_rechazo = :motivo, 
                                                  fecha_decision = NOW() 
                                              WHERE fk_excusa = :id AND fk_usuario_instructor = :instructor");
                $stmtEi->execute([
                    ':estado'     => $estado,
                    ':motivo'     => ($estado === 'Rechazada') ? $motivoRechazo : null,
                    ':id'         => $idExcusa,
                    ':instructor' => $idInstructor
                ]);

                // Si no existía fila previa en excusa_instructor, insertarla
                if ($stmtEi->rowCount() === 0) {
                    $stmtIns = $conexion->prepare("INSERT INTO excusa_instructor 
                        (fk_excusa, fk_usuario_instructor, detalle_clases, estado, motivo_rechazo, fecha_decision)
                        VALUES (:id, :instructor, 'Clases asignadas', :estado, :motivo, NOW())
                        ON DUPLICATE KEY UPDATE estado = VALUES(estado), motivo_rechazo = VALUES(motivo_rechazo), fecha_decision = NOW()");
                    $stmtIns->execute([
                        ':id'         => $idExcusa,
                        ':instructor' => $idInstructor,
                        ':estado'     => $estado,
                        ':motivo'     => ($estado === 'Rechazada') ? $motivoRechazo : null
                    ]);
                }

                // 2. Calcular estado consolidado para la tabla excusa
                $stmtCheck = $conexion->prepare("SELECT estado, motivo_rechazo FROM excusa_instructor WHERE fk_excusa = :id");
                $stmtCheck->execute([':id' => $idExcusa]);
                $estadosRev = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

                $aprobadas = 0;
                $rechazadas = 0;
                $pendientes = 0;
                $motivosRechazo = [];

                foreach ($estadosRev as $rev) {
                    if ($rev['estado'] === 'Aprobada') $aprobadas++;
                    elseif ($rev['estado'] === 'Rechazada') {
                        $rechazadas++;
                        if (!empty($rev['motivo_rechazo'])) {
                            $motivosRechazo[] = $rev['motivo_rechazo'];
                        }
                    } else {
                        $pendientes++;
                    }
                }

                $estadoGeneral = 'Pendiente';
                if ($pendientes === 0) {
                    if ($aprobadas > 0 && $rechazadas === 0) {
                        $estadoGeneral = 'Aprobada';
                    } elseif ($rechazadas > 0 && $aprobadas === 0) {
                        $estadoGeneral = 'Rechazada';
                    } elseif ($aprobadas > 0 && $rechazadas > 0) {
                        $estadoGeneral = 'Revisión Mixta';
                    }
                }

                $motivoConsolidado = !empty($motivosRechazo) ? implode(' | ', $motivosRechazo) : null;

                $sql = "UPDATE excusa 
                        SET estado = :estado, 
                            fk_usuario_instructor = :instructor, 
                            fecha_revision = NOW(),
                            motivo_rechazo = :motivo
                        WHERE id_excusa = :id";
                $stmt = $conexion->prepare($sql);
                return $stmt->execute([
                    ':estado'     => $estadoGeneral,
                    ':instructor' => $idInstructor,
                    ':motivo'     => $motivoConsolidado,
                    ':id'         => $idExcusa
                ]);
            }
        } catch (Exception $e) {
            error_log("Error al actualizar el estado de excusa: " . $e->getMessage());
        }
        return false;
    }
}
