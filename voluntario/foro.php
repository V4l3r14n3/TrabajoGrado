<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

// Validar que el usuario esté autenticado como voluntario
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'voluntario') {
    header("Location: login.php");
    exit();
}

$idUsuario = new ObjectId($_SESSION['usuario']['_id']);
$usuario = $database->usuarios->findOne(['_id' => $idUsuario]);

$organizaciones = $database->usuarios->find(
    ['tipo_usuario' => 'organizacion'],
    ['sort' => ['nombre' => 1]]
)->toArray();

$mensajes = $database->foro->find([], ['sort' => ['fecha' => -1]])->toArray();

// Asegurar que $hayNotificaciones esté definida
$hayNotificaciones = false;

// Obtener las notificaciones no leídas
$notificaciones = $database->notificaciones->find([
    'id_usuario' => $idUsuario,
    'leido' => false
])->toArray();

if (!empty($notificaciones)) {
    $hayNotificaciones = true;
}

// Marcar como leídas las notificaciones cuando el voluntario las vea
$database->notificaciones->updateMany(
    ['id_usuario' => $idUsuario, 'leido' => false],
    ['$set' => ['leido' => true]]
);

// Procesar nuevo mensaje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensaje'], $_POST['organizacion'])) {
    $idOrganizacion = new ObjectId($_POST['organizacion']);
    $organizacion = $database->usuarios->findOne(['_id' => $idOrganizacion]);

    $nuevoMensaje = [
        'id_usuario' => $idUsuario,
        'nombre' => $usuario['nombre'],
        'mensaje' => htmlspecialchars(trim($_POST['mensaje'])),
        'organizacion' => [
            '_id' => $organizacion['_id'],
            'nombre' => $organizacion['nombre'],
            'organizacion' => $organizacion['organizacion']
        ],
        'fecha' => new MongoDB\BSON\UTCDateTime()
    ];
    $database->foro->insertOne($nuevoMensaje);
    // Notificar a la organización
    $database->notificaciones->insertOne([
        'id_usuario' => $organizacion['_id'],
        'tipo' => 'nuevo_mensaje_foro',
        'mensaje' => "Has recibido un nuevo mensaje de {$usuario['nombre']} en el foro.",
        'fecha' => new MongoDB\BSON\UTCDateTime(),
        'leido' => false
    ]);

    header("Location: foro.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Foro de Voluntarios</title>
    <link rel="stylesheet" href="../css/foros.css">
    <style>
        .respuesta {
            background-color: #f3f3f3;
            border-left: 4px solid #4CAF50;
            padding: 10px;
            margin-top: 10px;
            font-style: italic;
        }

        .publicacion {
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            background: #fff;
        }

        /* Estilo para el punto rojo de notificación */
        .notificaciones-punto-rojo {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <!-- Barra de navegación -->
    <?php include 'navbar_voluntario.php'; ?>

    <!-- Contenido principal -->
    <div class="container">
        <h2 class="text-center">Foro de Voluntarios</h2>

        <!-- Formulario de publicación -->
        <div class="foro-form">
            <form method="POST" action="foro.php">
                <label for="organizacion">Organización destinataria:</label>
                <select name="organizacion" id="organizacion" required>
                    <option value="" disabled selected>Selecciona una organización</option>
                    <?php foreach ($organizaciones as $org): ?>
                        <option value="<?php echo $org['_id']; ?>">
                            <?php echo htmlspecialchars($org['nombre'] . ' - ' . $org['organizacion']); ?>
                        </option>

                    <?php endforeach; ?>
                </select>

                <label for="mensaje">Escribe tu mensaje:</label>
                <textarea name="mensaje" id="mensaje" required placeholder="Comparte ideas, dudas o experiencias..."></textarea>

                <button type="submit">Publicar</button>
            </form>
        </div>

        <!-- Dentro del foreach de mensajes (reemplaza tu bloque actual de mensajes por este): -->
            <div class="publicaciones">
    <?php foreach ($mensajes as $msg): ?>
        <?php
        $autor = $database->usuarios->findOne(['_id' => $msg['id_usuario']]);
        $fotoPerfil = isset($autor['foto_perfil']) && !empty($autor['foto_perfil']) ? '../uploads/' . $autor['foto_perfil'] : '../img/perfil_default.png';
        ?>
        <div class="publicacion">
            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="perfil_voluntario.php?id=<?= $autor['_id'] ?>">
                    <img src="<?= $fotoPerfil ?>" alt="Perfil" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                </a>
                <a href="perfil_voluntario.php?id=<?= $autor['_id'] ?>">
                    <h4 style="margin: 0;"><?= htmlspecialchars($msg['nombre']) ?></h4>
                </a>
            </div>
            <small><strong>Organización:</strong> <?= htmlspecialchars($msg['organizacion']['organizacion'] ?? 'General'); ?></small>
            <p><?= htmlspecialchars($msg['mensaje']); ?></p>
            <small>
                <?php
                $date = $msg['fecha']->toDateTime()->setTimezone(new DateTimeZone('America/Bogota'));
                echo $date->format('d/m/Y H:i');
                ?>
            </small>

            <?php if (isset($msg['respuesta'])): ?>
                <div class="respuesta">
                    <strong>Respuesta de <?= htmlspecialchars($msg['organizacion']['organizacion']); ?>:</strong>
                    <p><?= htmlspecialchars($msg['respuesta']); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>


    </div>

</body>

</html>