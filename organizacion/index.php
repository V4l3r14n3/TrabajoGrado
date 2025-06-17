<?php
session_start();
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
