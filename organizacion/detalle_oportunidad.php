<?php
session_start();
require '../conexion.php';

use MongoDB\BSON\ObjectId;

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Verificar si se envió el ID de la oportunidad
if (!isset($_GET['id'])) {
    echo "Oportunidad no encontrada.";
    exit();
}

$idOportunidad = new ObjectId($_GET['id']);
$coleccionOportunidades = $database->oportunidades;
$coleccionPostulaciones = $database->postulaciones;
$coleccionUsuarios = $database->usuarios;

// Obtener la oportunidad
$oportunidad = $coleccionOportunidades->findOne(['_id' => $idOportunidad]);

if (!$oportunidad) {
    echo "Oportunidad no encontrada.";
    exit();
}

// Verificar que el usuario sea una organización y que haya creado esta oportunidad
if (
    !isset($_SESSION['usuario']['tipo_usuario']) ||
    $_SESSION['usuario']['tipo_usuario'] !== 'organizacion' ||
    (string)(new ObjectId($_SESSION['usuario']['_id'])) !== (string)$oportunidad['creado_por']
) {
    echo "Acceso denegado.";
    exit();
}

// Obtener postulaciones de esta oportunidad
$postulaciones = $coleccionPostulaciones->find(['id_oportunidad' => $idOportunidad])->toArray();

// Mensajes
$mensajeExito = $_SESSION['success_message'] ?? null;
$mensajeError = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($oportunidad['titulo']); ?></title>
    <link rel="stylesheet" href="../css/detalles_oportunidad.css">
    <style>
        .download-buttons {
            margin-top: 20px;
            display: flex;
            gap: 15px;
            justify-content: flex-start;
        }

        .btn-download {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .btn-download img {
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }

        .btn-download.pdf {
            background-color: #dc3545;
        }

        .btn-download.pdf:hover {
            background-color: #b02a37;
        }

        .btn-download.excel {
            background-color: #28a745;
        }

        .btn-download.excel:hover {
            background-color: #1e7e34;
        }
    </style>
</head>

<body>

    <!-- Barra de navegación -->
    <?php include 'navbar_org.php'; ?>

    <div class="container">
        <?php if ($mensajeExito): ?>
            <div class="mensaje-exito"><?php echo $mensajeExito; ?></div>
        <?php endif; ?>

        <?php if ($mensajeError): ?>
            <div class="mensaje-error"><?php echo $mensajeError; ?></div>
        <?php endif; ?>


        <div class="opportunity-detail">
            <div class="image">
                <img src="<?php echo htmlspecialchars($oportunidad['imagen']); ?>" alt="Imagen de la oportunidad">
            </div>
            <div class="info">
                <h1><?php echo htmlspecialchars($oportunidad['titulo']); ?></h1>
                <p><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($oportunidad['descripcion'])); ?></p>
                <p><strong>Ubicación:</strong> <?php echo nl2br(htmlspecialchars($oportunidad['ubicacion'])); ?></p>
                <p><strong>Ubicación(URL):</strong> <a href="<?php echo htmlspecialchars($oportunidad['url_ubicacion']); ?>" target="_blank">Ver en Google Maps</a></p>
                <p><strong>Categoría:</strong> <?php echo htmlspecialchars($oportunidad['categoria']); ?></p>
                <p><strong>Duración:</strong> <?php echo htmlspecialchars($oportunidad['duracion']); ?> horas</p>
                <p><strong>Tipo de Actividad:</strong> <?php echo htmlspecialchars($oportunidad['tipo_actividad']); ?></p>
                <p><strong>Fecha de Inicio:</strong>
                    <?php
                    if ($oportunidad['fecha_inicio'] instanceof MongoDB\BSON\UTCDateTime) {
                        $fechaColombia = $oportunidad['fecha_inicio']->toDateTime();
                        $fechaColombia->setTimezone(new DateTimeZone('America/Bogota'));
                        echo $fechaColombia->format('d-m-Y h:i A');
                    } else {
                        echo htmlspecialchars($oportunidad['fecha_inicio']);
                    }
                    ?>
                </p>
                <p><strong>Total de postulados:</strong> <?php echo count($postulaciones); ?></p>
            </div>
        </div>

        <h2 class="postulados-title">Personas Postuladas</h2>

        <!-- DEPURACIÓN: muestra las postulaciones en bruto (quitar después) -->
        <!-- <pre><?php print_r($postulaciones); ?></pre> -->

        <!-- Formulario para guardar asistencia -->
        <form action="marcar_asistencia.php" method="POST">
            <input type="hidden" name="id_oportunidad" value="<?php echo $oportunidad['_id']; ?>">

            <table class="postulados-table">
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Fecha de Postulación</th>
                    <th>Asistió</th>
                    <th>Eliminar</th>
                </tr>

                <?php foreach ($postulaciones as $postulacion):
                    if (!isset($postulacion['id_usuario'])) continue;

                    try {
                        $idUsuario = $postulacion['id_usuario'] instanceof MongoDB\BSON\ObjectId
                            ? $postulacion['id_usuario']
                            : new MongoDB\BSON\ObjectId((string)$postulacion['id_usuario']);
                    } catch (Exception $e) {
                        continue;
                    }

                    $usuario = $coleccionUsuarios->findOne(['_id' => $idUsuario]);

                    if (!$usuario) continue;
                ?>
                    <tr>
                        <td>
                            <a href="perfil_voluntario.php?id=<?php echo $idUsuario; ?>">
                                <?php echo htmlspecialchars($postulacion['nombre_usuario'] ?? 'Desconocido'); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($postulacion['correo_usuario'] ?? 'Sin correo'); ?></td>
                        <td>
                            <?php
                            if (isset($postulacion['fecha_postulacion']) && $postulacion['fecha_postulacion'] instanceof MongoDB\BSON\UTCDateTime) {
                                echo $postulacion['fecha_postulacion']->toDateTime()->format('d-m-Y H:i');
                            } else {
                                echo 'Sin fecha';
                            }
                            ?>
                        </td>
                        <td>
                            <input type="checkbox" name="asistencia[<?php echo $postulacion['id_usuario']; ?>]" value="1"
                                <?php echo isset($postulacion['asistio']) && $postulacion['asistio'] ? 'checked' : ''; ?>>
                        </td>
                        <td>
                            <!-- Formulario de eliminación separado -->
                            <button
                                class="btn-delete"
                                onclick="eliminarPostulacion('<?php echo $idUsuario; ?>', '<?php echo $oportunidad['_id']; ?>', this)">
                                Eliminar
                            </button>


                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <button type="submit" class="btn-submit">Guardar Asistencia</button>
        </form>
        <div class="download-buttons">
            <a href="descargar_asistencia_pdf.php?id=<?php echo $oportunidad['_id']; ?>" class="btn-download pdf" target="_blank">
                <img src="../img/pdf.png" alt="PDF"> Descargar PDF
            </a>
            <a href="descargar_asistencia_excel.php?id=<?php echo $oportunidad['_id']; ?>" class="btn-download excel" target="_blank">
                <img src="../img/excel.png" alt="Excel"> Descargar Excel
            </a>
        </div>


    </div>

</body>


<script>
    setTimeout(() => {
        const mensaje = document.querySelector('.mensaje-exito');
        if (mensaje) mensaje.style.display = 'none';
    }, 4000);

    function eliminarPostulacion(idUsuario, idOportunidad, boton) {
        fetch('eliminar_postulacion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id_usuario=${idUsuario}&id_oportunidad=${idOportunidad}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    const fila = boton.closest('tr');
                    fila.classList.add('fade-out');
                    setTimeout(() => fila.remove(), 800);
                } else {
                    console.error("Error:", data.message);
                }
            })
            .catch(err => {
                console.error("Ocurrió un error:", err);
            });
    }
</script>

</html>