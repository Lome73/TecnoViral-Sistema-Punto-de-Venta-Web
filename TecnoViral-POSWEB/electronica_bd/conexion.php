<?php
$servidor = "localhost";
$usuario = "root"; // Cambiar según tu configuración
$password = ""; // Cambiar según tu configuración
$base_datos = "electronica_bd";

$conn = mysqli_connect($servidor, $usuario, $password, $base_datos);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Establecer charset para evitar problemas con caracteres especiales
mysqli_set_charset($conn, "utf8");
?>