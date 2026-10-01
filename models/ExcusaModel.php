<?php
// ──────────────────────────────────────────────
//  models/ExcusaModel.php — Modelo de Excusas Médicas
// ──────────────────────────────────────────────

require_once __DIR__ . '/../config/database.php';

class ExcusaModel {
    //esta funcion la estoy utilizando para agregar columnas y modificarlas en las tablas de excusas directamente en la base de datos 
    //si algo ya existe lo ignora y sigue gi
private static function asegurarColumnas($conexion): void {
    try { $conexion->exec("ALTER TABLE excusa MODIFY COLUMN observacion VARCHAR(500) NULL"); } catch (Exception $e) {}
    try { $conexion->exec("ALTER TABLE excusa ADD COLUMN fecha_inicio DATE NULL AFTER observacion"); } catch (Exception $e) {}
    try { $conexion->exec("ALTER TABLE excusa ADD COLUMN fecha_fin DATE NULL AFTER fecha_inicio"); } catch (Exception $e) {}
    try { $conexion->exec("ALTER TABLE excusa ADD COLUMN fk_aprendiz INT NULL AFTER fk_ingreso"); } catch (Exception $e) {}
    try { $conexion->exec("ALTER TABLE excusa ADD COLUMN fecha_registro DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER fk_usuario_instructor"); } catch (Exception $e) {}
    try { $conexion->exec("ALTER TABLE excusa ADD COLUMN fecha_revision DATETIME NULL AFTER fecha_registro"); } catch (Exception $e) {}
    try { $conexion->exec("ALTER TABLE excusa MODIFY COLUMN fk_ingreso INT NULL"); } catch (Exception $e) {}
    try { $conexion->exec("ALTER TABLE excusa MODIFY COLUMN fk_usuario_instructor INT NULL"); } catch (Exception $e) {}
}


    /**
     * Obtiene el listado de excusas médicas registradas
     */
    public static function obtenerTodas(): array {
        $excusas = [];

        try {
            $mysql = new MySQL();
            $mysql->conectarBD();
            $conexion = $mysql->getConexion();

            if ($conexion) {
                //con esto garantizo que las columnas de la tabla existan si no existen llamo la funcion para crear las que no 
                 self::asegurarColumnas($conexion);  //aqui digo que la funcion vive en esa misma clase 
                $sql = "SELECT e.id_excusa, e.documento, e.observacion, e.fecha_inicio, e.fecha_fin, e.estado,
                 CONCAT(u.nombre, ' ', u.apellido) AS aprendiz, u.identificacion AS documento_aprendiz,
                 a.fk_ficha AS numero_ficha
                 FROM excusa e
                 JOIN aprendiz a ON e.fk_aprendiz = a.id_aprendiz
                 JOIN usuario u ON a.fk_usuario = u.id_usuario
                 ORDER BY e.id_excusa DESC";

                $stmt = $conexion->query($sql);
              while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                 $excusas[] = [
                     'id'           => $row['id_excusa'],
                     'aprendiz'     => !empty(trim($row['aprendiz'])) ? $row['aprendiz'] : $row['documento_aprendiz'],
                     'documento'    => $row['documento_aprendiz'],
                     'numero_ficha' => $row['numero_ficha'] ?? 'N/A',
                     'motivo'       => $row['observacion'] ?? 'Excusa médica',
                     'fecha_inicio' => $row['fecha_inicio'],
                     'fecha_fin'    => $row['fecha_fin'],
                     'archivo'      => $row['documento'] ?? '',
                     'estado'       => $row['estado'] ?? 'Pendiente',
                     'created_at'   => $row['fecha_inicio']
                 ];
                }
            }
        } catch (Exception $e) {
            // Devuelve arreglo vacío si falla
        }

        return $excusas;
    }

    //filtro para que cada aprendiz vea solo sus excusas 

    public static function obtenerPorAprendiz(int $idUsuarioAprendiz): array {
        $excusas=[];
        try{
            $mysql= new MySQL();
            $mysql->conectarBD();
            $conexion=$mysql->getConexion();
            self::asegurarColumnas($conexion);
            $sql="SELECT e.id_excusa, e.documento, e.observacion, e.fecha_inicio, e.fecha_fin, e.estado,
                           CONCAT(u.nombre, ' ', u.apellido) AS aprendiz, u.identificacion AS documento_aprendiz,
                           a.fk_ficha AS numero_ficha
                    FROM excusa e
                    JOIN aprendiz a ON e.fk_aprendiz = a.id_aprendiz
                    JOIN usuario u ON a.fk_usuario = u.id_usuario
                    WHERE u.id_usuario = :idUsuario
                    ORDER BY e.id_excusa DESC";
            $stmt=$conexion->prepare($sql);
            $stmt->execute([':idUsuario'=>$idUsuarioAprendiz]);
            while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
                $excusas[]=[
                     'id'           => $row['id_excusa'],
                    'aprendiz'     => !empty(trim($row['aprendiz'])) ? $row['aprendiz'] : $row['documento_aprendiz'],
                    'documento'    => $row['documento_aprendiz'],
                    'numero_ficha' => $row['numero_ficha'] ?? 'N/A',
                    'motivo'       => $row['observacion'] ?? 'Excusa médica',
                    'fecha_inicio' => $row['fecha_inicio'],
                    'fecha_fin'    => $row['fecha_fin'],
                    'archivo'      => $row['documento'] ?? '',
                    'estado'       => $row['estado'] ?? 'Pendiente',
                    'created_at'   => $row['fecha_inicio']
                ];
            }
        }catch(Exception $e)
        {
            //devuelvo el arreglo vacio si algo falla

        }
        return $excusas;
    }
    //genero la funcion que guarda la excusa
    //registra una nueva exusa medica para el aprendiz indicado 
    public static function crearExcusa(int $idAprendiz, string $motivo,string $fechaInicio, string $fechaFin,?string $archivo): bool{

    try{ 
    $mysql= new MySQL();
    $mysql->conectarBD();
    $conexion=$mysql->getConexion();
    if($conexion){
        self::asegurarColumnas($conexion);
        $sql="INSERT INTO excusa(documento,observacion,fecha_inicio,fecha_fin,estado,fk_aprendiz, fecha_registro)
        values (:documento,:observacion,:fecha_inicio,:fecha_fin,'Pendiente',:fk_aprendiz,NOW())";

        $stmt=$conexion->prepare($sql);
        return $stmt->execute([
            ':documento'=> $archivo,
            ':observacion'=> $motivo,
            ':fecha_inicio'=> $fechaInicio,
            ':fecha_fin'=> $fechaFin,
            ':fk_aprendiz'=> $idAprendiz
            

        ]);
    }

    }
    catch(Exception $e){
        error_log("Error al crear excusa: " . $e->getMessage());
        

    }
    return false;
    }

    //obtengo una excusa por id al que le pertenece es decir el id del aprendiz
public static function obtenerPorId(int $idExcusa): ?array {
    try {
        $mysql = new MySQL();
        $mysql->conectarBD();
        $conexion = $mysql->getConexion();

        if ($conexion) {
            self::asegurarColumnas($conexion);

            $sql = "SELECT e.id_excusa, e.observacion, e.fecha_inicio, e.fecha_fin, e.estado,
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
    //funcion para editar la excusa solo se puede editar si el estado se encuentra en pendiente si no no se puede hacer ningun cambio si el 
    //instructor ya la aprobo o rechazo, solo el aprendiz dueño de la eexcusa la podra editar 
    public static function editarExcusa(int $idExcusa,string $motivo, string $fechaInicio, string $fechaFin, ?string $nuevoArchivo = null): bool{
        
    try{
    $mysql= new MySQL();
    $mysql->conectarBD();
    $conexion=$mysql->getConexion();
    if($conexion){
        self::asegurarColumnas($conexion);
        $sql="UPDATE excusa set observacion= :observacion,fecha_inicio= :fecha_inicio,fecha_fin= :fecha_fin "
        .($nuevoArchivo ? ", documento=:documento ": "")
        . " where id_excusa=:id and estado='Pendiente' ";
        $parametros=[
                ':observacion'  => $motivo,
                ':fecha_inicio' => $fechaInicio,
                ':fecha_fin'    => $fechaFin,
                ':id'           => $idExcusa
                ];

        if($nuevoArchivo){
            $parametros[':documento']=$nuevoArchivo;
        }
         $stmt = $conexion->prepare($sql);
           return $stmt->execute($parametros);
            
    }

    }catch(Exception $e){
    error_log("Error al editar excusa: " . $e->getMessage());
    }
    return false;
    }
    //funcion para que el instructor solo pueda ver las excusas el cual pertenecen a los aprendices de su ficha
    public static function obtenerPorInstructor(int $idInstructor, ?string $estado=null):array{
        $excusas=[];
        try{
            $mysql= new MySQL();
            $mysql->conectarBD();
            $conexion=$mysql->getConexion();
            if($conexion){
                self::asegurarColumnas($conexion);
                    $sql = "SELECT e.id_excusa, e.documento, e.observacion, e.fecha_inicio, e.fecha_fin, e.estado,
                           CONCAT(u.nombre, ' ', u.apellido) AS aprendiz, u.identificacion AS documento_aprendiz,
                           a.fk_ficha AS numero_ficha
                    FROM excusa e
                    JOIN aprendiz a ON e.fk_aprendiz = a.id_aprendiz
                    JOIN usuario u ON a.fk_usuario = u.id_usuario
                    JOIN ficha f ON a.fk_ficha = f.id_ficha
                    WHERE f.fk_usuario = :idInstructor";
                    $parametros=[':idInstructor'=>$idInstructor];
                    if(!empty($estado)){
                        $sql.=" AND e.estado=:estado";
                        $parametros[':estado']=$estado;
                    }
                    $sql.=" ORDER BY e.id_excusa DESC";
                    $stmt= $conexion->prepare($sql);
                    $stmt->execute($parametros);
                    while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
                     $excusas[] = [
                    'id'           => $row['id_excusa'],
                    'aprendiz'     => !empty(trim($row['aprendiz'])) ? $row['aprendiz'] : $row['documento_aprendiz'],
                    'documento'    => $row['documento_aprendiz'],
                    'numero_ficha' => $row['numero_ficha'] ?? 'N/A',
                    'motivo'       => $row['observacion'] ?? 'Excusa médica',
                    'fecha_inicio' => $row['fecha_inicio'],
                    'fecha_fin'    => $row['fecha_fin'],
                    'archivo'      => $row['documento'] ?? '',
                    'estado'       => $row['estado'] ?? 'Pendiente',
                    'created_at'   => $row['fecha_inicio']
                ];
            
                    }
            }

        }catch(Exception $e){

        }
        return $excusas;  
    }
//actualizo el estado de la excusa esta es la funcion que me permite cambiar el estado si fue aprobada o rechazada y deja la constancia que instructor lo decidio y cuando lo hizo
public static function actualizarEstado(int $idExcusa,string $estado, int $idInstructor): bool{
    if(!in_array($estado,['Aprobada','Rechazada'],true)){
        return false;
    }
    try{
        $mysql= new MySQL();
        $mysql->conectarBD();
        $conexion=$mysql->getConexion();
        if($conexion){
            self::asegurarColumnas($conexion);
            $sql="UPDATE excusa SET estado=:estado,fk_usuario_instructor=:instructor,fecha_revision=now() WHERE id_excusa=:id";
            $stmt=$conexion->prepare($sql);
            return $stmt->execute([
                ':estado'=>$estado,
                ':instructor'=>$idInstructor,
                ':id'=>$idExcusa
            ]);

        }
       


    }catch(Exception $e){
        error_log("Error al actualizar el estado de excusa: ". $e->getMessage());
        
    }
    return false;

}


}
