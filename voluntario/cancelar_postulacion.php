<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'voluntario') {
    http_response_code(401);
    exit("No autorizado");
}

try {
    $idUsuario = new ObjectId($_SESSION['usuario']['_id']);
    $idOportunidad = isset($_POST['id_oportunidad']) ? new ObjectId($_POST['id_oportunidad']) : null;
} catch (Exception $e) {
    http_response_code(400);
    exit("ID inválido");
}

if (!$idOportunidad) {
    http_response_code(400);
    exit("ID inválido");
}

// Verificar que la postulación existe
$postulacion = $database->postulaciones->findOne([
    'id_usuario' => $idUsuario,
    'id_oportunidad' => $idOportunidad
]);

if (!$postulacion) {
    http_response_code(404);
    exit("No se encontró la postulación");
}

// Eliminar postulación
$result = $database->postulaciones->deleteOne([
    'id_usuario' => $idUsuario,
    'id_oportunidad' => $idOportunidad
]);

if ($result->getDeletedCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Postulación cancelada con éxito.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo cancelar la postulación.']);
}
