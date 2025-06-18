<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$usuarioId = new ObjectId($_SESSION['usuario']['_id']);

// Marcar como leídas
$database->notificaciones->updateMany(
    ['id_usuario' => $usuarioId, 'leido' => false],
    ['$set' => ['leido' => true]]
);

// Obtener notificaciones (recientes primero)
$notificaciones = $database->notificaciones->find(
    ['id_usuario' => $usuarioId],
    ['sort' => ['fecha' => -1]]
);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Notificaciones</title>
    <link rel="stylesheet" href="../css/noti_org.css">
</head>
<?php include 'navbar_org.php'; ?>

<body>
    <h2>Tus Notificaciones</h2>

    <ul class="lista-notis">
        <?php foreach ($notificaciones as $noti): ?>
            <li>
                <p><?php echo htmlspecialchars($noti['mensaje']); ?></p>
                <small><?php
                        $fecha = $noti['fecha']->toDateTime()->setTimezone(new DateTimeZone('America/Bogota'));
                        echo $fecha->format('d/m/Y H:i');
                        ?>
                </small>
            </li>
        <?php endforeach; ?>
    </ul>
</body>

</html>