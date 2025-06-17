<?php
session_start();
require '../conexion.php';

// Obtener las últimas 4 oportunidades
$coleccion = $database->oportunidades;
$oportunidades = $coleccion->find([], [
    'sort' => ['fecha_creacion' => -1],
    'limit' => 4
]);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Plataforma de Voluntariado</title>
    <link rel="stylesheet" href="../css/volunteer.css" />
    <!-- SwiperJS CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
</head>

<body>
    <?php include 'navbar_voluntario.php'; ?>

    <main class="container">
        <section class="hero">
            <h1>Bienvenido a la Plataforma de Voluntariado</h1>
            <p>Encuentra la oportunidad de voluntariado para ti y conecta con la comunidad.</p>
        </section>

        <h2>Algunas de nuestras oportunidades de voluntariado</h2>
        <div style="text-align: center; margin-top: 30px;">
        <a href="oportunidades.php" class="btn-ver-todas">Ver todas las oportunidades</a>
    </div>

        <section class="swiper carrusel mt-4">
            <div class="swiper-wrapper">
                <?php foreach ($oportunidades as $op): ?>
                    <div class="swiper-slide carrusel-item">
                        <img src="<?= htmlspecialchars($op['imagen']) ?>" alt="Imagen oportunidad">
                        <div class="carrusel-caption">
                            <h3><?= htmlspecialchars($op['titulo']) ?></h3>
                            <p><?= htmlspecialchars($op['descripcion']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Controles de navegación -->
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>

            <!-- Paginación -->
            <div class="swiper-pagination"></div>
        </section>
    </main>
    


    <footer>
        <p>&copy; 2024 Plataforma de Voluntariado. Todos los derechos reservados.</p>
    </footer>

    <!-- SwiperJS JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- Inicialización de Swiper -->
    <script>
        const swiper = new Swiper('.swiper', {
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                0: {
                    slidesPerView: 1,
                    spaceBetween: 10
                },
                640: {
                    slidesPerView: 2,
                    spaceBetween: 20
                },
                1024: {
                    slidesPerView: 3,
                    spaceBetween: 30
                }
            }
        });
    </script>

</body>

</html>