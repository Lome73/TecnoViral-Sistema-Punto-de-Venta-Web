<?php
// Conexión compartida con el POS — misma base de datos
$servidor   = "localhost";
$usuario    = "root";
$password   = "";
$base_datos = "electronica_bd";

$conn = mysqli_connect($servidor, $usuario, $password, $base_datos);
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");
?>