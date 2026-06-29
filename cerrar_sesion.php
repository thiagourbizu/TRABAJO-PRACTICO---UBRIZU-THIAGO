<?php
// Retomamos la sesión actual
session_start();

// Vaciamos todas las variables
session_unset();

// Destruimos
session_destroy();

// Redirigimos al usuario al login
header("Location: ingreso.html");
exit();
?>