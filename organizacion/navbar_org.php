<?php
require_once '../conexion.php';
require_once '../vendor/autoload.php';

$notiCount = 0;

if (isset($_SESSION['usuario'])) {
    $usuarioId = $_SESSION['usuario']['_id'];
    $notiCount = $database->notificaciones->countDocuments([
        'id_usuario' => new MongoDB\BSON\ObjectId($usuarioId),
        'leido' => false
    ]);
}
?>

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        nav {
            background: linear-gradient(90deg, #0077cc, #00c6ff);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-logo img {
            height: 50px;
            width: auto;
        }

        .site-name {
            font-size: 1.8em;
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.5);
        }

        .menu-toggle {
            display: block !important;
            font-size: 1.8em;
            background: none;
            color: white;
            border: none;
            cursor: pointer;
        }

        .nav-menu {
            display: flex;
            flex: 1;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            background: transparent;
            /* ← corregido */
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
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 5px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-left ul li a::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 3px;
            background: #ffcc00;
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
        }

        .nav-left ul li a:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        .nav-left ul li a:hover {
            color: #ffcc00;
            text-shadow: 0 0 5px #fff, 0 0 10px #ffcc00;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .welcome {
            color: white;
            font-weight: bold;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.5);
        }

        .logout {
            background: linear-gradient(45deg, #ff4d4d, #ff1a1a);
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            transition: background 0.3s;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
        }

        .logout:hover {
            background: linear-gradient(45deg, #e60000, #cc0000);
            transform: translateY(-2px);
        }

        .noti-icon {
            position: relative;
            font-size: 1.6em;
            color: white;
            transition: transform 0.3s ease;
        }

        .noti-icon:hover {
            transform: scale(1.2);
            color: #ffcc00;
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
                transform: scale(1.3);
            }
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }

            .nav-menu {
                display: none !important;
                flex-direction: column;
                width: 100% !important;
                margin-top: 10px;
                background: transparent;
                /* ← corregido */
                border-top: 1px solid rgba(255, 255, 255, 0.3);
                padding: 15px 0;
            }

            .nav-menu.active {
                display: flex !important;
            }

            .nav-left ul {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }

            .nav-right {
                flex-direction: column;
                gap: 10px;
                margin-top: 10px;
            }

            .site-name {
                font-size: 1.5em;
            }
        }
    </style>

</head>

<nav>
    <div style="display: flex; align-items: center; gap: 15px;">
        <a href="index.php" class="nav-logo">
            <img src="../img/logo.png" alt="Logo">
            <span class="site-name">Volunteero</span>
        </a>
        <button class="menu-toggle" onclick="toggleMenu()" aria-label="Abrir menú">
            <i class="fas fa-bars"></i>
        </button>

    </div>

    <div class="nav-menu" id="navMenu">
        <div class="nav-left">
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="publicar.php">Publicar Oportunidad</a></li>
                <li><a href="foro_org.php">Foro</a></li>
                <li><a href="crear_blog.php">Crear Blog</a></li>
                <li><a href="ver_blogs.php">Blogs</a></li>
                <li><a href="ver_alcances.php">Alcances</a></li>
            </ul>
        </div>

        <div class="nav-right">
            <?php if (isset($_SESSION['usuario'])): ?>
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
            <?php else: ?>
                <a href="login.php" class="logout">Login</a>
                <a href="registro.php" class="logout">Registro</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    function toggleMenu() {
        const menu = document.getElementById('navMenu');
        menu.classList.toggle('active');
    }
</script>