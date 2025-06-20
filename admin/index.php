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

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id"], $_POST["accion"])) {
    $idOrg = $_POST["id"];
    $accion = $_POST["accion"];
    $estado = "";
    $mensaje = "";

    if ($accion === "aprobar") {
        $estado = "aprobado";
        $verificado = true;
        $mensaje = "✅ Tu solicitud de verificación ha sido aprobada. Ya puedes acceder al sistema como organización verificada.";

        $coleccion->updateOne(
            ["_id" => new ObjectId($idOrg)],
            [
                '$set' => [
                    "estado_verificacion" => $estado,
                    "verificado" => $verificado
                ],
                '$unset' => [
                    "documento_rechazado" => "" // limpiar rechazo previo
                ]
            ]
        );

    } elseif ($accion === "rechazar" && !empty(trim($_POST["mensaje_rechazo"]))) {
        $estado = "rechazado";
        $verificado = false;
        $mensaje = "❌ Tu solicitud de verificación ha sido rechazada. Motivo: " . trim($_POST["mensaje_rechazo"]);

        $orgActual = $coleccion->findOne(["_id" => new ObjectId($idOrg)]);
        $documentoActual = $orgActual["documento_link"] ?? null;

        $coleccion->updateOne(
            ["_id" => new ObjectId($idOrg)],
            ['$set' => [
                "estado_verificacion" => $estado,
                "verificado" => $verificado,
                "documento_rechazado" => $documentoActual
            ]]
        );
    }

    // Notificación
    if ($estado && $mensaje) {
        $coleccionNotificaciones->insertOne([
            "id_usuario" => new ObjectId($idOrg),
            "mensaje" => $mensaje,
            "fecha" => new MongoDB\BSON\UTCDateTime(),
            "leido" => false
        ]);
    }

    header("Location: index.php");
    exit();
}

// Mostrar organizaciones pendientes o rechazadas con nuevo documento
$organizaciones = $coleccion->find([
    "tipo_usuario" => "organizacion",
    '$or' => [
        ["estado_verificacion" => "pendiente"],
        [
            "estado_verificacion" => "rechazado",
            '$expr' => [
                '$ne' => ['$documento_link', '$documento_rechazado']
            ]
        ]
    ]
]);

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
    <title>Administración - Verificación</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<?php include 'navbar_admin.php'; ?>

<main class="container">
    <h1>Verificación de Organizaciones</h1>

    <?php if ($pendientes > 0): ?>
        <div class="alerta">Hay <strong><?= $pendientes ?></strong> organización(es) pendientes de verificación.</div>
    <?php else: ?>
        <div class="alerta" style="background-color: #d4edda; color: #155724;">No hay organizaciones pendientes.</div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th title="Nombre completo del solicitante">👤 Nombre</th>
                <th title="Correo electrónico de contacto">✉️ Correo</th>
                <th title="Documento de respaldo">📄 Enlace</th>
                <th title="Estado actual de verificación">🔎 Estado</th>
                <th title="Aprobar o rechazar la solicitud">⚙️ Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($organizaciones as $org): ?>
                <tr>
                    <td><?= htmlspecialchars($org["nombre"] ?? "Sin nombre") ?></td>
                    <td><?= htmlspecialchars($org["email"] ?? "Sin correo") ?></td>
                    <td>
                        <?php if (!empty($org["documento_link"])): ?>
                            <a href="<?= htmlspecialchars($org["documento_link"]) ?>" target="_blank">Ver Enlace</a>
                        <?php else: ?>
                            No disponible
                        <?php endif; ?>
                    </td>
                    <td><?= ucfirst($org["estado_verificacion"] ?? "pendiente") ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $org["_id"] ?>">
                            <input type="hidden" name="accion" value="aprobar">
                            <button type="submit" class="btn-aprobar">Aprobar</button>
                        </form>

                        <form method="POST" onsubmit="return confirmarRechazo(this);" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $org["_id"] ?>">
                            <textarea name="mensaje_rechazo" placeholder="Motivo del rechazo..." style="display:none;" required></textarea>
                            <button type="button" class="btn-rechazar" onclick="mostrarTextarea(this)">Rechazar</button>
                            <button type="submit" name="accion" value="rechazar" class="btn-rechazar" style="display:none;">Confirmar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</main>

<script>
    function mostrarTextarea(boton) {
        const form = boton.closest("form");
        const textarea = form.querySelector("textarea");
        const confirmarBtn = form.querySelector("button[type='submit']");
        textarea.style.display = "block";
        confirmarBtn.style.display = "inline-block";
        boton.style.display = "none";
        textarea.focus();
    }

    function confirmarRechazo(formulario) {
        const textarea = formulario.querySelector("textarea");
        if (!textarea.value.trim()) {
            alert("Por favor, indica el motivo del rechazo.");
            return false;
        }
        return true;
    }
</script>

</body>
</html>
