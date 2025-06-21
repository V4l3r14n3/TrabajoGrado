<?php
require 'conexion.php';
session_start();

if (isset($_SESSION['usuario'])) {
    $email = $_SESSION['usuario']['email'];
    $pregunta = $_POST['pregunta'];
    $respuesta = password_hash($_POST['respuesta'], PASSWORD_DEFAULT);

    $database->usuarios->updateOne(
        ['email' => $email],
        ['$set' => ['pregunta_seguridad' => $pregunta, 'respuesta_seguridad' => $respuesta]]
    );

    header("Location: index.php?mensaje=pregunta_guardada");
}
?>
