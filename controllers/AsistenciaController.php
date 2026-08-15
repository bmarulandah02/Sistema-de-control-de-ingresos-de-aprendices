<?php 
//icio la conexion para el registro de asistencias 
//solicito los archivos que necesito para llevar acabo lo requerido 

require_once __DIR__ . '/../models/IngresoModel.php';
require_once __DIR__ . '/../models/AprendizModel.php';
require_once __DIR__ . '/../models/HorarioModel.php';

//genero la clase para recibir el codigo rfid
class AsistenciaController {
public function lecturaCodigoRfid()
{
    //recibo el codigo pero primero valido que la peticion traiga rfid_uid O id_aprendiz
    $trajoRfid     = isset($_POST['rfid_uid']) && !empty(trim($_POST['rfid_uid']));
    $tRajoIdManual = isset($_POST['id_aprendiz']) && !empty(trim($_POST['id_aprendiz']));

    if($_SERVER['REQUEST_METHOD']==='POST' && ($trajoRfid || $tRajoIdManual)){

        //capturo la fecha y la hora
        $horaActual=date('H:i:s');
        $fechaActual=date('Y-m-d');
        $fechaHoraActual=$fechaActual . ' ' .$horaActual;

        //instancio los modelos 
        $aprendizModel= new AprendizModel();
        $horarioModel= new HorarioModel();
        $ingresoModel= new IngresoModel();
        //busco la informacion del aprendiz por el codigo 
        $datosAprendiz=null;

        //lectura automatica por tarjeta rfid
        if($trajoRfid)
            {
                
                $codigoRfid=htmlspecialchars(trim($_POST['rfid_uid']));
                $datosAprendiz=$aprendizModel->obtenerAprendiz($codigoRfid);
                   //ingreso manual por el id del aprendiz
            }elseif($tRajoIdManual)
            {
                //sanitizo forzando que sea un numero entero, si mandan texto se vuelve 0
                $idAprendizManual=intval($_POST['id_aprendiz']);
                if($idAprendizManual > 0)
                    {
                        $datosAprendiz=$aprendizModel->obtenerAprendizPorId($idAprendizManual);
                    }
            }

        if($datosAprendiz)
            {
                $identificadorAprendiz=intval($datosAprendiz['id_aprendiz']);
                $identificadorFicha=intval($datosAprendiz['fk_ficha']);

                //busco el horario que le corresponde a la ficha el dia de hoy
                $horarioFicha=$horarioModel->obtenerHorarioFicha($identificadorFicha,$fechaActual);
                if($horarioFicha){
                    $registroActual=$ingresoModel->verificarIngreso($identificadorAprendiz,$fechaActual);

                    //verifico si no hay registro hoy, si no lo hay registro la entrada
                    if(!$registroActual)
                        {
                            $horarioEntrada=date('H:i:s',strtotime($horarioFicha['entrada']));
                            $estadoAsistencia="Puntual";
                            //si la hora actual es diferente de la hora de entrada es decir llega mas tarde 
                            if($horaActual>$horarioEntrada)
                                {
                                    $estadoAsistencia="Retardo";
                                }
                                //guardo la hora de entrada en la tabla de ingresos 
                                $resultado=$ingresoModel->registrarEntrada($fechaActual,$fechaHoraActual,$estadoAsistencia,$identificadorAprendiz);
                                if($resultado)
                                    {
                                       $_SESSION['mensaje'] = ['texto' => "Entrada Registrada con exito. Estado:" . $estadoAsistencia, 'tipo' => "success"];
                                    }else{
                                       $_SESSION['mensaje'] = ['texto' => "Error de conexion al intentar guardar ", 'tipo' => "error"];
                                    }

                        }elseif($registroActual['salida']==null)
                        {
                            $horaSalida = date('H:i:s', strtotime($horarioFicha['salida']));
                            $estadoEntrada=$registroActual['estado_asistencia'];
                            $estadoDeSalida="Salio a la hora correspondiente";
                            if($horaActual<$horaSalida)
                                {
                                    $horaActualConvertida= new DateTime($horaActual);
                                    $horaSalidaConvertida= new DateTime($horaSalida);
                                    $tiempo=$horaActualConvertida->diff($horaSalidaConvertida);
                                    $minutos=($tiempo->h *60)+ $tiempo->i;

                                    $estadoDeSalida="Salio ".$minutos. " minutos antes.";
                                }
                                $estadoAsistenciaFinal =$estadoEntrada . "/" . $estadoDeSalida;
                                $idIngresoTabla=intval($registroActual['id_ingresos']);
                                $resultado=$ingresoModel->registrarSalida($idIngresoTabla,$fechaHoraActual,$estadoAsistenciaFinal);
                                if($resultado)
                                    {
                                   $_SESSION['mensaje'] = ['texto' => "Salida Registrada con exito. Estado:" . $estadoDeSalida, 'tipo' => "success"];
                                    }else{
                                          $_SESSION['mensaje'] = ['texto' => "Error no se pudo actualizar la salida", 'tipo' => "error"];
                                    }
                        }
                        else{
                            $_SESSION['mensaje'] = ['texto' => "El aprendiz ya completo sus registros de entrada y salida de hoy ", 'tipo' => "warning"];
                        }
                }else{
                 $_SESSION['mensaje'] = ['texto' => "no se encontro un horario asignado para la ficha hoy ", 'tipo' => "warning"];

                }
            }else{
                 $_SESSION['mensaje'] = ['texto' => "El codigo RFID no se encuentra registrado en el sistema ", 'tipo' => "error"];

            }

    }else{
          $_SESSION['mensaje'] = ['texto' => "Error datos de tarjeta incompletos ", 'tipo' => "error"];
    }
    header("Location: index.php?action=asistencia");
    exit();

}
//funcionn para limpiar los registros que cumplieron su entrada y salida 
public function cerrarJornada()
{
    $ingresoModel= new IngresoModel();
    $resultado= $ingresoModel->BorrarRegistros();
    if($resultado['success'])
        {
            $_SESSION['mensaje']=[
                'texto'=>"Se limpiaron " . $resultado['eliminados'] . " registros",
                'tipo'=>"success"
            ];
        }else
        {
             $_SESSION['mensaje']=[
                'texto'=>"Error al limpiar los registros",
                'tipo'=>"error"
            ];
        }
        header("Location: index.php?action=dashboard");
        exit();
}
}
?>