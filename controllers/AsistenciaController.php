<?php 
//icio la conexion para el registro de asistencias 
//solicito los archivos que necesito para llevar acabo lo requerido 

require_once __DIR__ . '/../models/IngresoModel.php';
require_once __DIR__ . '/../models/AprendizModel.php';
require_once __DIR__ . '/../models/HorarioModel.php';

//genero la clase para recibir el codigo rfid
class AsistenciaController {

    public function index(?array $mensaje = null): void {
        $rolSesion       = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int) ($_SESSION['usuario_id'] ?? 0);

        if ($rolSesion === 'Instructor') {
            $fichas = HorarioModel::obtenerFichasPorInstructor($usuarioIdSesion);
            $soloInstructorId = $usuarioIdSesion;
            // Buscar clase programada EXCLUSIVAMENTE para el día de HOY
            $claseMomento = HorarioModel::obtenerClaseDelMomento(null, null, null, $usuarioIdSesion);
            
            // Si hoy NO tiene clase programada, buscar cuál es su próxima clase futura sólo para informarle
            $proximaClase = null;
            if (!$claseMomento) {
                $proximaClase = HorarioModel::obtenerProximaClaseInstructor($usuarioIdSesion);
            }
        } else {
            $fichas = HorarioModel::obtenerTodasFichas();
            $soloInstructorId = null;
            $idFichaPrincipal = !empty($fichas) ? (int)$fichas[0]['id'] : null;
            $claseMomento = HorarioModel::obtenerClaseDelMomento($idFichaPrincipal);
            $proximaClase = null;
        }

        // Si hoy SÍ hay clase programada para el instructor/ficha:
        if (!empty($claseMomento)) {
            $numFichaActual = $claseMomento['fk_ficha'] ?? (!empty($fichas) ? $fichas[0]['id'] : 0);
            $nombreMateria  = $claseMomento['materia'] ?? 'Formación';
            $_SESSION['materia_actual'] = $nombreMateria . ' - Ficha ' . $numFichaActual;
            $_SESSION['ficha_actual']   = (string)$numFichaActual;
            if (!empty($claseMomento['hora_inicio']) && !empty($claseMomento['hora_fin'])) {
                $_SESSION['bloque_actual'] = substr($claseMomento['hora_inicio'], 0, 5)
                    . '|' . substr($claseMomento['hora_fin'], 0, 5)
                    . '|' . ($claseMomento['bloque'] ?? 'bloque_1');
            }
        } else {
            // NO hay clase programada hoy: limpiar datos de sesión de clase activa
            unset($_SESSION['materia_actual'], $_SESSION['ficha_actual'], $_SESSION['bloque_actual']);
        }

        if (!$mensaje && isset($_SESSION['mensaje'])) {
            $mensaje = $_SESSION['mensaje'];
            unset($_SESSION['mensaje']);
        }
        require __DIR__ . '/../views/asistencia/registro.php';
    }

    public function abrirVentanaAsistencia(): void {
        $rolSesion       = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);

        // Validar que realmente tenga clase hoy antes de abrir la ventana de 5 minutos
        if ($rolSesion === 'Instructor') {
            $claseHoy = HorarioModel::obtenerClaseDelMomento(null, null, null, $usuarioIdSesion);
            if (!$claseHoy) {
                $_SESSION['mensaje'] = [
                    'texto' => "No tienes ninguna clase programada para el día de hoy en tu horario. No se puede abrir registro de asistencia.",
                    'tipo' => "warning"
                ];
                header("Location: index.php?action=asistencia");
                exit();
            }
        }

        $_SESSION['hora_apertura_asistencia'] = time();
        $_SESSION['mensaje'] = [
            'texto' => "¡Registro de asistencia abierto! Los aprendices que escaneen en los próximos 5 minutos serán marcados como PUNTUALES.",
            'tipo' => "success"
        ];
        header("Location: index.php?action=asistencia");
        exit();
    }

    public function lecturaCodigoRfid()
    {
        $rolSesion       = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);

        // Bloquear registro si es instructor y no tiene clase programada hoy
        if ($rolSesion === 'Instructor') {
            $claseHoy = HorarioModel::obtenerClaseDelMomento(null, null, null, $usuarioIdSesion);
            if (!$claseHoy) {
                $_SESSION['mensaje'] = [
                    'texto' => "No tienes ninguna clase programada para el día de hoy. No es posible registrar asistencias.",
                    'tipo' => "warning"
                ];
                header("Location: index.php?action=asistencia");
                exit();
            }
        }

        // Guardar o actualizar la sesión activa de clase/materia y bloque horario si viene en POST
        if (isset($_POST['materia'])) {
            $_SESSION['materia_actual'] = trim($_POST['materia']);
        }
        if (isset($_POST['bloque_horario'])) {
            $_SESSION['bloque_actual'] = trim($_POST['bloque_horario']);
        }
        if (isset($_POST['ficha_id'])) {
            $_SESSION['ficha_actual'] = trim($_POST['ficha_id']);
        }

        $materiaActual = $_SESSION['materia_actual'] ?? '';
        $bloqueActual  = $_SESSION['bloque_actual'] ?? '';

        // Recibo el código RFID o ID manual
        $trajoRfid     = isset($_POST['rfid_uid']) && !empty(trim($_POST['rfid_uid']));
        $tRajoIdManual = isset($_POST['id_aprendiz']) && !empty(trim($_POST['id_aprendiz']));

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($trajoRfid || $tRajoIdManual)) {
            $horaActual = date('H:i:s');
            $fechaActual = date('Y-m-d');
            $fechaHoraActual = $fechaActual . ' ' . $horaActual;

            $aprendizModel = new AprendizModel();
            $horarioModel  = new HorarioModel();
            $ingresoModel  = new IngresoModel();
            $datosAprendiz = null;

            if ($trajoRfid) {
                $codigoRfid = htmlspecialchars(trim($_POST['rfid_uid']));
                $datosAprendiz = $aprendizModel->obtenerAprendiz($codigoRfid);
            } elseif ($tRajoIdManual) {
                $idAprendizManual = intval($_POST['id_aprendiz']);
                if ($idAprendizManual > 0) {
                    $datosAprendiz = $aprendizModel->obtenerAprendizPorId($idAprendizManual);
                }
            }

            if ($datosAprendiz) {
                $identificadorAprendiz = intval($datosAprendiz['id_aprendiz']);
                $identificadorFicha    = intval($datosAprendiz['fk_ficha']);

                // ── VALIDACIÓN DE FICHA: Solo puede marcar en la ficha a la que pertenece ──
                $fichaSeleccionada = !empty($_POST['ficha_id']) ? intval($_POST['ficha_id']) : (!empty($_SESSION['ficha_actual']) ? intval($_SESSION['ficha_actual']) : 0);

                // Si no se obtuvo por ficha_id directo, intentar extraer el número de ficha del texto de la materia
                if (!$fichaSeleccionada && !empty($materiaActual)) {
                    if (preg_match('/(?:Ficha|ficha)\s*[:#-]?\s*([0-9]+)/i', $materiaActual, $coincidencias)) {
                        $fichaSeleccionada = intval($coincidencias[1]);
                    }
                }

                if ($fichaSeleccionada > 0 && $identificadorFicha !== $fichaSeleccionada) {
                    $nombreCompleto = trim(($datosAprendiz['nombre'] ?? '') . ' ' . ($datosAprendiz['apellido'] ?? ''));
                    $nombreMostrar  = !empty($nombreCompleto) ? $nombreCompleto : "El aprendiz (ID: {$identificadorAprendiz})";
                    $progAprendiz   = !empty($datosAprendiz['nombre_programa']) ? " ({$datosAprendiz['nombre_programa']})" : "";

                    $_SESSION['mensaje'] = [
                        'texto' => "Error de Ficha: {$nombreMostrar} pertenece a la Ficha {$identificadorFicha}{$progAprendiz}. No tiene permitido registrar asistencia en la Ficha {$fichaSeleccionada} seleccionada.",
                        'tipo'  => "error"
                    ];
                    header("Location: index.php?action=asistencia");
                    exit();
                }

                $horarioFicha = $horarioModel->obtenerHorarioFicha($identificadorFicha, $fechaActual);

                // Determinar franja/bloque horario
                $horarioEntrada = null;
                $horarioSalida = null;
                $nombreBloque = null;

                if (!empty($bloqueActual) && str_contains($bloqueActual, '|')) {
                    $partes = explode('|', $bloqueActual);
                    $horarioEntrada = $partes[0] ?? null;
                    $horarioSalida  = $partes[1] ?? null;
                    $nombreBloque   = $partes[2] ?? $bloqueActual;
                }

                if (!$horarioEntrada && $horarioFicha) {
                    $horarioEntrada = date('H:i:s', strtotime($horarioFicha['entrada']));
                }
                if (!$horarioSalida && $horarioFicha) {
                    $horarioSalida = date('H:i:s', strtotime($horarioFicha['salida']));
                }

                $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);
                $fkInstructor = ($usuarioIdSesion > 0) ? $usuarioIdSesion : null;

                if ($horarioEntrada || isset($_SESSION['hora_apertura_asistencia'])) {
                    // Verifica si este instructor ya registró al aprendiz hoy
                    $registroActual = $ingresoModel->verificarIngreso($identificadorAprendiz, $fechaActual, $fkInstructor);

                    if (!$registroActual) {
                        $estadoAsistencia = "Puntual";

                        // Si la ventana de 5 minutos fue iniciada por el instructor:
                        if (isset($_SESSION['hora_apertura_asistencia'])) {
                            $segundosTranscurridos = time() - (int)$_SESSION['hora_apertura_asistencia'];
                            if ($segundosTranscurridos <= 300) { // 5 minutos = 300 segundos
                                $estadoAsistencia = "Puntual";
                            } else {
                                $estadoAsistencia = "Retardo";
                            }
                        } else {
                            if ($horarioEntrada && $horaActual > $horarioEntrada) {
                                $estadoAsistencia = "Retardo";
                            }
                        }

                        $resultado = $ingresoModel->registrarEntrada(
                            $fechaActual,
                            $fechaHoraActual,
                            $estadoAsistencia,
                            $identificadorAprendiz,
                            $materiaActual,
                            $nombreBloque,
                            $fkInstructor
                        );

                        if ($resultado) {
                            $infoBloque = $nombreBloque ? " [$nombreBloque]" : "";
                            $infoMat = $materiaActual ? " [$materiaActual]" : "";
                            $_SESSION['mensaje'] = [
                                'texto' => "Entrada Registrada con éxito. Estado: {$estadoAsistencia}{$infoBloque}{$infoMat}",
                                'tipo' => "success"
                            ];
                        } else {
                            $_SESSION['mensaje'] = ['texto' => "Error de conexión al intentar guardar ", 'tipo' => "error"];
                        }
                    } elseif ($registroActual['salida'] == null) {
                        $estadoEntrada = $registroActual['estado_asistencia'];
                        $estadoDeSalida = "Salió a la hora correspondiente";
                        if ($horarioSalida && $horaActual < $horarioSalida) {
                            $horaActualConvertida = new DateTime($horaActual);
                            $horaSalidaConvertida = new DateTime($horarioSalida);
                            $tiempo = $horaActualConvertida->diff($horaSalidaConvertida);
                            $minutos = ($tiempo->h * 60) + $tiempo->i;
                            $estadoDeSalida = "Salió " . $minutos . " minutos antes.";
                        }
                        $estadoAsistenciaFinal = $estadoEntrada . "/" . $estadoDeSalida;
                        $idIngresoTabla = intval($registroActual['id_ingresos']);
                        $resultado = $ingresoModel->registrarSalida($idIngresoTabla, $fechaHoraActual, $estadoAsistenciaFinal);
                        if ($resultado) {
                            $_SESSION['mensaje'] = ['texto' => "Salida Registrada con éxito. Estado: " . $estadoDeSalida, 'tipo' => "success"];
                        } else {
                            $_SESSION['mensaje'] = ['texto' => "Error no se pudo actualizar la salida", 'tipo' => "error"];
                        }
                    } else {
                        $_SESSION['mensaje'] = ['texto' => "El aprendiz ya completó sus registros de entrada y salida con este instructor hoy", 'tipo' => "warning"];
                    }
                } else {
                    $_SESSION['mensaje'] = ['texto' => "No se encontró un horario asignado para la ficha hoy", 'tipo' => "warning"];
                }
            } else {
                $_SESSION['mensaje'] = ['texto' => "El código RFID o ID no se encuentra registrado en el sistema", 'tipo' => "error"];
            }
        } else {
            $_SESSION['mensaje'] = ['texto' => "Error datos de tarjeta incompletos", 'tipo' => "error"];
        }

        header("Location: index.php?action=asistencia");
        exit();
    }

    public function cerrarJornada()
    {
        unset($_SESSION['materia_actual'], $_SESSION['bloque_actual'], $_SESSION['hora_apertura_asistencia'], $_SESSION['ficha_actual']);
        $rolSesion = $_SESSION['rol'] ?? '';
        $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);
        $instructorId = ($rolSesion === 'Instructor') ? $usuarioIdSesion : null;

        $ingresoModel = new IngresoModel();
        $resultado = $ingresoModel->BorrarRegistros(null, $instructorId);
        if ($resultado['success']) {
            $_SESSION['mensaje'] = [
                'texto' => "Se limpiaron " . $resultado['eliminados'] . " registros de la jornada",
                'tipo' => "success"
            ];
        } else {
            $_SESSION['mensaje'] = [
                'texto' => "Error al limpiar los registros",
                'tipo' => "error"
            ];
        }
        header("Location: index.php?action=dashboard");
        exit();
    }
}
?>