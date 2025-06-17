<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// Validar que el usuario sea una organización
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'organizacion') {
    header("Location: login.php");
    exit();
}

$idOrganizacion = new ObjectId($_SESSION['usuario']['_id']);
$nombreOrganizacion = $_SESSION['usuario']['organizacion'];

// Obtener mensajes dirigidos a esta organización
$mensajes = $database->foro->find([
    'organizacion._id' => $idOrganizacion
], ['sort' => ['fecha' => -1]])->toArray();

// Procesar la respuesta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_mensaje'], $_POST['respuesta'])) {
    $idMensaje = new ObjectId($_POST['id_mensaje']);
    $respuestaTexto = htmlspecialchars(trim($_POST['respuesta']));

    // Guardar la respuesta en el mensaje
    $database->foro->updateOne(
        ['_id' => $idMensaje],
        [
            '$set' => [
                'respuesta' => $respuestaTexto,
                'fecha_respuesta' => new UTCDateTime()
            ]
        ]
    );

    // Obtener el mensaje original para saber a qué voluntario notificar
    $mensajeOriginal = $database->foro->findOne(['_id' => $idMensaje]);
    $idVoluntario = $mensajeOriginal['id_usuario'];

    // Insertar notificación para el voluntario
    $database->notificaciones->insertOne([
        'id_usuario' => $idVoluntario,
        'tipo' => 'respuesta_foro',
        'mensaje' => "La organización {$nombreOrganizacion} respondió a tu mensaje en el foro.",
        'fecha' => new UTCDateTime(),
        'leido' => false
    ]);

    header("Location: foro_org.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Foro de la Organización</title>
    <link rel="stylesheet" href="../css/foros.css">
</head>

<body>
    <!-- Barra de navegación -->
    <?php include 'navbar_org.php'; ?>


    <div class="container">
        <h2>Mensajes del Foro Recibidos</h2>

        <?php if (empty($mensajes)): ?>
            <p>No hay mensajes dirigidos a tu organización aún.</p>
        <?php else: ?>
            <?php foreach ($mensajes as $msg): ?>
                <div class="publicacion">
                    <h4><?php echo htmlspecialchars($msg['nombre']); ?> escribió:</h4>
                    <p><?php echo htmlspecialchars($msg['mensaje']); ?></p>
                    <?php
                    $date = $msg['fecha']->toDateTime()->setTimezone(new DateTimeZone('America/Bogota'));
                    echo "<small>" . $date->format('d/m/Y H:i') . "</small>";
                    ?>


                    <?php if (!isset($msg['respuesta'])): ?>
                        <form method="POST" class="respuesta-form">
                            <input type="hidden" name="id_mensaje" value="<?php echo $msg['_id']; ?>">
                            <textarea name="respuesta" required placeholder="Escribe tu respuesta..."></textarea>
                            <button type="submit">Responder</button>
                        </form>
                    <?php else: ?>
                        <div class="respuesta">
                            <strong>Tu respuesta:</strong>
                            <p><?php echo htmlspecialchars($msg['respuesta']); ?></p>
                            <?php
                            $respuestaDate = $msg['fecha_respuesta']->toDateTime()->setTimezone(new DateTimeZone('America/Bogota'));
                            echo "<small>Respondido el " . $respuestaDate->format('d/m/Y H:i') . "</small>";
                            ?>

                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>

</html>