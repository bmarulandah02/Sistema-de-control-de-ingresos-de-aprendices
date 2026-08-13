<?php 
//icio la conexion para el registro de asistencias 
//solicito los archivos que necesito para llevar acabo lo requerido 

require_once '../models/IngresoModel.php';
require_once '../models/AprendizModel.php';
require_once '../models/HorarioModel.php';

//genero la clase para recibir el codigo rfid
class AsistenciaController {
public function lecturaCodigoRfid()
{
    //recibo el codigo pero primero valido que la avriable y la peticion no esten vacias 

    if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['rfid']) && !empty($_POST['rfid'])){
        //sanatizo las variables eliminando espacios
        $rfid=htmlspecialchars(trim($_POST['rfid']));
        //capturo la fecha y la hora
        $horaActual=date('H:i:s');
        $fechaActual=date('y-m-d');

        //instancio los modelos 
        $aprendizModel= new AprendizModel();
        $horarioModel= new HorarioModel();
        $ingresoModel= new IngresoModel();
        //busco la informacion del aprendiz por el codigo 
        $datosAprendiz=$aprendizModel->obtenerAprendiz($rfid);

        if($datosAprendiz)
            {
                $identificadorAprendiz=intval($datosAprendiz['id_aprendiz']);
                $identificadorFicha=intval('fk_ficha');

                //busco el horario que le corresponde a la ficha el dia de hoy
                $horarioFicha=$horarioModel->obtenerHorarioFicha($identificadorFicha,$fechaActual);
                if($horarioFicha){
                    $registroActual=$ingresoModel->verificarIngreso($identificadorAprendiz,$fechaActual);



                    //verifico si no hay registro hoy, si no lo hay registro la entrada
                    if(!$registroActual)
                        {
                            $horarioEntrada=$horarioFicha['entrada'];
                            $tipoAsistencia="LLego puntual";
                        }
                }
            }

        
    }

}
}




?>