<?php 
//creo la conexion a la base de datos CON UNA CLASE
class MySQL{
    //creo esta variable para almacenar la conexion con PDO

    private $conexion;
    //establesco la conexion
    public function conectarBD(){
        $host='localhost';
        $dbname='control_ingreso_aprendices';
        $usuario='root';
        $contrasena="";
        //data source name (linea que contiene el nombre origen de datos)
        $dsn="mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        try{
            $this->conexion=new PDO($dsn,$usuario,$contrasena);
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        }catch (PDOException $e){
            $this->conexion=null;
        }
     }
     //esta la utilizo por si la conexion suele ser privada 
     public function getConexion()
     {
        return $this->conexion;
     }
    //cierro la conecion con el siguiente metodo
    public function desconectar(){
        $this->conexion=null;
    }
    

}



?>