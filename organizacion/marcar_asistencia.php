<?php
session_start();
require '../conexion.php';

use MongoDB\BSON\ObjectId;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Acceso no permitido.";
    header("Location: index.php");
    exit();
}

if (!isset($_POST['id_oportunidad'])) {
    $_SESSION['error_message'] = "Falta ID de oportunidad.";
    header("Location: index.php");
    exit();
}

$idOportunidad = new ObjectId($_POST['id_oportunidad']);
$asistencias = $_POST['asistencia'] ?? [];
$coleccionPostulaciones = $database->postulaciones;

// Obtener todas las postulaciones de esa oportunidad
$postulaciones = $coleccionPostulaciones->find(['id_oportunidad' => $idOportunidad]);

foreach ($postulaciones as $postulacion) {
    $idUsuario = (string)$postulacion['id_usuario'];
    $asistio = isset($asistencias[$idUsuario]) ? true : false;

    $coleccionPostulaciones->updateOne(
        [
            '_id' => $postulacion['_id']
        ],
        [
            '$set' => ['asistio' => $asistio]
        ]
    );
}

$_SESSION['success_message'] = "Asistencia actualizada correctamente.";
header("Location: detalle_oportunidad.php?id=" . $idOportunidad);
exit();
