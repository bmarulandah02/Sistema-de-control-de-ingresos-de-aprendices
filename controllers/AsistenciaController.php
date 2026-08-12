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
        
    }

}
}




?>