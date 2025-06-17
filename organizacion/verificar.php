<?php
require '../conexion.php';
require '../vendor/autoload.php';
use MongoDB\BSON\ObjectId;

session_start();

// Verificar si el usuario está autenticado y es una organización
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["tipo_usuario"] !== "organizacion") {
    header("Location: ../login.php");
    exit();
}

// Obtener datos de la organización desde la sesión
$organizacion_id = $_SESSION["usuario"]["_id"];
$coleccion = $database->usuarios;
$organizacion = $coleccion->findOne(["_id" => new ObjectId($organizacion_id)]);

// Si ya está verificada, redirigir al panel de la organización
if (!empty($organizacion["verificado"])) {
    header("Location: index.php");
    exit();
}

// Mensaje de retroalimentación para el usuario
$mensaje = "";

// Bloquear el formulario si ya hay una solicitud en curso o aprobada
$estado = $organizacion["estado_verificacion"] ?? "";
$bloquearFormulario = ($estado !== "" && $estado !== "rechazado");

// Procesar el formulario solo si no está bloqueado
if (!$bloquearFormulario && $_SERVER["REQUEST_METHOD"] === "POST") {
    $linkDocumento = trim($_POST["link_documento"] ?? "");

    // Validar que sea un URL válido y permitido (ej. Google Drive o Dropbox)
    if (filter_var($linkDocumento, FILTER_VALIDATE_URL) && 
        (strpos($linkDocumento, "drive.google.com") !== false || strpos($linkDocumento, "dropbox.com") !== false)) {
        
        // Guardar el enlace y marcar estado como pendiente
        $coleccion->updateOne(
            ["_id" => new ObjectId($organizacion_id)],
            ['$set' => [
                "documento_link" => $linkDocumento,
                "estado_verificacion" => "pendiente",
                "fecha_envio_verificacion" => new MongoDB\BSON\UTCDateTime()
            ]]
        );

        $mensaje = "<div id='mensajeExito' class='mensaje-exito'>
                        ✅ Enlace enviado correctamente. Espera la validación del administrador.
                    </div>";
        $bloquearFormulario = true; // Para evitar nuevo envío en la misma sesión
    } else {
        $mensaje = "<div id='mensajeError' class='mensaje-error'>
                        ❌ Error: El enlace ingresado no es válido o no es de un proveedor permitido.
                    </div>";
    }
}

// Obtener la notificación más reciente de esta organización
$coleccionNotificaciones = $database->notificaciones;
$notificacion = $coleccionNotificaciones->findOne(
    ["id_usuario" => new ObjectId($organizacion_id)],
    ['sort' => ['fecha' => -1]]
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Organización</title>
    <link rel="stylesheet" href="../css/verificar.css">
    <style>
        .mensaje-exito, .mensaje-error, .mensaje-info {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .mensaje-exito {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .mensaje-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .mensaje-info {
            background-color: #e8f0fe;
            color: #1a73e8;
            border: 1px solid #a0c0f8;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            setTimeout(function () {
                let mensaje = document.getElementById("mensajeExito") || document.getElementById("mensajeError");
                if (mensaje) {
                    mensaje.style.opacity = "0";
                    setTimeout(() => mensaje.style.display = "none", 500);
                }
            }, 4000);
        });
    </script>
</head>
<body>
    <div class="container">
        <h2>Verificación de Organización</h2>
        <p>Para acceder al sistema, es necesario completar el proceso de verificación.</p>

        <?= $mensaje ?>

        <?php if ($notificacion): ?>
            <div class="mensaje-info">
                <?php echo htmlspecialchars($notificacion["mensaje"]); ?>
            </div>
        <?php endif; ?>

        <?php if (!$bloquearFormulario): ?>
            <form method="POST" action="verificar.php">
                <label for="link_documento">Enlace al documento de validación:</label>
                <input type="url" name="link_documento" id="link_documento" placeholder="https://drive.google.com/..." required>
                <small>Solo se aceptan enlaces de Google Drive o Dropbox.</small>
                <button type="submit">Enviar para verificación</button>
            </form>
        <?php else: ?>
            <p style="margin-top: 15px; color: #666;">Ya has enviado tu solicitud de verificación. Espera a que sea revisada.</p>
        <?php endif; ?>
    </div>
</body>
</html>
