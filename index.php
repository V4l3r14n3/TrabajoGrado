<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plataforma de Voluntariado</title>
    <link rel="stylesheet" href="css/index1.css">
    <script defer src="js/index.js"></script>
</head>

<body>
    <header>
        <nav>
            <a href="index.php" class="logo-container">
                <img src="img/logo.png" alt="Logo" class="logo">
                <span class="logo-text">Volunteero</span>
            </a>

            <button class="menu-toggle" onclick="toggleMenu()">☰</button>
            <ul id="menu">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="registro.php">Registro</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="nosotros.php">Sobre Nosotros</a></li>
            </ul>
        </nav>


    </header>

    <main class="container">
        <section class="hero">
            <h1>Conéctate y Transforma</h1>
            <p>Únete a nuestra comunidad de voluntarios y genera un impacto positivo.</p>
            <a href="registro.php" class="btn">Únete Ahora</a>
        </section>

        <section class="objetivos">
            <h2>Nuestros Objetivos</h2>
            <div class="objetivos-grid">
                <div class="card">
                    <h3>Transformación Social</h3>
                    <p>Fomentamos el voluntariado como una herramienta de cambio.</p>
                </div>
                <div class="card">
                    <h3>Cultura Ciudadana</h3>
                    <p>Promovemos la solidaridad, responsabilidad y cooperación.</p>
                </div>
                <div class="card">
                    <h3>Conexión Eficiente</h3>
                    <p>Facilitamos la comunicación entre voluntarios y organizaciones.</p>
                </div>
                <div class="card">
                    <h3>Herramientas de Gestión</h3>
                    <p>Optimiza el impacto de tus actividades con nuestras herramientas.</p>
                </div>
                <div class="card">
                    <h3>Reconocimiento</h3>
                    <p>Generamos un ecosistema donde el voluntariado sea valorado.</p>
                </div>
            </div>
        </section>

        <section class="funcionalidades">
            <h2>Funcionalidades Principales</h2>
            <div class="tab-container">
                <button class="tab-btn active" onclick="mostrarSeccion('voluntarios')">Para Voluntarios</button>
                <button class="tab-btn" onclick="mostrarSeccion('organizaciones')">Para Organizaciones</button>
            </div>

            <div id="voluntarios" class="tab-content active">
                <div class="funcionalidades-grid">
                    <div class="card">
                        <h3>Perfiles Personalizados</h3>
                        <p>Crea un perfil con tus habilidades, intereses y disponibilidad.</p>
                    </div>
                    <div class="card">
                        <h3>Búsqueda de Oportunidades</h3>
                        <p>Filtra actividades por ubicación, categoría y tipo de voluntariado.</p>
                    </div>
                    <div class="card">
                        <h3>Postulación Directa</h3>
                        <p>Inscríbete en proyectos de forma sencilla y rápida.</p>
                    </div>
                    <div class="card">
                        <h3>Certificados y Reconocimientos</h3>
                        <p>Registra tus horas de voluntariado y recibe certificados digitales.</p>
                    </div>
                </div>
            </div>

            <div id="organizaciones" class="tab-content">
                <div class="funcionalidades-grid">
                    <div class="card">
                        <h3>Registro de Organizaciones</h3>
                        <p>Crea un perfil y publica oportunidades de voluntariado.</p>
                    </div>
                    <div class="card">
                        <h3>Gestión de Actividades</h3>
                        <p>Administra eventos, participantes y coordina horarios.</p>
                    </div>
                    <div class="card">
                        <h3>Comunicación con Voluntarios</h3>
                        <p>Envía notificaciones y mantén informados a los participantes.</p>
                    </div>
                    <div class="card">
                        <h3>Análisis de Impacto</h3>
                        <p>Genera reportes sobre la participación y el impacto del voluntariado.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Plataforma de Voluntariado. Todos los derechos reservados.</p>
    </footer>
</body>

</html>