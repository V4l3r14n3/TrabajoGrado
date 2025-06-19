<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUsuario = new ObjectId($_POST['id_usuario']);
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $password = $_POST['password'];
    $habilidades = trim($_POST['habilidades'] ?? '');
    $disponibilidad = $_POST['disponibilidad'] ?? '';
    $intereses = $_POST['intereses'] ?? [];

    // Validaciones básicas
    if (!empty($password) && strlen($password) < 8) {
        $_SESSION['error_message'] = 'La contraseña debe tener al menos 8 caracteres.';
        header('Location: perfil.php');
        exit();
    }

    if (!empty($telefono) && !preg_match('/^\+?\d{7,15}$/', $telefono)) {
        $_SESSION['error_message'] = 'El teléfono debe contener solo números y puede incluir un "+" al inicio.';
        header('Location: perfil.php');
        exit();
    }

    // Validar si el teléfono ya está en uso por otro usuario
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

    // Construir campos a actualizar
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

    // Ejecutar actualización
    $database->usuarios->updateOne(
        ['_id' => $idUsuario],
        ['$set' => $updateFields]
    );

    $_SESSION['success_message'] = 'Perfil actualizado correctamente.';
    header('Location: perfil.php');
    exit();
}
?>
