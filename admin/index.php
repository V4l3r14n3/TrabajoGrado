<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

// Verificar si el usuario es un administrador
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["tipo_usuario"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

$coleccion = $database->usuarios;
$coleccionNotificaciones = $database->notificaciones;

// Procesar formulario si se envió
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"], $_POST["accion"])) {
    $idOrg = $_POST["id"];
    $accion = $_POST["accion"];

    $estado = "";
    $mensaje = "";

    if ($accion === "aprobar") {
        $estado = "aprobado";
        $verificado = true;
        $mensaje = "✅ Tu solicitud de verificación ha sido aprobada. Ya puedes acceder al sistema como organización verificada.";
    } elseif ($accion === "rechazar" && !empty(trim($_POST["mensaje_rechazo"]))) {
        $estado = "rechazado";
        $verificado = false;
        $mensaje = "❌ Tu solicitud de verificación ha sido rechazada. Motivo: " . trim($_POST["mensaje_rechazo"]);
    }
    
    if ($estado && $mensaje) {
        // Actualiza estado del usuario
        $coleccion->updateOne(
            ["_id" => new ObjectId($idOrg)],
            ['$set' => [
                "estado_verificacion" => $estado,
                "verificado" => $verificado
            ]]
        );

        // Inserta notificación
        $coleccionNotificaciones->insertOne([
            "id_usuario" => new ObjectId($idOrg),
            "mensaje" => $mensaje,
            "fecha" => new MongoDB\BSON\UTCDateTime(),
            "leido" => false
        ]);
    }

    // Redireccionar para evitar reenvío del formulario
    header("Location: index.php");
    exit();
}

// Obtener organizaciones con estado pendiente o rechazado
$organizaciones = $coleccion->find([
    "tipo_usuario" => "organizacion",
    "estado_verificacion" => ['$in' => ["pendiente", "rechazado"]]
]);

// Contar cuántas están pendientes
$pendientes = $coleccion->count([
    "tipo_usuario" => "organizacion",
    "estado_verificacion" => "pendiente"
]);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - Verificación de Organizaciones</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            background-color: #f9f9f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .alerta {
            background-color: #ffeeba;
            border: 1px solid #f0ad4e;
            color: #856404;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .btn-aprobar {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            margin-right: 5px;
            cursor: pointer;
        }

        .btn-rechazar {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }

        .container {
            max-width: 900px;
            margin: auto;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th,
        table td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }
    </style>
</head>

<body>
    <!-- Barra de navegación -->
    <?php include 'navbar_admin.php'; ?>

    <main class="container">
        <h1>Verificación de Organizaciones</h1>

        <!-- Alerta de pendientes -->
        <?php if ($pendientes > 0): ?>
            <div class="alerta">
                Hay <strong><?php echo $pendientes; ?></strong> organización(es) pendientes de verificación.
            </div>
        <?php else: ?>
            <div class="alerta" style="background-color: #d4edda; color: #155724; border-color: #c3e6cb;">
                No hay organizaciones pendientes de verificación.
            </div>
        <?php endif; ?>

        <!-- Tabla -->
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Enlace</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($organizaciones as $org): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($org["nombre"] ?? "Sin nombre"); ?></td>
                        <td><?php echo htmlspecialchars($org["email"] ?? "Sin correo"); ?></td>
                        <td>
                            <?php if (!empty($org["documento_link"])): ?>
                                <a href="<?php echo htmlspecialchars($org["documento_link"]); ?>" target="_blank" rel="noopener noreferrer">Ver Enlace</a>
                            <?php else: ?>
                                No disponible
                            <?php endif; ?>
                        </td>
                        <td><?php echo ucfirst($org["estado_verificacion"] ?? "pendiente"); ?></td>
                        <td>
                            <!-- Formulario para Aprobar -->
                            <form action="index.php" method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $org["_id"]; ?>">
                                <input type="hidden" name="accion" value="aprobar">
                                <button type="submit" class="btn-aprobar">Aprobar</button>
                            </form>

                            <!-- Formulario para Rechazar con textarea -->
                            <form action="index.php" method="POST" onsubmit="return confirmarRechazo(this);" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $org["_id"]; ?>">
                                <textarea name="mensaje_rechazo" placeholder="Motivo del rechazo..." style="display:none; margin-top: 5px;" required></textarea>
                                <button type="button" class="btn-rechazar" onclick="mostrarTextarea(this)">Rechazar</button>
                                <button type="submit" name="accion" value="rechazar" class="btn-rechazar" style="display:none;">Confirmar Rechazo</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <script>
        function mostrarTextarea(boton) {
            const form = boton.closest("form");
            const textarea = form.querySelector("textarea[name='mensaje_rechazo']");
            const botonConfirmar = form.querySelector("button[type='submit'][value='rechazar']");

            textarea.style.display = "block";
            boton.style.display = "none";
            botonConfirmar.style.display = "inline-block";
            textarea.focus();
        }

        function confirmarRechazo(formulario) {
            const accion = formulario.querySelector("button[type='submit'][value='rechazar']");
            const textarea = formulario.querySelector("textarea[name='mensaje_rechazo']");
            if (accion && accion.style.display !== "none" && textarea.value.trim() === "") {
                alert("Por favor, indica el motivo del rechazo.");
                return false;
            }
            return true;
        }
    </script>

</body>

</html>
