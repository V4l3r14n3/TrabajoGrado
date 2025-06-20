<?php
session_start();
require '../conexion.php';

$coleccion = $database->blogs;

if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["tipo_usuario"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET["eliminar"])) {
    $idEliminar = new MongoDB\BSON\ObjectId($_GET["eliminar"]);
    $blog = $coleccion->findOne(['_id' => $idEliminar]);

    if ($blog) {
        // Insertar notificación para la organización creadora
        if (isset($blog['creado_por']) && $blog['creado_por'] instanceof MongoDB\BSON\ObjectId) {
            $notificacion = [
                'id_usuario' => $blog['creado_por'],
                'tipo' => 'eliminacion_blog',
                'mensaje' => "El administrador ha eliminado tu blog titulado \"{$blog['titulo']}\".",
                'fecha' => new MongoDB\BSON\UTCDateTime(),
                'leido' => false
            ];
            $database->notificaciones->insertOne($notificacion);
        }

        // Eliminar el blog
        $coleccion->deleteOne(["_id" => $idEliminar]);
        $_SESSION['mensaje'] = "Blog eliminado exitosamente.";
    } else {
        $_SESSION['mensaje'] = "Blog no encontrado.";
    }

    header("Location: blogs.php");
    exit();
}

$blogs = $coleccion->find([], ['sort' => ['fecha_creacion' => -1]]);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Blogs</title>
    <link rel="stylesheet" href="../css/admin_infos.css">
    <style>
        .btn-ver,
        .btn-img {
            display: inline-block;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            color: white;
            transition: background-color 0.2s ease;
        }

        .btn-ver {
            background-color: #3498db;
        }

        .btn-ver:hover {
            background-color: #2980b9;
        }

        .btn-img {
            background-color: #9b59b6;
        }

        .btn-img:hover {
            background-color: #8e44ad;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .modal-content {
            background: white;
            max-width: 90%;
            max-height: 90%;
            overflow-y: auto;
            padding: 20px;
            border-radius: 10px;
            position: relative;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            background: #333;
            color: white;
            border: none;
            font-size: 18px;
            cursor: pointer;
            padding: 4px 10px;
            border-radius: 4px;
        }

        .img-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .img-grid img {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ccc;
            transition: transform 0.2s ease;
        }

        .img-grid img:hover {
            transform: scale(1.02);
        }
    </style>
</head>

<body>
    <?php include 'navbar_admin.php'; ?>

    <div class="contenedor">
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <?= $_SESSION['mensaje'] ?>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>

        <h1>Blogs Publicados</h1>
        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Contenido</th>
                    <th>Organización</th>
                    <th>Fecha</th>
                    <th>Imágenes</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blogs as $index => $blog): ?>
                    <tr>
                        <td data-label="Titulo"><?= htmlspecialchars($blog["titulo"]) ?></td>
                        <td data-label="Contenido">
                            <?= nl2br(htmlspecialchars(mb_strimwidth($blog["contenido"], 0, 150, "..."))) ?>
                        </td>
                        <td data-label="Organización"><?= htmlspecialchars($blog["nombre_organizacion"] ?? 'Desconocida') ?></td>
                        <td data-label="Fecha">
                            <?php
                            if (isset($blog["fecha_creacion"])) {
                                $fechaUTC = $blog["fecha_creacion"]->toDateTime();
                                $fechaUTC->setTimezone(new DateTimeZone('America/Bogota')); // Ajusta según tu zona
                                echo $fechaUTC->format('d/m/Y H:i');
                            } else {
                                echo "Sin fecha";
                            }
                            ?>
                        </td>
                        <td data-label="Imágenes">
                            <?php if (!empty($blog["imagenes"])): ?>
                                <a class="btn-img" href="#" onclick="mostrarModal('imagenes<?= $index ?>'); return false;">Ver imágenes</a>
                            <?php else: ?>
                                No disponible
                            <?php endif; ?>
                        </td>
                        <td data-label="Acción">
                            <a class="btn-eliminar" href="blogs.php?eliminar=<?= $blog["_id"] ?>" onclick="return confirm('¿Seguro que deseas eliminar este blog?')">Eliminar</a>
                        </td>
                    </tr>

                    <!-- Modal de Imágenes -->
                    <?php if (!empty($blog["imagenes"])): ?>
                        <div id="imagenes<?= $index ?>" class="modal">
                            <div class="modal-content">
                                <button class="modal-close" onclick="cerrarModal('imagenes<?= $index ?>')">X</button>
                                <h3>Imágenes del blog</h3>
                                <div class="img-grid">
                                    <?php foreach ($blog["imagenes"] as $img): ?>
                                        <img src="<?= htmlspecialchars($img) ?>" alt="Imagen del blog">
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function mostrarModal(id) {
            document.getElementById(id).style.display = "flex";
        }

        function cerrarModal(id) {
            document.getElementById(id).style.display = "none";
        }

        document.addEventListener("click", function(e) {
            if (e.target.classList.contains("modal")) {
                e.target.style.display = "none";
            }
        });
    </script>
</body>

</html>