<?php
session_start();
require '../conexion.php';

// Verificar si el usuario tiene una pregunta de seguridad configurada
$mostrarConfigPregunta = false;

if (isset($_SESSION['usuario'])) {
    $email = $_SESSION['usuario']['email'];

    $usuario = $database->usuarios->findOne(['email' => $email]);

    if (!isset($usuario['pregunta_seguridad']) || empty($usuario['pregunta_seguridad'])) {
        $mostrarConfigPregunta = true;
    }
}
$mensajeGuardado = false;
if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'pregunta_guardada') {
    $mensajeGuardado = true;
}

// Si el usuario no está logueado, redirigir al login
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit();
}

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
    <link rel="stylesheet" href="../css/voluntarioindex.css" />
    <!-- SwiperJS CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
</head>

<body>
    <?php include 'navbar_voluntario.php'; ?>
    <?php if ($mensajeGuardado): ?>
        <div class="toast success show">Pregunta de seguridad guardada correctamente.</div>
        <script>
            setTimeout(() => {
                document.querySelector('.toast.success').classList.remove('show');
            }, 4000);
        </script>
    <?php endif; ?>

    <main class="container">
        <section class="hero">
            <h1>Bienvenido a la Plataforma de Voluntariado</h1>
            <p>Encuentra la oportunidad de voluntariado para ti y conecta con la comunidad.</p>
        </section>

        <!-- Botón para abrir el modal -->
        <div style="text-align:center; margin-bottom: 30px;">
            <button class="btn-seguridad" onclick="document.getElementById('modalPregunta').style.display='block'">
                Configurar pregunta de seguridad
            </button>
        </div>

        <!-- Modal -->
        <div id="modalPregunta" class="modal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('modalPregunta').style.display='none'">&times;</span>
                <h3>Establecer pregunta de seguridad</h3>
                <form method="POST" action="guardar_pregunta.php">
                    <select name="pregunta" required>
                        <option value="">Seleccione una pregunta</option>
                        <option>¿Cuál es el nombre de tu primera mascota?</option>
                        <option>¿En qué ciudad naciste?</option>
                        <option>¿Cuál es tu color favorito?</option>
                    </select>
                    <input type="text" name="respuesta" placeholder="Respuesta" required>
                    <?php if ($mostrarConfigPregunta): ?>
                        <div style="text-align: center; margin: 20px 0;">
                            <button class="btn-seguridad" onclick="document.getElementById('modalPregunta').style.display='block'">
                                Configurar pregunta de seguridad
                            </button>
                        </div>
                    <?php endif; ?>

                </form>
            </div>
        </div>


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

            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
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