<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;
use Cloudinary\Cloudinary;

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'organizacion') {
    header("Location: login.php");
    exit();
}

$idUsuario = new ObjectId($_SESSION['usuario']['_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
    $archivo = $_FILES['foto_perfil'];

    // Verificamos si se subió correctamente
    if ($archivo['error'] === UPLOAD_ERR_OK) {
        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => getenv('CLOUDINARY_API_KEY'),
                'api_secret' => getenv('CLOUDINARY_API_SECRET')
            ],
            'url' => [
                'secure' => true
            ]
        ]);

        try {
            // Subir la imagen a Cloudinary
            $resultado = $cloudinary->uploadApi()->upload($archivo['tmp_name'], [
                'folder' => 'voluntariado/perfiles/',
                'public_id' => uniqid("perfil_org_"),
                'overwrite' => true,
                'resource_type' => 'image'
            ]);

            $urlImagen = $resultado['secure_url'];

            // Guardar la URL en MongoDB
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
