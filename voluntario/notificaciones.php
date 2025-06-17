<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

// Verificar si es voluntario
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'voluntario') {
    header("Location: login.php");
    exit();
}

$idVoluntario = new ObjectId($_SESSION['usuario']['_id']);

// Obtener todas las notificaciones del voluntario (leídas y no leídas)
$notificaciones = $database->notificaciones->find(
    ['id_usuario' => $idVoluntario],
    ['sort' => ['fecha' => -1]]
)->toArray();

// Marcar como leídas todas las notificaciones después de mostrar
$database->notificaciones->updateMany(
    ['id_usuario' => $idVoluntario, 'leido' => false],
    ['$set' => ['leido' => true]]
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Notificaciones</title>
    <link rel="stylesheet" href="../css/foros.css">
    <style>
        .notificacion {
            border: 1px solid #ccc;
            padding: 12px;
            margin: 10px 0;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .no-hay {
            color: #888;
            font-style: italic;
            text-align: center;
        }
        .notificaciones-punto-rojo {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>

<?php include 'navbar_voluntario.php'; ?>

<div class="container">
    <h2>Mis Notificaciones</h2>

    <?php if (empty($notificaciones)): ?>
        <p class="no-hay">No tienes notificaciones aún.</p>
    <?php else: ?>
        <?php foreach ($notificaciones as $notif): ?>
            <div class="notificacion">
                <p><?php echo htmlspecialchars($notif['mensaje']); ?></p>
                <small>
                    <?php
                    $fecha = $notif['fecha']->toDateTime()->setTimezone(new DateTimeZone('America/Bogota'));
                    echo $fecha->format('d/m/Y H:i');
                    ?>
                </small>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
