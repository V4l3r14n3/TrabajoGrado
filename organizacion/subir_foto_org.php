<?php
session_start();
require '../conexion.php';

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'organizacion') {
    header("Location: login.php");
    exit();
}

$idUsuario = new ObjectId($_SESSION['usuario']['_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
    $archivo = $_FILES['foto_perfil'];
    $nombreArchivo = basename($archivo['name']);
    $extension = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
    $nuevoNombre = uniqid() . '.' . $extension;
    $rutaDestino = '../uploads/' . $nuevoNombre;

    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        // Actualizar en la base de datos
        $database->usuarios->updateOne(
            ['_id' => $idUsuario],
            ['$set' => ['foto_perfil' => $nuevoNombre]]
        );

        $_SESSION['success_message'] = 'Foto de perfil actualizada correctamente.';
    } else {
        $_SESSION['error_message'] = 'Hubo un error al subir la imagen.';
    }
}

header("Location: perfil.php"); // Asegúrate que este sea el nombre correcto del archivo
exit();
