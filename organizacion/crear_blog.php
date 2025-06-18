<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

$coleccion = $database->blogs;
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo = trim($_POST["titulo"] ?? '');
    $contenido = trim($_POST["contenido"] ?? '');
    $imagenes_paths = [];

    if (!$titulo || !$contenido) {
        $mensaje = "❌ El título y contenido son obligatorios.";
    } elseif (!isset($_SESSION["usuario"]["_id"])) {
        $mensaje = "❌ No estás autenticado.";
    } else {
        // Procesar imágenes
        if (!empty($_FILES['imagenes']['name'][0])) {
    $permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    foreach ($_FILES['imagenes']['name'] as $key => $nombreOriginal) {
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

        if (!in_array($extension, $permitidos)) {
            $mensaje = "❌ Tipo de archivo no permitido: $extension";
            continue;
        }

        $nombreSeguro = uniqid("img_", true) . '.' . $extension;
        $rutaCarpeta = "../uploads/";
        $rutaCompleta = $rutaCarpeta . $nombreSeguro;

        // Verifica que la carpeta exista
        if (!is_dir($rutaCarpeta)) {
            mkdir($rutaCarpeta, 0755, true);
        }

        if (move_uploaded_file($_FILES['imagenes']['tmp_name'][$key], $rutaCompleta)) {
            $imagenes_paths[] = "uploads/" . $nombreSeguro;
        } else {
            error_log("❌ No se pudo mover el archivo: " . $_FILES['imagenes']['tmp_name'][$key]);
            error_log("Permisos carpeta: " . (is_writable($rutaCarpeta) ? "OK" : "No escribible"));
            $mensaje = "❌ Error al subir una imagen: $nombreOriginal";
        }
    }
}


        if (!$mensaje) {
            $nuevoBlog = [
                "titulo" => $titulo,
                "contenido" => $contenido,
                "imagenes" => $imagenes_paths,
                "creado_por" => new ObjectId($_SESSION["usuario"]["_id"]),
                "nombre_organizacion" => $_SESSION["usuario"]["organizacion"] ?? "Organización Desconocida",
                "fecha_creacion" => new UTCDateTime()
            ];            

            try {
                $coleccion->insertOne($nuevoBlog);
                $mensaje = "✅ Blog publicado con éxito.";
            } catch (Exception $e) {
                $mensaje = "❌ Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Crear Blog</title>
    <link rel="stylesheet" href="../css/blog.css">
</head>
<body>
    <?php include 'navbar_org.php'; ?>

    <div class="container">
        <h2>Publicar Nueva Entrada de Blog</h2>

        <?php if ($mensaje): ?>
            <div class="alert <?php echo strpos($mensaje, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form action="crear_blog.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="titulo" class="form-label">Título</label>
                <input type="text" name="titulo" id="titulo" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="contenido" class="form-label">Contenido</label>
                <textarea name="contenido" id="contenido" class="form-control" rows="5" required></textarea>
            </div>

            <div class="mb-3">
                <label for="imagenes" class="form-label">Imágenes (opcional)</label>
                <input type="file" name="imagenes[]" id="imagenes" class="form-control" multiple>
                <small class="form-text text-muted">Puedes seleccionar múltiples imágenes.</small>
            </div>

            <button type="submit" class="btn btn-primary">Publicar Blog</button>
        </form>
    </div>
</body>
</html>
