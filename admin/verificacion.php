<?php
require '../conexion.php';
require '../vendor/autoload.php';
session_start();
use MongoDB\BSON\ObjectId;

// Verificar si el usuario es un administrador
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["tipo"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id"], $_POST["accion"])) {
    $organizacion_id = $_POST["id"];
    $accion = $_POST["accion"];
    $coleccion = $database->usuarios;

    if ($accion === "aprobar") {
        $coleccion->updateOne(
            ["_id" => new ObjectId($organizacion_id)],
            ['$set' => [
                "verificado" => true,
                "estado_verificacion" => "aprobado"
            ]]
        );
    } elseif ($accion === "rechazar") {
        $coleccion->updateOne(
            ["_id" => new ObjectId($organizacion_id)],
            ['$set' => [
                "verificado" => false,
                "estado_verificacion" => "rechazado"
            ]]
        );
    }

    header("Location: index.php");
    exit();
}
?>
