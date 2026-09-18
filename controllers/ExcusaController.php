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
             header('Location: index.php?action=mis-excusas&error='. urlencode('No se encontro un registro de aprendiz.'));
            exit();

        }
        //leo los textos de campos del formulario 
        $motivo= trim($_POST['motivo']?? '');
        $fechaInicio= trim($_POST['fecha_inicio']?? '');
        $fechaFin= trim($_POST['fecha_fin']?? '');
        if($motivo==='' || $fechaInicio==='' || $fechaFin==='')
            {
                header('Location: index.php?action=mis-excusas&error=' . urlencode('Debes completar el motivo y las fechas.'));
                exit();
            }
            //strtotime convierte una fecha escrita como texto a numero para poder comparar
        if (strtotime($fechaFin) < strtotime($fechaInicio)) {
            header('Location: index.php?action=mis-excusas&error=' . urlencode('La fecha fin no puede ser anterior a la fecha inicio.'));
             exit();
            }
            $nombreArchivo=null;
            if(!empty($_FILES['archivo']['name'])){
                $nombreArchivo=$this->guardarArchivo($_FILES['archivo']);
                if($nombreArchivo===false){
                     header('Location: index.php?action=mis-excusas&error=' . urlencode('El archivo debe ser PDF, JPG o PNG y pesar máximo 5MB.'));
                exit();
                }
            }else{
            header('Location: index.php?action=mis-excusas&error=' . urlencode('Debes adjuntar un documento de soporte.'));
            exit();
            }
            $ok=ExcusaModel::crearExcusa((int)$datosAprendiz['id_aprendiz'],$motivo,$fechaInicio,$fechaFin,$nombreArchivo);
            if($ok){
                header('Location: index.php?action=mis-excusas&ok=1');

            }else{
                header('Location: index.php?action=mis-excusas&error='.urlencode('No fue posible guardar la excusa. INTENTELO DE NUEVO!!!'));
            }
            exit();
    }
    private const CARPETA_UPLOADS = __DIR__ . '/../public/uploads/excusas/';
    private const EXTENSIONES_PERMITIDAS = ['pdf', 'jpg', 'jpeg', 'png'];
    private const TAMANO_MAXIMO = 5 * 1024 * 1024; // 5 MB en bytes
    private function guardarArchivo(array $archivo){
        //aqui se avisa si hay algun tipo de error
        if($archivo['error']!==UPLOAD_ERR_OK)
            {
                return false;
            }
        //verifico que el archivo no supere el tamaño en bytes 
        if($archivo['size'] > self::TAMANO_MAXIMO)
            {
                return false;

            }
            //pathinfo desgloza el archivo en partes 
            //PATHINFO_EXTENSION esto lo que hace es, le dice al programa no lo desgloces todo solo dame la extencion
            //strtolower convierte todo el texto a minusculas para evitar problesma en la validacion del archivo 
         $extension=strtolower(pathinfo($archivo['name'],PATHINFO_EXTENSION));
    //in_array pregunta si el valor esta dentro de una lista y devuelve verdadero o falso 
         if(!in_array($extension,self::EXTENSIONES_PERMITIDAS,true)){
            return false;
        }
        //este bloque crea la carpeta si no existe 
        if(! is_dir(self::CARPETA_UPLOADS)){
            mkdir(self::CARPETA_UPLOADS,0775,true);
        }
        //genero un nombre unico para el archivo
        //uniqid genera un texto unico basado en la hora actual haciendo imposible que se repita 2 veces
        //time me da el numero de segundo actuales
        //no utilizamos directamente el nombre del archib¿v porque evita que se sobreescriban archivos
        // y evitamos algun tipo de inseguridad porque el nombre proviene del usuario
        $nombreFinal='excusa_' . uniqid() . '_'. time().'.'. $extension;
        $rutaDestino=self::CARPETA_UPLOADS . $nombreFinal;

        if(!move_uploaded_file($archivo['tmp_name'],$rutaDestino)){
            return false;
        }
        return $nombreFinal;


    }



}
