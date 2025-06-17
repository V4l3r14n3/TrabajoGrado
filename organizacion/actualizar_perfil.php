<?php
session_start();
require '../conexion.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idUsuario = new ObjectId($_POST['id_usuario']);
    $nombre = trim($_POST['nombre']);
    $password = trim($_POST['password']);
    $organizacion = trim($_POST['organizacion']);
    $descripcion = trim($_POST['descripcion']);
    $telefono = trim($_POST['telefono']);
    $ciudad = trim($_POST['ciudad']);

    // Validación mínima
    if (empty($nombre) || empty($organizacion) || empty($descripcion)) {
        $_SESSION['error_message'] = "Todos los campos son obligatorios, excepto la contraseña.";
        header("Location: perfil.php");
        exit();
    }

    $coleccionUsuarios = $database->usuarios;
    $updateData = [
        'nombre' => $nombre,
        'organizacion' => $organizacion,
        'descripcion' => $descripcion,
        'telefono' => $telefono,
        'ciudad' => $ciudad
    ];

    // Si el usuario ingresa una nueva contraseña, la actualizamos con hash
    if (!empty($password)) {
        $updateData['password'] = password_hash($password, PASSWORD_BCRYPT);
    }

    $resultado = $coleccionUsuarios->updateOne(
        ['_id' => $idUsuario],
        ['$set' => $updateData]
    );

    if ($resultado->getModifiedCount() > 0) {
        $_SESSION['success_message'] = "Perfil actualizado correctamente.";
        $_SESSION['usuario']['nombre'] = $nombre;
        $_SESSION['usuario']['organizacion'] = $organizacion;
        $_SESSION['usuario']['descripcion'] = $descripcion;
        $_SESSION['usuario']['telefono'] = $telefono;
        $_SESSION['usuario']['ciudad'] = $ciudad;
    } else {
        $_SESSION['error_message'] = "No se realizaron cambios o hubo un error.";
    }

    header("Location: perfil.php");
    exit();
}
