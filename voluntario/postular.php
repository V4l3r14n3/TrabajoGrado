<?php
ob_start();
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para postularte.']);
    exit;
}

if (!isset($_POST['id_oportunidad'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'ID de oportunidad no recibido.']);
    exit;
}

try {
    $idOportunidad = new ObjectId($_POST['id_oportunidad']);
    $usuario = $_SESSION['usuario'];

    $oportunidad = $database->oportunidades->findOne(['_id' => $idOportunidad]);
    if (!$oportunidad) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'La oportunidad no existe.']);
        exit;
    }

    $yaPostulado = $database->postulaciones->findOne([
        'id_oportunidad' => $idOportunidad,
        'id_usuario' => new ObjectId($usuario['_id'])
    ]);

    if ($yaPostulado) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Ya te has postulado a esta oportunidad.']);
        exit;
    }

    $database->postulaciones->insertOne([
        'id_oportunidad' => $idOportunidad,
        'id_usuario' => new ObjectId($usuario['_id']),
        'nombre_usuario' => $usuario['nombre'],
        'correo_usuario' => $usuario['email'],
        'nombre_oportunidad' => $oportunidad['titulo'] ?? 'Oportunidad sin nombre',
        'nombre_organizacion' => $oportunidad['nombre_organizacion'] ?? 'Desconocido',
        'fecha_postulacion' => new UTCDateTime()
    ]);

    $database->notificaciones->insertOne([
        'id_usuario' => new ObjectId($oportunidad['creado_por']),
        'tipo' => 'postulacion',
        'mensaje' => "El voluntario {$usuario['nombre']} se ha postulado a tu oportunidad: \"{$oportunidad['titulo']}\".",
        'fecha' => new UTCDateTime(),
        'leido' => false
    ]);

    ob_end_clean();
    echo json_encode(['success' => true, 'message' => '¡Postulación exitosa!']);
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
