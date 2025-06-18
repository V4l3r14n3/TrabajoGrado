<?php
session_start();
require '../conexion.php';

// Verificar si el usuario está autenticado y es una organización
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["tipo_usuario"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

// Eliminar usuario si se envía el ID por GET
if (isset($_GET["eliminar"])) {
    $idEliminar = new MongoDB\BSON\ObjectId($_GET["eliminar"]);
    $coleccion = $database->usuarios;
    $resultado = $coleccion->deleteOne(["_id" => $idEliminar]);
    header("Location: organizacion.php");
    exit();
}

// Obtener usuarios de tipo "organizacion"
$usuarios = $database->usuarios->find(["tipo_usuario" => "organizacion"]);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Organizaciones</title>
    <link rel="stylesheet" href="../css/admin_infos.css">
</head>

<body>
    <!-- Barra de navegación -->
    <?php include 'navbar_admin.php'; ?>
    <div class="contenedor">
        <h1>Organizaciones Registradas</h1>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Organización</th>
                    <th>Descripción</th>
                    <th>Documentos</th>
                    <th>Verificado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= htmlspecialchars($usuario["nombre"]) ?></td>
                        <td><?= htmlspecialchars($usuario["email"]) ?></td>
                        <td><?= htmlspecialchars($usuario["organizacion"] ?? '') ?></td>
                        <td><?= htmlspecialchars($usuario["descripcion"] ?? '') ?></td>
                        <td>
                            <?php if (!empty($usuario["documento_link"])): ?>
                                <a href="<?= htmlspecialchars($usuario["documento_link"]) ?>" target="_blank">Ver documento</a>
                            <?php else: ?>
                                No disponible
                            <?php endif; ?>
                        </td>
                        <td><?= $usuario["verificado"] ? "Sí" : "No" ?></td>
                        <td>
                            <a class="btn-eliminar" href="organizacion.php?eliminar=<?= $usuario["_id"] ?>" onclick="return confirm('¿Seguro que deseas eliminar este usuario?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</body>

</html>