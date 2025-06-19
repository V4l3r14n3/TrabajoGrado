<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

$coleccion = $database->blogs;
$mensaje = "";

if (!isset($_GET['id'])) {
    die("Falta el ID del blog.");
}

$id = $_GET['id'];
$blog = $coleccion->findOne([
    "_id" => new ObjectId($id),
    "creado_por" => new ObjectId($_SESSION["usuario"]["_id"])
]);

if (!$blog) {
    die("Blog no encontrado o no tienes permiso.");
}

// ELIMINAR IMAGEN DEL ARRAY (NO DEL SERVIDOR)
if (isset($_GET['eliminar_img'])) {
    $imgEliminar = $_GET['eliminar_img'];
    $imagenesActuales = iterator_to_array($blog->imagenes ?? []);
    $imagenesActualizadas = array_filter($imagenesActuales, fn($img) => $img !== $imgEliminar);

    $coleccion->updateOne(
        ["_id" => new ObjectId($id)],
        ['$set' => ["imagenes" => array_values($imagenesActualizadas)]]
    );

    // SOLO ELIMINAR ARCHIVO SI ES LOCAL
    if (strpos($imgEliminar, 'http') !== 0) {
        $rutaArchivo = "../" . $imgEliminar;
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }
    }

    header("Location: editar_blog.php?id=$id");
    exit();
}

// REORDENAR IMÁGENES
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["orden_imagenes"])) {
    $nuevoOrden = json_decode($_POST["orden_imagenes"], true);
    $coleccion->updateOne(
        ["_id" => new ObjectId($id)],
        ['$set' => ["imagenes" => $nuevoOrden]]
    );
}

// GUARDAR CAMBIOS
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["titulo"])) {
    $titulo = trim($_POST["titulo"] ?? '');
    $contenido = trim($_POST["contenido"] ?? '');

    if ($titulo && $contenido) {
        $nuevas_imagenes = [];

        // SUBIDA DE IMÁGENES NUEVAS (puede incluir URLs de Cloudinary si se adapta más adelante)
        if (!empty($_FILES['imagenes']['name'][0])) {
            $permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            foreach ($_FILES['imagenes']['name'] as $key => $nombreArchivo) {
                $nombreLimpio = basename($nombreArchivo);
                $rutaArchivo = "../uploads/" . $nombreLimpio;
                $tipoArchivo = strtolower(pathinfo($rutaArchivo, PATHINFO_EXTENSION));

                if (in_array($tipoArchivo, $permitidos)) {
                    if (move_uploaded_file($_FILES["imagenes"]["tmp_name"][$key], $rutaArchivo)) {
                        $nuevas_imagenes[] = "uploads/" . $nombreLimpio;
                    }
                }
            }
        }

        $updateData = [
            "titulo" => $titulo,
            "contenido" => $contenido
        ];

        if (!empty($nuevas_imagenes)) {
            $imagenesActuales = iterator_to_array($blog->imagenes ?? []);
            $updateData["imagenes"] = array_merge($imagenesActuales, $nuevas_imagenes);
        }

        $coleccion->updateOne(
            ["_id" => new ObjectId($id)],
            ['$set' => $updateData]
        );

        header("Location: ver_blogs.php");
        exit();
    } else {
        $mensaje = "❌ Todos los campos son requeridos.";
    }
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Blog</title>
    <link rel="stylesheet" href="../css/editar_blog.css">
    <style>
        .img-preview {
            position: relative;
            display: inline-block;
            margin: 5px;
            cursor: move;
        }

        .img-preview img {
            max-width: 120px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .img-preview a {
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(255, 0, 0, 0.85);
            color: white;
            padding: 4px 8px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 0 6px 0 6px;
            z-index: 10;
            transition: background 0.2s ease;
        }

        .img-preview a:hover {
            background: rgba(200, 0, 0, 1);
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Editar Entrada</h2>

        <?php if ($mensaje): ?>
            <div class="alert <?php echo strpos($mensaje, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" onsubmit="guardarOrden()">
            <?php if (!empty($blog->imagenes)): ?>
                <div class="mb-3">
                    <label>Imágenes actuales (puedes reordenarlas):</label><br>
                    <div id="galeria" ondrop="drop(event)" ondragover="allowDrop(event)">
                        <?php foreach ($blog->imagenes as $img): ?>
                            <div class="img-preview" draggable="true" ondragstart="drag(event)" data-src="<?php echo htmlspecialchars($img); ?>">
                                <a href="editar_blog.php?id=<?php echo $id; ?>&eliminar_img=<?php echo urlencode($img); ?>" onclick="return confirm('¿Eliminar esta imagen?')">×</a>
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="img">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <input type="hidden" name="orden_imagenes" id="orden_imagenes">

            <div class="mb-3">
                <label for="titulo" class="form-label">Título</label>
                <input type="text" name="titulo" value="<?php echo htmlspecialchars($blog->titulo); ?>" class="form-control">
            </div>

            <div class="mb-3">
                <label for="contenido" class="form-label">Contenido</label>
                <textarea name="contenido" class="form-control" rows="6"><?php echo htmlspecialchars($blog->contenido); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="imagenes" class="form-label">Agregar nuevas imágenes</label>
                <input type="file" name="imagenes[]" class="form-control" multiple>
            </div>

            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="ver_blogs.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
    <!-- Modal de imagen -->
    <div id="modalImagen" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.8); z-index:1000; justify-content: center; align-items: center;">
        <span onclick="cerrarModal()" style="position:absolute; top:20px; right:30px; color:white; font-size:40px; cursor:pointer;">&times;</span>
        <img id="imgGrande" src="" style="max-width:90%; max-height:90%; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.5);">
    </div>

    <script>
        let dragged;

        function allowDrop(ev) {
            ev.preventDefault();
        }

        function drag(ev) {
            dragged = ev.target.closest(".img-preview");
        }

        function drop(ev) {
            ev.preventDefault();
            const target = ev.target.closest(".img-preview");
            const galeria = document.getElementById("galeria");

            if (dragged !== target) {
                const draggedIndex = Array.from(galeria.children).indexOf(dragged);
                const targetIndex = Array.from(galeria.children).indexOf(target);

                if (draggedIndex < targetIndex) {
                    galeria.insertBefore(target, dragged);
                    galeria.insertBefore(dragged, target.nextSibling);
                } else {
                    galeria.insertBefore(dragged, target);
                }
            }
        }

        function guardarOrden() {
            const galeria = document.getElementById("galeria");
            const orden = Array.from(galeria.children).map(div => div.getAttribute("data-src"));
            document.getElementById("orden_imagenes").value = JSON.stringify(orden);
        }
        // Modal de imagen
        document.querySelectorAll(".img-preview img").forEach(img => {
            img.addEventListener("click", function(e) {
                e.preventDefault();
                document.getElementById("imgGrande").src = this.src;
                document.getElementById("modalImagen").style.display = "flex";
            });
        });

        function cerrarModal() {
            document.getElementById("modalImagen").style.display = "none";
        }
    </script>
</body>

</html>