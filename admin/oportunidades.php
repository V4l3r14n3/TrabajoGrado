<?php
session_start();
require '../conexion.php';

$coleccion = $database->oportunidades;

if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["tipo_usuario"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET["eliminar"])) {
    $idEliminar = new MongoDB\BSON\ObjectId($_GET["eliminar"]);
    $oportunidad = $coleccion->findOne(['_id' => $idEliminar]);

    if ($oportunidad && isset($oportunidad['fecha_inicio'])) {
        $fechaOportunidad = $oportunidad['fecha_inicio']->toDateTime();
        $fechaOportunidad->setTimezone(new DateTimeZone('America/Bogota'));

        $hoy = new DateTime('today', new DateTimeZone('America/Bogota'));

        if ($fechaOportunidad > $hoy) {
            // Solo se elimina si es futura
            $notificacion = [
                'id_usuario' => $oportunidad['creado_por'],
                'tipo' => 'eliminacion_oportunidad',
                'mensaje' => "El administrador ha eliminado tu oportunidad \"{$oportunidad['titulo']}\".",
                'fecha' => new MongoDB\BSON\UTCDateTime(),
                'leido' => false
            ];
            $database->notificaciones->insertOne($notificacion);
            $coleccion->deleteOne(["_id" => $idEliminar]);

            $_SESSION['mensaje'] = "Oportunidad eliminada exitosamente.";
        } else {
            $_SESSION['mensaje'] = "No puedes eliminar oportunidades pasadas o del día de hoy.";
        }
    } else {
        $_SESSION['mensaje'] = "Oportunidad no encontrada o sin fecha válida.";
    }

    header("Location: oportunidades.php");
    exit();
}

$filtro = [];

if (!empty($_GET["busqueda"])) {
    $busqueda = trim($_GET["busqueda"]);
    $filtro['$or'] = [
        ['titulo' => ['$regex' => $busqueda, '$options' => 'i']],
        ['ubicacion' => ['$regex' => $busqueda, '$options' => 'i']],
        ['categoria' => ['$regex' => $busqueda, '$options' => 'i']],
        ['nombre_organizacion' => ['$regex' => $busqueda, '$options' => 'i']],
    ];
}


$oportunidades = $coleccion->find($filtro, ['sort' => ['fecha_creacion' => -1]]);

function nombreCategoria($categoria)
{
    $nombres = [
        'educacion' => 'Educación',
        'medio_ambiente' => 'Medio Ambiente',
        'animal' => 'Animal',
        'salud' => 'Salud',
        'social' => 'Trabajo Social',
        'tecnologia' => 'Tecnología'
    ];
    return $nombres[$categoria] ?? ucfirst($categoria);
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Oportunidades</title>
    <link rel="stylesheet" href="../css/admin_infos.css">
    <style>
        .btn-ver,
        .btn-img,
        .btn-eliminar {
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

        .btn-eliminar {
            background-color: #e74c3c;
        }

        .btn-eliminar:hover {
            background-color: #c0392b;
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
        }

        .modal-content {
            background: white;
            max-width: 90%;
            max-height: 90%;
            overflow-y: auto;
            padding: 20px;
            border-radius: 10px;
            position: relative;
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

        img.oportunidad-img {
            width: 100%;
            max-height: 300px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ccc;
            transition: transform 0.2s ease;
        }

        img.oportunidad-img:hover {
            transform: scale(1.02);
        }
    </style>
</head>

<body>

    <?php include 'navbar_admin.php'; ?>

    <div class="contenedor">
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div style="background-color: #f1f1f1; padding: 10px; margin-bottom: 15px; border-left: 5px solid #007BFF;">
                <?= htmlspecialchars($_SESSION['mensaje']) ?>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>

        <h1>Oportunidades Publicadas</h1>
        <form method="GET" class="filtro-buscador">
            <input type="text" name="busqueda" placeholder="Buscar por título, ubicación o categoría" value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
            <button type="submit">Buscar</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>Organización</th>
                    <th>Categoria</th>
                    <th>Inicio</th>
                    <th>Ubicación</th>
                    <th>Imagen</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($oportunidades as $index => $oportunidad): ?>
                    <tr>
                        <td><?= htmlspecialchars($oportunidad["titulo"]) ?></td>
                        <td><a class="btn-ver" href="#" onclick="mostrarModal('desc<?= $index ?>'); return false;">Ver</a></td>
                        <td><?= htmlspecialchars($oportunidad["nombre_organizacion"] ?? 'Desconocida') ?></td>
                        <td><?= nombreCategoria($oportunidad["categoria"] ?? '') ?></td>
                        <td>
                            <?php
                            if (isset($oportunidad["fecha_inicio"])) {
                                $fecha = $oportunidad["fecha_inicio"]->toDateTime();
                                $fecha->setTimezone(new DateTimeZone('America/Bogota'));
                                echo $fecha->format('d/m/Y H:i');
                            } else {
                                echo "Sin fecha";
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($oportunidad["ubicacion"] ?? 'Desconocida') ?></td>
                        <td>
                            <a class="btn-img" href="#" onclick="mostrarModal('img<?= $index ?>'); return false;">Ver imagen</a>
                        </td>
                        <td>
                            <a class="btn-eliminar" href="oportunidades.php?eliminar=<?= $oportunidad["_id"] ?>" onclick="return confirm('¿Eliminar esta oportunidad?')">Eliminar</a>
                        </td>
                    </tr>

                    <!-- Modal de Descripción -->
                    <div id="desc<?= $index ?>" class="modal">
                        <div class="modal-content">
                            <button class="modal-close" onclick="cerrarModal('desc<?= $index ?>')">X</button>
                            <h2><?= htmlspecialchars($oportunidad["titulo"]) ?></h2>
                            <p><?= nl2br(htmlspecialchars($oportunidad["descripcion"])) ?></p>
                        </div>
                    </div>

                    <!-- Modal de Imagen -->
                    <div id="img<?= $index ?>" class="modal">
                        <div class="modal-content">
                            <button class="modal-close" onclick="cerrarModal('img<?= $index ?>')">X</button>
                            <h3>Imagen de la oportunidad</h3>
                            <img class="oportunidad-img" src="<?= htmlspecialchars($oportunidad["imagen"] ?? '') ?>" alt="Imagen de la oportunidad">
                        </div>
                    </div>
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