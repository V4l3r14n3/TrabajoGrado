<?php
session_start();
require '../conexion.php';

// Verificar si el usuario está autenticado y es admin
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["tipo_usuario"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

// Eliminar foro si se envía el ID por GET
if (isset($_GET["eliminar"])) {
    $idEliminar = new MongoDB\BSON\ObjectId($_GET["eliminar"]);
    $database->foro->deleteOne(["_id" => $idEliminar]);
    header("Location: foros.php"); // Asegúrate que este sea el nombre correcto
    exit();
}

// Obtener foros
$foros = $database->foro->find([], ['sort' => ['fecha' => -1]]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Foros</title>
    <link rel="stylesheet" href="../css/admin_infos.css">
</head>
<body>
<?php include 'navbar_admin.php'; ?>
<div class="contenedor">
    <h1>Foros Publicados</h1>
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Mensaje</th>
                <th>Organización</th>
                <th>Respuesta</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($foros as $foro): ?>
                <tr>
                    <td data-label="Nombre"><?= htmlspecialchars($foro["nombre"] ?? 'Sin nombre') ?></td>
                    <td data-label="Mensaje"><?= htmlspecialchars($foro["mensaje"] ?? '') ?></td>
                    <td data-label="Organización"><?= htmlspecialchars($foro["organizacion"]["organizacion"] ?? 'No disponible') ?></td>
                    <td data-label="Respuesta"><?= htmlspecialchars($foro["respuesta"] ?? 'Sin respuesta') ?></td>
                    <td data-label="Acción">
                        <a class="btn-eliminar" href="foros.php?eliminar=<?= $foro["_id"] ?>" onclick="return confirm('¿Seguro que deseas eliminar este foro?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
</body>
</html>
