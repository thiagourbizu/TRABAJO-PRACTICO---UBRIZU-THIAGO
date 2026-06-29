<?php
// motor de SECIONES de php
session_start();

// CREDENCIALES BASE 
$host = 'localhost';
$db   = 'mi_banco_db';
$user = 'root'; 
$pass = 'root'; 
$port = 3306; // 3307 porque tengo otra base en 3306

// Instanciamos la conn
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Error crítico de conexión: " . $conn->connect_error);
}


// capturamos la info del form de ingreso.html
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $tipo_doc = $_POST['tipo_doc'];
    $documento = $_POST['documento'];
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    
    // AGREGAMOS ACTIVO AL SELECT
    $sql = "SELECT nombre, apellido, activo FROM usuarios 
            WHERE tipo_doc = ? AND documento = ? AND usuario = ? AND password = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $tipo_doc, $documento, $usuario, $password);
    $stmt->execute();
    
    // resultado de la consulta
    $resultado = $stmt->get_result();
    
    // validamos sesion
    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        
        // validamos si es activo.
        if ($fila['activo'] == 0) {
            echo "<h3>Cuenta Inactiva</h3>";
            echo "<p>Su cuenta fue desactivada por el banco. No puede acceder al sistema web.</p>";
            echo "<a href='ingreso.html'>Volver al Login</a>";
            exit();
        }
        
        // variables para durante la SESION
        $_SESSION['logueado'] = true;
        $_SESSION['documento'] = $documento;
        $_SESSION['nombre_completo'] = $fila['nombre'] . ' ' . $fila['apellido'];
        
        // Redirigimos al resumen.php
        header("Location: resumen.php");
        exit();
    } else {
        echo "<h3>Acceso Denegado</h3>";
        echo "<p>Los datos ingresados son incorrectos. Por favor, verifica tu información.</p>";
        echo "<a href='ingreso.html'>Volver al Login</a>";
    }
    
    $stmt->close();
}

$conn->close();
?>