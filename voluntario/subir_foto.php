<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;
use Cloudinary\Cloudinary;

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'voluntario') {
    header("Location: login.php");
    exit();
}

$idUsuario = new ObjectId($_SESSION['usuario']['_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
    $foto = $_FILES['foto_perfil'];

    if ($foto['error'] === UPLOAD_ERR_OK) {
        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => getenv('CLOUDINARY_API_KEY'),
                'api_secret' => getenv('CLOUDINARY_API_SECRET')
            ],
            'url' => ['secure' => true]
        ]);

        try {
            $resultado = $cloudinary->uploadApi()->upload($foto['tmp_name'], [
                'folder' => 'voluntariado/perfiles/',
                'public_id' => uniqid("perfil_vol_"),
                'overwrite' => true,
                'resource_type' => 'image'
            ]);

            $urlImagen = $resultado['secure_url'];

            $database->usuarios->updateOne(
                ['_id' => $idUsuario],
                ['$set' => ['foto_perfil' => $urlImagen]]
            );

            $_SESSION['success_message'] = '✅ Foto de perfil actualizada con éxito.';
        } catch (Exception $e) {
            $_SESSION['error_message'] = '❌ Error al subir a Cloudinary: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = '❌ Error al subir la imagen localmente.';
    }
}

header("Location: perfil.php");
exit();
