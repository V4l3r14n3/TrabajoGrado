<?php
require 'vendor/autoload.php'; // Cargar Composer
use MongoDB\Client;

try {
    $mongo = new Client(getenv("MONGODB_URI"));
    $database = $mongo->Voluntariado; 
} catch (Exception $e) {
    error_log("Error de conexión a MongoDB: " . $e->getMessage()); // Registrar error en el log
}
?>
