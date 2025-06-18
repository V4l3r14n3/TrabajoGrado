<?php
require 'vendor/autoload.php';

use MongoDB\Client;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

// =============================
// 🔗 Conexión a MongoDB Atlas
// =============================
try {
    $mongo = new Client(getenv("MONGODB_URI")); // variable de entorno en Render
    $database = $mongo->Voluntariado;
} catch (Exception $e) {
    error_log("❌ Error de conexión a MongoDB: " . $e->getMessage());
    die("No se pudo conectar a la base de datos.");
}

// =============================
// ☁️ Configuración Cloudinary
// =============================
try {
    Configuration::instance([
        'cloud' => [
            'cloud_name' => getenv("CLOUDINARY_CLOUD_NAME"),
            'api_key'    => getenv("CLOUDINARY_API_KEY"),
            'api_secret' => getenv("CLOUDINARY_API_SECRET"),
        ],
        'url' => [
            'secure' => true
        ]
    ]);

    // Opcional: objeto Cloudinary disponible globalmente
    $cloudinary = new Cloudinary();
} catch (Exception $e) {
    error_log("❌ Error configurando Cloudinary: " . $e->getMessage());
}
?>
