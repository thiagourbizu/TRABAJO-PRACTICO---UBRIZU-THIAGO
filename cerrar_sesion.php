<?php
// 1. Retomamos la sesión actual
session_start();

// 2. Vaciamos todas las variables de sesión
session_unset();

// 3. Destruimos la sesión en el servidor
session_destroy();

// 4. Redirigimos al usuario al login
header("Location: ingreso.html");
exit();
?>