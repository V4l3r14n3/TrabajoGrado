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
    header("Location: voluntario.php");
    exit();
}

// Obtener usuarios de tipo "voluntario"
$usuarios = $database->usuarios->find(["tipo_usuario" => "voluntario"]);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Voluntarios</title>
    <link rel="stylesheet" href="../css/admin_infos.css">
</head>

<body>
    <!-- Barra de navegación -->
    <?php include 'navbar_admin.php'; ?>
    <div class="contenedor">
        <h1>Voluntarios Registrados</h1>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Intereses</th>
                    <th>Habilidades</th>
                    <th>Ciudad</th>
                    <th>Teléfono</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td data-label="nombre"><?= htmlspecialchars($usuario["nombre"]) ?></td>
                        <td data-label="email"><?= htmlspecialchars($usuario["email"]) ?></td>
                        <td data-label="intereses">
                            <?php
                            $interesesRaw = $usuario["intereses"] ?? [];

                            // Convertir BSONArray a array PHP nativo
                            if ($interesesRaw instanceof MongoDB\Model\BSONArray) {
                                $intereses = iterator_to_array($interesesRaw);
                            } elseif (is_array($interesesRaw)) {
                                $intereses = $interesesRaw;
                            } else {
                                $intereses = [];
                            }

                            $opcionesIntereses = [
                                'educacion' => 'Educación',
                                'medio_ambiente' => 'Medio Ambiente',
                                'animal' => 'Animal',
                                'salud' => 'Salud',
                                'social' => 'Trabajo Social',
                                'tecnologia' => 'Tecnología'
                            ];

                            if (!empty($intereses)) {
                                $nombresIntereses = array_map(function ($int) use ($opcionesIntereses) {
                                    return $opcionesIntereses[$int] ?? $int;
                                }, $intereses);
                                echo htmlspecialchars(implode(", ", $nombresIntereses));
                            } else {
                                echo 'No registrados';
                            }
                            ?>
                        </td>

                        <td data-label="habilidades"><?= htmlspecialchars($usuario["habilidades"] ?? '') ?></td>
                        <td data-label="ciudad"><?= htmlspecialchars($usuario["ciudad"] ?? '') ?></td>
                        <td data-label="telefono"><?= htmlspecialchars($usuario["telefono"] ?? '') ?></td>
                        <td data-label="acción">
                            <a class="btn-eliminar" href="voluntario.php?eliminar=<?= $usuario["_id"] ?>" onclick="return confirm('¿Seguro que deseas eliminar este usuario?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</body>

</html>