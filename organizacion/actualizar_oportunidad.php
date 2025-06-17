<?php
session_start();
require '../conexion.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idUsuario = new ObjectId($_SESSION['usuario']['_id']);
    $coleccionOportunidades = $database->oportunidades;

    // Obtener datos del formulario
    $idOportunidad = new ObjectId($_POST['id_oportunidad']);
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $ubicacion = trim($_POST['ubicacion']);
    $url_ubicacion = trim($_POST['url_ubicacion']);
    $categoria = trim($_POST['categoria']);
    $duracion = (int) $_POST['duracion'];
    $tipoActividad = trim($_POST['tipo_actividad']);
    $fechaInicio = $_POST['fecha_inicio'];
    $imagen = trim($_POST['imagen']);

    // Convertir la fecha a UTCDateTime
    $fechaMongo = new UTCDateTime((new DateTime($fechaInicio))->getTimestamp() * 1000);

    // Validar que la oportunidad pertenece al usuario (aceptar ObjectId o string)
    $oportunidad = $coleccionOportunidades->findOne([
        '_id' => $idOportunidad,
        '$or' => [
            ['creado_por' => $idUsuario],
            ['creado_por' => (string)$idUsuario]
        ]
    ]);

    if (!$oportunidad) {
        $_SESSION['error_message'] = "No tienes permiso para modificar esta oportunidad.";
        header("Location: perfil.php");
        exit();
    }

    // Actualizar en MongoDB
    $resultado = $coleccionOportunidades->updateOne(
        ['_id' => $idOportunidad],
        ['$set' => [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'ubicacion' => $ubicacion,
            'url_ubicacion' => $url_ubicacion,
            'categoria' => $categoria,
            'duracion' => $duracion,
            'tipo_actividad' => $tipoActividad,
            'fecha_inicio' => $fechaMongo,
            'imagen' => $imagen
        ]]
    );

    if ($resultado->getModifiedCount() > 0) {
        $_SESSION['success_message'] = "Oportunidad actualizada correctamente.";
    } else {
        $_SESSION['error_message'] = "No se realizaron cambios o hubo un error.";
    }

    header("Location: perfil.php");
    exit();
}
?>
