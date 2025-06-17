<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_usuario']) && isset($_POST['id_oportunidad'])) {
    try {
        $idUsuario = new ObjectId($_POST['id_usuario']);
        $idOportunidad = new ObjectId($_POST['id_oportunidad']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        exit();
    }

    $coleccionPostulaciones = $database->postulaciones;
    $coleccionOportunidades = $database->oportunidades;
    $coleccionNotificaciones = $database->notificaciones;

    // Buscar la postulación
    $postulacion = $coleccionPostulaciones->findOne([
        'id_usuario' => $idUsuario,
        'id_oportunidad' => $idOportunidad
    ]);

    if (!$postulacion) {
        echo json_encode(['status' => 'error', 'message' => 'No se encontró la postulación para eliminar.']);
        exit();
    }

    // Obtener datos de la oportunidad para el mensaje
    $oportunidad = $coleccionOportunidades->findOne(['_id' => $idOportunidad]);

    // Eliminar la postulación
    $resultado = $coleccionPostulaciones->deleteOne([
        '_id' => $postulacion['_id']
    ]);

    if ($resultado->getDeletedCount() > 0) {
        // Crear notificación para el voluntario
        if ($oportunidad) {
            $mensaje = "Tu postulación a la oportunidad \"{$oportunidad['titulo']}\" ha sido eliminada por la organización.";
            $coleccionNotificaciones->insertOne([
                'id_usuario' => $idUsuario,
                'tipo' => 'postulacion_eliminada',
                'mensaje' => $mensaje,
                'fecha' => new UTCDateTime(),
                'leido' => false
            ]);
        }

        echo json_encode(['status' => 'success', 'message' => 'Postulación eliminada correctamente']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar la postulación.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud inválida']);
}
?>
