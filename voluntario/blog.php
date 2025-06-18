<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit();
}

$collection = $database->blogs;
$userId = new ObjectId($_SESSION['usuario']['_id']); // ID del voluntario como ObjectId

// Procesar "Me gusta"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['blog_id'])) {
  $blogId = new ObjectId($_POST['blog_id']);

  $blog = $collection->findOne(['_id' => $blogId]);

  $likedBy = isset($blog['liked_by']) && is_iterable($blog['liked_by'])
    ? iterator_to_array($blog['liked_by'])
    : [];

  $yaDioLike = false;
  foreach ($likedBy as $likeUserId) {
    if ($likeUserId instanceof ObjectId && (string)$likeUserId === (string)$userId) {
      $yaDioLike = true;
      break;
    }
  }

  if ($yaDioLike) {
    $collection->updateOne(
      ['_id' => $blogId],
      [
        '$pull' => ['liked_by' => $userId],
        '$inc' => ['likes' => -1]
      ]
    );
  } else {
    $collection->updateOne(
      ['_id' => $blogId],
      [
        '$addToSet' => ['liked_by' => $userId],
        '$inc' => ['likes' => 1]
      ]
    );

    // Notificación al autor del blog
    $idOrg = $blog['creado_por'];
    $voluntarioNombre = $_SESSION['usuario']['nombre'];
    $mensaje = "$voluntarioNombre le dio like a tu publicación: \"" . $blog['titulo'] . "\".";

    $database->notificaciones->insertOne([
      'id_usuario' => $idOrg,
      'mensaje' => $mensaje,
      'fecha' => new UTCDateTime(),
      'leido' => false
    ]);
  }

  header("Location: blog.php");
  exit();
}

// Obtener blogs
$blogs = $collection->find([], ['sort' => ['orden' => 1]]);

// 🔍 Obtener la organización creadora del blog
$organizacion = null;
if (isset($blog['creado_por'])) {
  $organizacion = $database->usuarios->findOne([
    '_id' => new ObjectId($blog['creado_por'])
  ]);
}
?>



<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blog de Voluntarios</title>
  <link rel="stylesheet" href="../css/ver_publicacion_org.css">
  <link href="https://cdn.jsdelivr.net/npm/lightbox2@2/dist/css/lightbox.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/lightbox2@2/dist/js/lightbox-plus-jquery.min.js"></script>

  <!-- Añadir el CSS aquí -->
  <style>
    /* Establecer un tamaño máximo para las imágenes dentro del Lightbox */
    img.img-lightbox {
      max-width: 100%;
      /* Para adaptarse a la pantalla */
      max-height: 600px;
      /* Tamaño máximo para evitar que la imagen sea demasiado grande */
      object-fit: contain;
      /* Asegura que la imagen mantenga sus proporciones */
    }

    /* Puedes controlar el tamaño de las imágenes en el carrusel también si es necesario */
    .carousel-item img {
      max-width: 100%;
      height: auto;
      object-fit: contain;
    }
  </style>
</head>

<body>
  <?php include 'navbar_voluntario.php'; ?>

  <div class="container">
    <h2>Blog para Voluntarios</h2>

    <div class="publicaciones">
      <?php foreach ($blogs as $blog): ?>
        <?php
        // Obtener imágenes del blog
        $imagenes = [];
        if (isset($blog['imagenes']) && is_iterable($blog['imagenes'])) {
          $imagenes = iterator_to_array($blog['imagenes']);
        } elseif (isset($blog['imagen'])) {
          $imagenes[] = $blog['imagen'];
        }

        // Validar si el voluntario ya dio like
        $likedBy = isset($blog['liked_by']) ? iterator_to_array($blog['liked_by']) : [];
        $yaDioLike = false;
        foreach ($likedBy as $likeUserId) {
          if ($likeUserId instanceof ObjectId && (string)$likeUserId === (string)$userId) {
            $yaDioLike = true;
            break;
          }
        }

        // 🔍 Obtener la organización del blog
        $organizacion = null;
        if (isset($blog['creado_por'])) {
          $organizacion = $database->usuarios->findOne([
            '_id' => new ObjectId($blog['creado_por'])
          ]);
        }
        ?>

        <div class="card">

          <h3><?php echo htmlspecialchars($blog['titulo']); ?></h3>
          <?php if ($organizacion): ?>
            <div class="autor-blog" style="display: flex; align-items: center; gap: 10px;">
              <?php if (!empty($organizacion['foto_perfil'])): ?>
                <?php
                // Detecta si es una imagen de Cloudinary (url completa) o local (archivo en /uploads)
                $fotoSrc = str_starts_with($organizacion['foto_perfil'], 'http')
                  ? $organizacion['foto_perfil']
                  : "/uploads/" . $organizacion['foto_perfil'];
                ?>
                <img src="<?= htmlspecialchars($fotoSrc) ?>" alt="Logo de <?= htmlspecialchars($organizacion['nombre']) ?>"
                  style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
              <?php endif; ?>
              <p style="margin: 0;">
                Publicado por:
                <a href="perfil_organizacion.php?id=<?= $organizacion['_id'] ?>">
                  <?= htmlspecialchars($organizacion['organizacion']) ?>
                </a>
              </p>
            </div>
          <?php endif; ?>

          <?php if (count($imagenes) > 1): ?>
            <div class="carousel">
              <div class="carousel-inner">
                <?php foreach ($imagenes as $index => $img): ?>
                  <?php
                  $src = str_starts_with($img, 'http') ? $img : "/voluntariado/" . $img;
                  ?>
                  <div class="carousel-item">
                    <a href="<?= htmlspecialchars($src) ?>" data-lightbox="blog-<?= $blog['_id'] ?>" data-title="<?= htmlspecialchars($blog['titulo']) ?>">
                      <img src="<?= htmlspecialchars($src) ?>" alt="Imagen blog" class="img-lightbox">
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php elseif (count($imagenes) === 1): ?>
            <?php
            $src = str_starts_with($imagenes[0], 'http') ? $imagenes[0] : "/voluntariado/" . $imagenes[0];
            ?>
            <a href="<?= htmlspecialchars($src) ?>" data-lightbox="blog-<?= $blog['_id'] ?>" data-title="<?= htmlspecialchars($blog['titulo']) ?>" class="lightbox-img">
              <img src="<?= htmlspecialchars($src) ?>" alt="Imagen blog" class="img-lightbox">
            </a>
          <?php endif; ?>


          <p><?php echo nl2br(htmlspecialchars($blog['contenido'])); ?></p>

          <form method="POST" class="like-form">
            <input type="hidden" name="blog_id" value="<?= $blog['_id'] ?>">
            <button type="submit" class="like-button <?= $yaDioLike ? 'liked' : '' ?>">
              <span class="heart">❤️</span>
              <span class="like-text"><?= $yaDioLike ? 'Te gusta' : 'Me gusta' ?></span>
              <span class="like-count">(<?= $blog['likes'] ?? 0 ?>)</span>
            </button>
          </form>

        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <script>
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

      setInterval(nextItem, 5000); // auto-slide

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