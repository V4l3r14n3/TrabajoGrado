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
            foreach ($_FILES['imagenes']['name'] as $key => $nombreArchivo) {
                $rutaArchivo = "../uploads/" . basename($nombreArchivo);
                $tipoArchivo = strtolower(pathinfo($rutaArchivo, PATHINFO_EXTENSION));

                if (in_array($tipoArchivo, $permitidos)) {
                    if (move_uploaded_file($_FILES["imagenes"]["tmp_name"][$key], $rutaArchivo)) {
                        $imagenes_paths[] = "uploads/" . $nombreArchivo;
                    } else {
                        $mensaje = "❌ Error al subir una de las imágenes.";
                    }
                } else {
                    $mensaje = "❌ Tipo de imagen no permitido.";
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
