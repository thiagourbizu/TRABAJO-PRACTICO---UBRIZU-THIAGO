<?php
// CREDENCIALES BASE 
$host = 'localhost';
$db   = 'mi_banco_db';
$user = 'root'; 
$pass = 'root'; 
$port = 3307; // 3307 porque tengo otra base en 3306

// Instanciamos la conexión
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Error crítico de conexión: " . $conn->connect_error);
}
// GENERAR TARJETA valida
function generarTarjetaLuhn($bin_inicial) {
    $tarjeta = (string)$bin_inicial;
    
    // Calculamos numero restantes
    $longitud_faltante = 15 - strlen($tarjeta);
    // Generamos los restantes al azar
    for ($i = 0; $i < $longitud_faltante; $i++) {
        $tarjeta .= rand(0, 9);
    }
    
    // Calculamos la suma Mod 10
    $suma = 0;
    for ($i = 0; $i < 15; $i++) {
        $digito = (int)$tarjeta[$i];
        
        // Las posiciones impares de la tarjeta se duplican
        if ($i % 2 === 0) {
            $digito *= 2;
            if ($digito > 9) {
                $digito -= 9;
            }
        }
        $suma += $digito;
    }
    
    // Calculamos el digito verificador
    $digito_verificador = (10 - ($suma % 10)) % 10;
    
    // Retornamos la tarjeta concatenada lista para usar
    return $tarjeta . $digito_verificador;
}


// POST del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipo_doc = $_POST['tipo_doc'];
    $documento = $_POST['documento'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $email = $_POST['email'];
    $usuario = $_POST['usuario'];
    
    // Passwords
    $passwordA = $_POST['passwordA']; 
    $passwordB = $_POST['passwordB']; 
    // Agregado del banco en el form para claridad
    $banco_seleccionado = $_POST['banco_seleccionado'];
    
    // PASSWORDS iguales
    if ($passwordA !== $passwordB) {
        die("Error: Las contraseñas ingresadas no coinciden. Vuelve atrás e inténtalo de nuevo.");
    }
    
    // TIPO doc
    if ($tipo_doc !== 'DNI' && $tipo_doc !== 'PASAPORTE') {
        die("Error de seguridad: Tipo de documento no permitido.");
    }
    
    // SI LLEGAMOS ACA = INSERCIÓN DE USUARIO
    $sql_usuario = "INSERT INTO usuarios (documento, tipo_doc, nombre, apellido, fecha_nacimiento, email, usuario, password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt_usu = $conn->prepare($sql_usuario);
    $stmt_usu->bind_param("ssssssss", $documento, $tipo_doc, $nombre, $apellido, $fecha_nacimiento, $email, $usuario, $passwordA);
    if ($stmt_usu->execute()) {
    
        // BIN inicial de parametro
        $numero_tarjeta = generarTarjetaLuhn('4512');
        
        // Insertamos la tarjeta asociando el banco capturado
        $sql_tarjeta = "INSERT INTO tarjetas (numero_tarjeta, banco_emisor, estado, saldo, dni_titular) 
                        VALUES (?, ?, 'Activa', 0.00, ?)";
                        
        $stmt_tarj = $conn->prepare($sql_tarjeta);
        $stmt_tarj->bind_param("sss", $numero_tarjeta, $banco_seleccionado, $documento);
        
        if ($stmt_tarj->execute()) {
            echo "<h3>¡Registro exitoso!</h3>";
            echo "<p>Tu cuenta ha sido creada y se te ha asignado la tarjeta número: <strong>$numero_tarjeta</strong>.</p>";
            echo "<a href='ingreso.html'>Ir al Login</a>";
        } else {
            echo "Error al asignar la tarjeta: " . $conn->error;
        }
        
        $stmt_tarj->close();
        
    } else {
        // Si falla el primer INSERT (DNI o Email ya existen en la base)
        echo "<h3>Error en el registro</h3>";
        echo "<p>El documento o el correo electrónico ya se encuentran registrados en el sistema.</p>";
        echo "<a href='registro.html'>Volver a intentar</a>";
    }
    
    $stmt_usu->close();
}
$conn->close();
?>