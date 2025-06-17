<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

$coleccion = $database->blogs;

// Procesar "Me gusta" (toggle)
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST["like_blog_id"])) {
    $blogId = new ObjectId($_POST["like_blog_id"]);
    $userId = $_SESSION["usuario"]["_id"];

    $blog = $coleccion->findOne(["_id" => $blogId]);
    $likedBy = isset($blog["liked_by"]) ? iterator_to_array($blog["liked_by"]) : [];

    if (in_array($userId, $likedBy)) {
        // Quitar like
        $coleccion->updateOne(
            ["_id" => $blogId],
            [
                '$pull' => ['liked_by' => $userId],
                '$inc' => ['likes' => -1]
            ]
        );
    } else {
        // Agregar like
        $coleccion->updateOne(
            ["_id" => $blogId],
            [
                '$addToSet' => ['liked_by' => $userId],
                '$inc' => ['likes' => 1]
            ]
        );
    }

    header("Location: ver_blogs.php");
    exit();
}

// Eliminar blog
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["eliminar"]) && isset($_POST["id_blog"])) {
    $coleccion->deleteOne(["_id" => new ObjectId($_POST["id_blog"])]);    
    header("Location: ver_blogs.php");
    exit();
}

// Obtener blogs del usuario
$blogs = [];
if (isset($_SESSION["usuario"]["_id"])) {
    $blogs = $coleccion->find([
        "creado_por" => new ObjectId($_SESSION["usuario"]["_id"])
    ]);
} else {
    die("Debes iniciar sesión para ver tus blogs.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Blogs</title>
    <link rel="stylesheet" href="../css/ver_publicacion_org.css">
    <link href="https://cdn.jsdelivr.net/npm/lightbox2@2/dist/css/lightbox.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/lightbox2@2/dist/js/lightbox-plus-jquery.min.js"></script>
</head>

<body>
<?php include 'navbar_org.php'; ?>


<div class="container">
    <h2>Mis Publicaciones</h2>

    <div class="publicaciones">
        <?php foreach ($blogs as $blog): ?>
            <div class="card mb-4">
                <h4><?php echo htmlspecialchars($blog->titulo); ?></h4>

                <!-- Imagen individual -->
                <?php if (!empty($blog->imagen)): ?>
                    <a href="../<?php echo htmlspecialchars($blog->imagen); ?>" data-lightbox="blog-<?php echo $blog->_id; ?>" data-title="<?php echo htmlspecialchars($blog->titulo); ?>" class="lightbox-img">
                        <img src="../<?php echo htmlspecialchars($blog->imagen); ?>" alt="Imagen blog" class="img-lightbox">
                    </a>
                <?php endif; ?>

                <!-- Múltiples imágenes en carrusel -->
                <?php
                if (isset($blog->imagenes) && is_iterable($blog->imagenes)) {
                    $imagenes_array = iterator_to_array($blog->imagenes);
                    if (!empty($imagenes_array)) {
                        echo '<div class="carousel"><div class="carousel-inner">';
                        foreach ($imagenes_array as $img) {
                            if (!empty($img)) {
                                echo '<div class="carousel-item">';
                                echo '<a href="../' . htmlspecialchars($img) . '" data-lightbox="blog-' . $blog->_id . '" data-title="' . htmlspecialchars($blog->titulo) . '" class="lightbox-img">';
                                echo '<img src="../' . htmlspecialchars($img) . '" alt="Imagen blog" class="img-lightbox">';
                                echo '</a></div>';
                            }
                        }
                        echo '</div></div>';
                    }
                }
                ?>

                <p><?php echo nl2br(htmlspecialchars($blog->contenido)); ?></p>

                <a href="editar_blog.php?id=<?php echo $blog->_id; ?>" class="btn btn-sm btn-warning">Editar</a>

                <!-- Botón eliminar -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="id_blog" value="<?php echo $blog->_id; ?>">
                    <button type="submit" name="eliminar" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este blog?');">Eliminar Blog</button>
                </form>

                <!-- Botón like -->
                <?php
                $likedBy = isset($blog->liked_by) ? iterator_to_array($blog->liked_by) : [];
                $yaDioLike = in_array($_SESSION['usuario']['_id'], $likedBy);
                ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="like_blog_id" value="<?php echo $blog->_id; ?>">
                    <button type="submit" class="like-button <?= $yaDioLike ? 'liked' : '' ?>">
                        <span class="heart">❤️</span>
                        <?= $yaDioLike ? 'Te gusta' : 'Me gusta' ?>
                        (<?= isset($blog->likes) ? $blog->likes : 0 ?>)
                    </button>
                </form>

            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    // Carrusel automático
    document.querySelectorAll('.carousel').forEach(function(carousel) {
        let index = 0;
        const items = carousel.querySelectorAll('.carousel-item');
        const totalItems = items.length;

        function nextItem() {
            index = (index + 1) % totalItems;
            updateCarousel();
        }

        function prevItem() {
            index = (index - 1 + totalItems) % totalItems;
            updateCarousel();
        }

        function updateCarousel() {
            const offset = -index * 100;
            carousel.querySelector('.carousel-inner').style.transform = 'translateX(' + offset + '%)';
        }

        setInterval(nextItem, 5000); // Carrusel automático

        const prevBtn = document.createElement('button');
        prevBtn.textContent = '←';
        prevBtn.classList.add('prev');
        prevBtn.addEventListener('click', prevItem);
        carousel.appendChild(prevBtn);

        const nextBtn = document.createElement('button');
        nextBtn.textContent = '→';
        nextBtn.classList.add('next');
        nextBtn.addEventListener('click', nextItem);
        carousel.appendChild(nextBtn);
    });
</script>

</body>
</html>
