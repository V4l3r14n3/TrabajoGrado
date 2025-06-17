<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUsuario = new ObjectId($_POST['id_usuario']);
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'] ?? '';
    $ciudad = $_POST['ciudad'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $password = $_POST['password'];
    $habilidades = $_POST['habilidades'] ?? '';
    $disponibilidad = $_POST['disponibilidad'] ?? '';
    $intereses = $_POST['intereses'] ?? [];

    // Verificar si el teléfono ya existe para otro usuario
    if (!empty($telefono)) {
        $existeTelefono = $database->usuarios->findOne([
            'telefono' => $telefono,
            '_id' => ['$ne' => $idUsuario]
        ]);

        if ($existeTelefono) {
            $_SESSION['error_message'] = 'Este número de teléfono ya está registrado en otro usuario.';
            header('Location: perfil.php');
            exit();
        }
    }


    $updateFields = [
        'nombre' => $nombre,
        'habilidades' => $habilidades,
        'disponibilidad' => $disponibilidad,
        'intereses' => $intereses,
        'telefono' => $telefono,
        'ciudad' => $ciudad,
        'descripcion' => $descripcion
    ];

    if (!empty($password)) {
        $updateFields['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $database->usuarios->updateOne(
        ['_id' => $idUsuario],
        ['$set' => $updateFields]
    );

    $_SESSION['success_message'] = 'Perfil actualizado correctamente.';
    header('Location: perfil.php');
    exit();
}
