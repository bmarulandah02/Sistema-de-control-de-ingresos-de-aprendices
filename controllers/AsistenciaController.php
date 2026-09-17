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
        } else {
            $fichas = HorarioModel::obtenerTodasFichas();
        }

        $bloques = HorarioModel::obtenerTodosLosBloques();
        if (!$mensaje && isset($_SESSION['mensaje'])) {
            $mensaje = $_SESSION['mensaje'];
            unset($_SESSION['mensaje']);
        }
        require __DIR__ . '/../views/asistencia/registro.php';
    }

    public function abrirVentanaAsistencia(): void {
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
        // Guardar o actualizar la sesión activa de clase/materia y bloque horario si viene en POST
        if (isset($_POST['materia'])) {
            $_SESSION['materia_actual'] = trim($_POST['materia']);
        }
        if (isset($_POST['bloque_horario'])) {
            $_SESSION['bloque_actual'] = trim($_POST['bloque_horario']);
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

                if ($horarioEntrada || isset($_SESSION['hora_apertura_asistencia'])) {
                    $registroActual = $ingresoModel->verificarIngreso($identificadorAprendiz, $fechaActual);

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
                            $nombreBloque
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
                        $_SESSION['mensaje'] = ['texto' => "El aprendiz ya completó sus registros de entrada y salida de hoy", 'tipo' => "warning"];
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
        unset($_SESSION['materia_actual'], $_SESSION['bloque_actual'], $_SESSION['hora_apertura_asistencia']);
        $ingresoModel = new IngresoModel();
        $resultado = $ingresoModel->BorrarRegistros();
        if ($resultado['success']) {
            $_SESSION['mensaje'] = [
                'texto' => "Se limpiaron " . $resultado['eliminados'] . " registros",
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