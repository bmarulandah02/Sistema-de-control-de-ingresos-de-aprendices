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
        $instructorDestino = trim($_POST['instructor_id'] ?? 'auto');
        $ok = ExcusaModel::crearExcusa((int)$datosAprendiz['id_aprendiz'], $motivo, $fechaInicio, $fechaFin, $nombreArchivo, $instructorDestino);
        if ($ok) {
            header('Location: index.php?action=mis-excusas&ok=1');
        } else {
            header('Location: index.php?action=mis-excusas&error=' . urlencode('No fue posible guardar la excusa. INTÉNTELO DE NUEVO.'));
        }
            exit();
    }
    public function editar(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (($_SESSION['rol'] ?? '') !== 'Aprendiz')) {
        header('Location: index.php?action=403');
        exit();
    }

    $idUsuario = (int) ($_SESSION['usuario_id'] ?? 0);
    $idExcusa  = (int) ($_POST['id_excusa'] ?? 0);

    $excusa = ExcusaModel::obtenerPorId($idExcusa);

    if (!$excusa || (int) $excusa['id_usuario_aprendiz'] !== $idUsuario) {
        header('Location: index.php?action=403');
        exit();
    }
        $motivo      = trim($_POST['motivo'] ?? '');
    $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
    $fechaFin    = trim($_POST['fecha_fin'] ?? '');

    if ($motivo === '' || $fechaInicio === '' || $fechaFin === '') {
        header('Location: index.php?action=mis-excusas&error=' . urlencode('Debes completar el motivo y las fechas.'));
        exit();
    }

    if (strtotime($fechaFin) < strtotime($fechaInicio)) {
        header('Location: index.php?action=mis-excusas&error=' . urlencode('La fecha fin no puede ser anterior a la fecha inicio.'));
        exit();
    }
      if ($excusa['estado'] !== 'Pendiente') {
        header('Location: index.php?action=mis-excusas&error=' . urlencode('Solo puedes editar excusas que aún estén Pendientes.'));
        exit();
    }

    $nombreArchivo = null;
    if (!empty($_FILES['archivo']['name'])) {
        $nombreArchivo = $this->guardarArchivo($_FILES['archivo']);
        if ($nombreArchivo === false) {
            header('Location: index.php?action=mis-excusas&error=' . urlencode('El archivo debe ser PDF, JPG o PNG y pesar máximo 5MB.'));
            exit();
        }
    }

    $ok = ExcusaModel::editarExcusa($idExcusa, $motivo, $fechaInicio, $fechaFin, $nombreArchivo);

    if ($ok) {
        header('Location: index.php?action=mis-excusas&ok=2');
    } else {
        header('Location: index.php?action=mis-excusas&error=' . urlencode('No se pudo actualizar la excusa.'));
    }
    exit();

    }
    // Funciones de decisión de excusa médica para instructores
    public function aprobar(): void {
        $this->cambiarEstado('Aprobada');
    }

    public function rechazar(): void {
        $this->cambiarEstado('Rechazada');
    }

  private function cambiarEstado(string $estadoNuevo): void {
    if (($_SESSION['rol'] ?? '') !== 'Instructor' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?action=403');
        exit();
    }

    $idExcusa     = (int) ($_POST['id'] ?? 0);
    $idInstructor = (int) ($_SESSION['usuario_id'] ?? 0);
    $motivo       = mb_substr(trim((string) ($_POST['motivo'] ?? '')), 0, 500);

    // ── Verificación de propiedad (corrige el IDOR) ──
    if (!ExcusaModel::instructorPuedeGestionar($idExcusa, $idInstructor)) {
        header('Location: index.php?action=403');
        exit();
    }

    if ($estadoNuevo === 'Rechazada' && $motivo === '') {
        header('Location: index.php?action=reportes&tab=asistencia&error=' . urlencode('Debes indicar el motivo del rechazo.'));
        exit();
    }

    $ok = ExcusaModel::actualizarEstado($idExcusa, $estadoNuevo, $idInstructor, $motivo);

    if (!$ok) {
        header('Location: index.php?action=reportes&tab=asistencia&error=' . urlencode('No fue posible registrar tu decisión.'));
        exit();
    }

    $msg = ($estadoNuevo === 'Aprobada')
        ? 'Excusa médica aprobada correctamente para tus clases asignadas.'
        : 'Excusa médica rechazada. Se ha notificado el motivo al aprendiz.';
    header('Location: index.php?action=reportes&tab=asistencia&msg=' . urlencode($msg));
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




    /**
     * Descarga o visualiza de forma segura un archivo de soporte de excusa médica.
     * Solo permite el acceso al Administrador, al aprendiz dueño o al instructor asignado.
     */
    public function descargar(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
        $rol = $_SESSION['rol'] ?? '';

        if ($usuarioId <= 0 || empty($rol)) {
            header('Location: index.php?action=login');
            exit();
        }

        $archivo = basename(trim((string)($_GET['archivo'] ?? '')));
        if ($archivo === '') {
            header('Location: index.php?action=404');
            exit();
        }

        $rutaArchivo = realpath(self::CARPETA_UPLOADS . $archivo);
        $carpetaReal = realpath(self::CARPETA_UPLOADS);

        if (!$rutaArchivo || !file_exists($rutaArchivo) || !is_file($rutaArchivo) || strpos($rutaArchivo, $carpetaReal) !== 0) {
            header('Location: index.php?action=404');
            exit();
        }

        // Verificación de autorización
        $autorizado = false;

        if ($rol === 'Administrador') {
            $autorizado = true;
        } elseif ($rol === 'Aprendiz') {
            // El aprendiz solo puede ver sus propias excusas
            try {
                $mysql = new MySQL();
                $mysql->conectarBD();
                $conexion = $mysql->getConexion();
                if ($conexion) {
                    $stmt = $conexion->prepare("SELECT 1 FROM excusa e JOIN aprendiz a ON e.fk_aprendiz = a.id_aprendiz WHERE e.documento = :archivo AND a.fk_usuario = :uid LIMIT 1");
                    $stmt->execute([':archivo' => $archivo, ':uid' => $usuarioId]);
                    $autorizado = (bool)$stmt->fetchColumn();
                }
            } catch (Exception $e) {
                error_log("Error autorizando descarga de excusa para aprendiz: " . $e->getMessage());
            }
        } elseif ($rol === 'Instructor') {
            // El instructor solo puede ver excusas de aprendices que tiene a cargo o donde está asignado
            try {
                $mysql = new MySQL();
                $mysql->conectarBD();
                $conexion = $mysql->getConexion();
                if ($conexion) {
                    $stmt = $conexion->prepare("SELECT e.id_excusa FROM excusa e WHERE e.documento = :archivo LIMIT 1");
                    $stmt->execute([':archivo' => $archivo]);
                    $idExcusa = (int)$stmt->fetchColumn();
                    if ($idExcusa > 0) {
                        $autorizado = ExcusaModel::instructorPuedeGestionar($idExcusa, $usuarioId);
                    }
                }
            } catch (Exception $e) {
                error_log("Error autorizando descarga de excusa para instructor: " . $e->getMessage());
            }
        }

        if (!$autorizado) {
            header('Location: index.php?action=403');
            exit();
        }

        // Servir el archivo con el Content-Type adecuado
        $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        $tiposMime = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
        ];
        $contentType = $tiposMime[$ext] ?? 'application/octet-stream';

        header('Content-Type: ' . $contentType);
        header('Content-Disposition: inline; filename="' . $archivo . '"');
        header('Content-Length: ' . filesize($rutaArchivo));
        header('Cache-Control: private, max-age=3600');
        readfile($rutaArchivo);
        exit();
    }

}
