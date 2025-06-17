<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'voluntario') {
    header("Location: login.php");
    exit();
}

$idUsuario = new ObjectId($_SESSION['usuario']['_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
    $foto = $_FILES['foto_perfil'];

    if ($foto['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = uniqid() . "_" . basename($foto['name']);
        $rutaDestino = "../uploads/" . $nombreArchivo;

        if (move_uploaded_file($foto['tmp_name'], $rutaDestino)) {
            // Actualizar en la base de datos
            $database->usuarios->updateOne(
                ['_id' => $idUsuario],
                ['$set' => ['foto_perfil' => $nombreArchivo]]
            );

            // Actualizar la sesión si quieres que se refleje al instante
            $_SESSION['usuario']['foto_perfil'] = $nombreArchivo;

            header("Location: perfil.php?exito=1");
            exit();
        } else {
            echo "Error al mover el archivo.";
        }
    } else {
        echo "Error al subir la imagen.";
    }
} else {
    echo "No se recibió ningún archivo.";
}
?>
