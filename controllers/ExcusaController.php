<?php 
//control para el gestionamiento de las excusas 
require_once __DIR__ . '/../models/ExcusaModel.php';
require_once __DIR__ . '/../models/AprendizModel.php';
class ExcusaController{
    //funcion para subir la excusa
    public function subir(): void{
        //validamos el metodo del servidor 
        if($_SERVER['REQUEST_METHOD']!=='POST' || (($_SESSION['rol']?? '')!=='Aprendiz')){
            header('Location: index.php?action=403');
            exit();
        }
        //obtengo el id de quien esta conectado 
        $idUsuario=(int)($_SESSION['usuario_id'] ?? 0);
        //el (int) es un metodo llamado casting que obliga a que el id sea un numero entero 
        $datosAprendiz= AprendizModel::obtenerPorUsuarioId($idUsuario);
        if(!$datosAprendiz || empty($datosAprendiz['id_aprendiz'])){
             header('Location: index.php?action=mis-excusas&error='. urldecode('No ae encontro un registro de aprendiz.'));
            exit();

        }
    }

}
