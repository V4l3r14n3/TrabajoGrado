<?php
require_once '../conexion.php';
require_once '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

$notiCount = 0;

if (isset($_SESSION['usuario']) && $_SESSION['usuario']['tipo_usuario'] === 'voluntario') {
    $idVoluntario = new ObjectId($_SESSION['usuario']['_id']);
    $notiCount = $database->notificaciones->countDocuments([
        'id_usuario' => $idVoluntario,
        'leido' => false
    ]);
}
?>

<!-- Íconos y estilos -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    nav {
        background: linear-gradient(90deg, #005aa7, #86c7f3);
        padding: 15px 30px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        justify-content: space-between;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        margin: 15px;
        transition: all 0.3s ease;
    }

    .nav-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
    }

    .nav-logo img {
        height: 50px;
        border-radius: 50%;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
    }

    .site-name {
        font-size: 1.8em;
        font-weight: bold;
        color: white;
        letter-spacing: 1px;
    }

    .menu-toggle {
        display: none;
        font-size: 1.8em;
        background: none;
        color: white;
        border: none;
        cursor: pointer;
        transition: transform 0.3s;
    }

    .menu-toggle:hover {
        transform: rotate(90deg);
    }

    .nav-menu {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        flex-wrap: wrap;
        transition: max-height 0.3s ease;
    }

    .nav-left ul {
        list-style: none;
        display: flex;
        gap: 20px;
        margin: 0;
        padding: 0;
    }

    .nav-left ul li a {
        text-decoration: none;
        color: white;
        font-weight: 600;
        padding: 8px 12px;
        border-radius: 8px;
        transition: background-color 0.3s, color 0.3s;
    }

    .nav-left ul li a:hover {
        background-color: rgba(255, 255, 255, 0.2);
        color: #ffe066;
    }

    .nav-right {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .welcome {
        color: white;
        font-weight: 600;
        text-decoration: none;
    }

    .logout {
        background-color: #ff4d4d;
        padding: 8px 16px;
        border-radius: 8px;
        text-decoration: none;
        color: white;
        font-weight: bold;
        transition: background 0.3s, transform 0.3s;
    }

    .logout:hover {
        background-color: #cc0000;
        transform: translateY(-2px);
    }

    .noti-icon {
        position: relative;
        font-size: 1.6em;
        color: white;
        text-decoration: none;
        transition: transform 0.3s ease;
    }

    .noti-icon:hover {
        transform: scale(1.2);
    }

    .noti-count {
        position: absolute;
        top: -8px;
        right: -10px;
        background-color: red;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 0.75em;
        animation: pulse 1.5s infinite;
    }

    .noti-count.hide {
        display: none;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.2);
        }
    }

    /* Responsivo */
    @media (max-width: 768px) {
        .menu-toggle {
            display: block;
        }

        .nav-menu {
            display: none;
            flex-direction: column;
            width: 100%;
            margin-top: 10px;
            z-index: 999;
            background: linear-gradient(90deg, #005aa7, #86c7f3);
            /* Para que tenga fondo */
            border-radius: 0 0 12px 12px;
        }

        .nav-menu.active {
            display: flex;
        }

        .nav-left ul {
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .nav-right {
            flex-direction: column;
            gap: 15px;
            margin-top: 15px;
        }

        .site-name {
            font-size: 1.5em;
        }
    }
</style>

<nav>
    <!-- Logo + botón hamburguesa -->
    <div style="display: flex; align-items: center; gap: 15px;">
        <a href="index.php" class="nav-logo">
            <img src="../img/logo.png" alt="Logo">
            <span class="site-name">Volunteero</span>
        </a>
        <button class="menu-toggle" id="hamburguesa">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Menú que se colapsa en móvil -->
    <div class="nav-menu" id="navMenu">
        <div class="nav-left">
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="oportunidades.php">Ver Oportunidades</a></li>
                <li><a href="blog.php">Blog</a></li>
                <li><a href="foro.php">Foro</a></li>
            </ul>
        </div>

        <div class="nav-right">
            <a href="notificaciones.php" class="noti-icon">
                <i class="fas fa-bell"></i>
                <?php if ($notiCount > 0): ?>
                    <span class="noti-count"><?php echo $notiCount; ?></span>
                <?php else: ?>
                    <span class="noti-count hide"></span>
                <?php endif; ?>
            </a>
            <a href="perfil.php" class="welcome">Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']['nombre']); ?>!</a>
            <a href="logout.php" class="logout">Cerrar sesión</a>
        </div>
    </div>
</nav>

<!-- Script para menú hamburguesa -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('hamburguesa');
    const menu = document.getElementById('navMenu');
    
    if (toggleBtn && menu) {
        toggleBtn.addEventListener('click', () => {
            menu.classList.toggle('active');
        });
    }
});
</script>
