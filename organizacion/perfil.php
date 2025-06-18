<?php
session_start();
require '../conexion.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

use MongoDB\BSON\ObjectId;

// ✅ Obtener el ID del usuario desde la sesión correctamente
$idUsuario = new ObjectId($_SESSION['usuario']['_id']);

$coleccionUsuarios = $database->usuarios;
$coleccionOportunidades = $database->oportunidades;

// Obtener datos del usuario
$usuario = $coleccionUsuarios->findOne(['_id' => $idUsuario]);

// Obtener oportunidades publicadas por el usuario
$oportunidades = $coleccionOportunidades->find(['creado_por' => $idUsuario])->toArray();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario</title>
    <link rel="stylesheet" href="../css/perfil_org.css">
</head>

<body>

    <?php include 'navbar_org.php'; ?>

    <div class="container">
        <h2 class="text-center">Perfil de <?php echo htmlspecialchars($usuario['nombre']); ?></h2>

        <!-- Mensajes de éxito o error -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert success"><?php echo $_SESSION['success_message'];
                                        unset($_SESSION['success_message']); ?></div>
        <?php elseif (isset($_SESSION['error_message'])): ?>
            <div class="alert error"><?php echo $_SESSION['error_message'];
                                        unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>


        <div class="profile-section">
            <div class="profile-pic">
                <h4>Foto de Perfil</h4>
                <img src="<?php echo htmlspecialchars($usuario['foto_perfil'] ?? ''); ?>" alt="Foto de perfil" width="150">

                <form action="subir_foto_org.php" method="POST" enctype="multipart/form-data">
                    <input type="file" name="foto_perfil" accept="image/*" required>
                    <button type="submit">Subir Foto</button>
                </form>
            </div>

            <div class="profile-form">
                <h4>Actualizar Datos</h4>
                <form action="actualizar_perfil.php" method="POST">
                    <input type="hidden" name="id_usuario" value="<?php echo $usuario['_id']; ?>">

                    <label><i class="fas fa-user"></i> Nombre:</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>

                    <label><i class="fa fa-envelope"></i> Email:</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled>

                    <label><i class="fas fa-phone"></i> Teléfono:</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">

                    <label><i class="fas fa-city"></i> Ciudad:</label>
                    <select name="ciudad">
                        <option value="">Selecciona una ciudad</option>
                        <?php
                        $ciudades = [
                            "Bogotá",
                            "Medellín",
                            "Cali",
                            "Barranquilla",
                            "Cartagena",
                            "Bucaramanga",
                            "Manizales",
                            "Pereira",
                            "Santa Marta",
                            "Villavicencio"
                        ];
                        foreach ($ciudades as $ciudad) {
                            $selected = ($usuario['ciudad'] ?? '') === $ciudad ? 'selected' : '';
                            echo "<option value=\"$ciudad\" $selected>$ciudad</option>";
                        }
                        ?>
                    </select>

                    <label>Organización:</label>
                    <input type="text" name="organizacion" value="<?php echo htmlspecialchars($usuario['organizacion']); ?>" required>

                    <label><i class="fas fa-lock"></i> Nueva Contraseña:</label>
                    <input type="password" name="password" placeholder="Déjala en blanco si no deseas cambiarla.">

                    <label><i class="fas fa-align-left"></i> Descripción:</label>
                    <textarea name="descripcion" rows="3" required><?php echo htmlspecialchars($usuario['descripcion']); ?></textarea>

                    <button type="submit">Actualizar</button>
                </form>
            </div>
        </div>

        <h3 class="text-center">Oportunidades Publicadas</h3>
        <div class="opportunities">
            <?php foreach ($oportunidades as $oportunidad): ?>
                <div class="opportunity">
                    <h4><?php echo htmlspecialchars($oportunidad['titulo']); ?></h4>
                    <p><strong>Descripción:</strong> <?php echo htmlspecialchars($oportunidad['descripcion']); ?></p>
                    <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($oportunidad['ubicacion']); ?></p>
                    <p><strong>Ubicación(URL):</strong> <a href="<?php echo htmlspecialchars($oportunidad['url_ubicacion']); ?>" target="_blank">Ver en Google Maps</a></p>
                    <p><strong>Categoría:</strong> <?php echo htmlspecialchars($oportunidad['categoria']); ?></p>
                    <p><strong>Duración:</strong> <?php echo htmlspecialchars($oportunidad['duracion']); ?> horas</p>
                    <p><strong>Tipo de Actividad:</strong> <?php echo htmlspecialchars($oportunidad['tipo_actividad']); ?></p>
                    <?php
                    $fechaColombia = $oportunidad['fecha_inicio']->toDateTime();
                    $fechaColombia->setTimezone(new DateTimeZone('America/Bogota'));
                    ?>
                    <p><strong>Fecha y Hora de Inicio:</strong> <?php echo $fechaColombia->format('d-m-Y h:i A'); ?></p>

                    <!-- Botón para ver detalles -->
                    <a href="detalle_oportunidad.php?id=<?php echo $oportunidad['_id']; ?>" class="btn-detalle">Ver detalles</a>

                    <!-- Mostrar cantidad de postulaciones -->
                    <?php
                    $postulaciones = $database->postulaciones->countDocuments(['id_oportunidad' => $oportunidad['_id']]);
                    ?>
                    <p><strong>Postulaciones:</strong> <?php echo $postulaciones; ?></p>

                    <!-- Botón para editar -->
                    <a href="editar_oportunidad.php?id=<?php echo $oportunidad['_id']; ?>" class="btn-editar">Editar</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</body>

</html>