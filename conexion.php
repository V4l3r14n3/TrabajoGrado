<?php
require 'vendor/autoload.php'; // Cargar Composer
use MongoDB\Client;

try {
    $mongo = new Client("mongodb+srv://valemantilla15:Valentina.17@cluster0.zwlhw.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0");
    $database = $mongo->Voluntariado; 
} catch (Exception $e) {
    error_log("Error de conexión a MongoDB: " . $e->getMessage()); // Registrar error en el log
}
?>
