<?php
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


// POST del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipo_doc = $_POST['tipo_doc'];
    $documento = $_POST['documento'];
    $usuario = $_POST['usuario'];
    $passwordA = $_POST['passwordA']; 
    $passwordB = $_POST['passwordB']; 
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    
    // PASSWORDS iguales
    if ($passwordA !== $passwordB) {
        die("Error: Las contraseñas ingresadas no coinciden. Vuelve atrás e inténtalo de nuevo.");
    }
    
    // TIPO doc
    if ($tipo_doc !== 'DNI' && $tipo_doc !== 'PASAPORTE') {
        die("Error de seguridad: Tipo de documento no permitido.");
    }
    
    // VALIDACION DE ACTIVO
    $sql_check = "SELECT activo, usuario FROM usuarios WHERE documento = ? AND tipo_doc = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $documento, $tipo_doc);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();

    // Verificamos si YA FUE DADO DE ALTA REALMENTE C#
    if ($resultado_check->num_rows === 0) {
        echo "<h3>Error en la activación</h3>";
        echo "<p>El documento ingresado no existe en los registros del banco. Diríjase a una sucursal.</p>";
        echo "<a href='registro.html'>Volver a intentar</a>";
        exit();
    }

    $fila = $resultado_check->fetch_assoc();
    $stmt_check->close();

    // Verificamos si la cuenta está dada de baja (activo = 0)
    if ($fila['activo'] == 0) {
        echo "<h3>Cuenta Inactiva</h3>";
        echo "<p>Su cuenta fue desactivada por el banco. No puede generar credenciales web.</p>";
        echo "<a href='ingreso.html'>Ir al Login</a>";
        exit();
    }

    // Verificamos si ya había sido activada antes (usuario no es NULL)
    if (!is_null($fila['usuario'])) {
        echo "<h3>Cuenta ya activada</h3>";
        echo "<p>Esta cuenta web ya fue activada previamente.</p>";
        echo "<a href='ingreso.html'>Ir al Login</a>";
        exit();
    }

    // AHORA SI, insertamos sabiendo que validamos los posibles casos
    $sql_activacion = "UPDATE usuarios 
                       SET usuario = ?, password = ?, fecha_nacimiento = ? 
                       WHERE documento = ? AND tipo_doc = ?";
            
    $stmt = $conn->prepare($sql_activacion);
    $stmt->bind_param("sssss", $usuario, $passwordA, $fecha_nacimiento, $documento, $tipo_doc);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "<h3>¡Activación exitosa!</h3>";
            echo "<p>Tu cuenta web ha sido activada y tus datos actualizados correctamente. Ya puedes acceder al panel de control.</p>";
            echo "<a href='ingreso.html'>Ir al Login</a>";
        } else {
            echo "Error inesperado al intentar actualizar el registro.";
        }
    } else {
        echo "Error al ejecutar la activación: " . $conn->error;
    }
    
    $stmt->close();
}

$conn->close();
?>