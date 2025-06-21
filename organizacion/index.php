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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Organizaciones</title>
    <link rel="stylesheet" href="../css/organizaciones.css">
</head>
<body>
    <!-- Barra de navegación -->
    <?php include 'navbar_org.php'; ?>
    
    <main class="container">
        <section class="hero">
            <h1>Bienvenido a la Plataforma de Organizaciones</h1>
            <p>Publica oportunidades de voluntariado y conecta con la comunidad.</p>
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
        
        <section class="features">
            <div class="feature-card" onclick="window.location.href='publicar.php';">
                <img src="../img/publicar.jpg" alt="Publicar oportunidades">
                <h3>Publicar Oportunidades</h3>
                <p>Crea y administra oportunidades de voluntariado.</p>
            </div>
            <div class="feature-card" onclick="window.location.href='foro_org.php';">
                <img src="../img/comunidad.png" alt="Foro de la comunidad">
                <h3>Foro de Comunidad</h3>
                <p>Comparte experiencias y resuelve dudas con otras organizaciones.</p>
            </div>
            <div class="feature-card" onclick="window.location.href='ver_blogs.php';">
                <img src="../img/blog.jpeg" alt="Blog de voluntariado">
                <h3>Blog de Voluntariado</h3>
                <p>Inspira con historias y consejos sobre voluntariado.</p>
            </div>
        </section>
    </main>
    
    <footer>
        <p>&copy; 2024 Plataforma de Voluntariado. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
