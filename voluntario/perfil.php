<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo_usuario'] !== 'voluntario') {
    header("Location: login.php");
    exit();
}

$idUsuario = new ObjectId($_SESSION['usuario']['_id']);
$usuario = $database->usuarios->findOne(['_id' => $idUsuario]);

$postulaciones = $database->postulaciones->find(['id_usuario' => $idUsuario])->toArray();
$idsOportunidades = array_map(fn($p) => $p['id_oportunidad'], $postulaciones);

// CONTAR ASISTENCIAS CONFIRMADAS
$asistenciasConfirmadas = array_filter($postulaciones, fn($p) => isset($p['asistio']) && $p['asistio'] === true);
$totalAsistencias = count($asistenciasConfirmadas);

// DETERMINAR IMAGEN SEGÚN ASISTENCIAS
$badgeImg = 'avance0.jpg'; // imagen por defecto
if ($totalAsistencias >= 30) {
    $badgeImg = 'avance30.jpg';
} elseif ($totalAsistencias >= 20) {
    $badgeImg = 'avance20.jpg';
} elseif ($totalAsistencias >= 10) {
    $badgeImg = 'avance10.jpg';
} elseif ($totalAsistencias >= 5) {
    $badgeImg = 'avance5.jpg';
} elseif ($totalAsistencias >= 1) {
    $badgeImg = 'avance1.jpg';
}

$oportunidades = [];
$asistidas = [];
$detallesPostulacion = [];

if (!empty($idsOportunidades)) {
    $cursor = $database->oportunidades->find(['_id' => ['$in' => $idsOportunidades]]);
    foreach ($cursor as $op) {
        $postulacion = array_filter($postulaciones, fn($p) => $p['id_oportunidad'] == $op['_id']);
        $esAsistida = isset(array_values($postulacion)[0]['asistio']) && array_values($postulacion)[0]['asistio'] === true;

        $creadorId = new ObjectId((string)$op['creado_por']);
        $op['nombre_organizacion'] = array_values($postulacion)[0]['nombre_organizacion'] ?? 'Desconocida';

        $detallesPostulacion[(string)$op['_id']] = array_values($postulacion)[0];
        if ($esAsistida) {
            $asistidas[] = $op;
        } else {
            $oportunidades[] = $op;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Perfil del Voluntario</title>
    <link rel="stylesheet" href="../css/perfil_vol.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 10;
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
            position: relative;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
        }

        .view-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            cursor: pointer;
            border-radius: 5px;
        }

        .view-button:hover {
            background-color: #0056b3;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            display: none;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            display: none;
        }
    </style>
</head>

<body>
    <?php include 'navbar_voluntario.php'; ?>

    <div class="container">
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message" style="display:block;">
                <?php
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>


        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message" style="display:block;">
                <?php
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <h2>Perfil de <?php echo htmlspecialchars($usuario['nombre']); ?></h2>

        <!-- Perfil -->
        <div class="profile-top">
            <div class="profile-pic">
                <img src="../uploads/<?php echo $usuario['foto_perfil'] ?? 'default.jpg'; ?>" alt="Foto de perfil" width="150">

                <form action="subir_foto.php" method="POST" enctype="multipart/form-data">
                    <input type="file" name="foto_perfil" accept="image/*" required>
                    <button type="submit">Subir Foto</button>
                </form>
                <div class="badge-section">
                    <h4>Insignia de Asistencia</h4>
                    <img src="../img/<?php echo $badgeImg; ?>" alt="Insignia de asistencia" class="badge-img">
                    <p>Has asistido a <strong><?php echo $totalAsistencias; ?></strong> oportunidad<?php echo $totalAsistencias === 1 ? '' : 'es'; ?>.</p>
                </div>

            </div>



            <div class="profile-info">
                <form action="actualizar_perfil_vol.php" method="POST">
                    <input type="hidden" name="id_usuario" value="<?php echo $usuario['_id']; ?>">
                    <label><i class="fas fa-user"></i> Nombre:</label>
                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>

                    <label><i class="fa fa-envelope"></i> Email:</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled>

                    <label><i class="fas fa-phone"></i> Teléfono:</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">

                    <label><i class="fas fa-city"></i> Ciudad:</label>
                    <select name="ciudad">
                        <option value="">Selecciona una ciudad</option>
                        <?php
                        $ciudades = [
                            "Bogotá",
                            "Medellín",
                            "Cali",
                            "Barranquilla",
                            "Cartagena",
                            "Bucaramanga",
                            "Manizales",
                            "Pereira",
                            "Santa Marta",
                            "Villavicencio"
                        ];
                        foreach ($ciudades as $ciudad) {
                            $selected = ($usuario['ciudad'] ?? '') === $ciudad ? 'selected' : '';
                            echo "<option value=\"$ciudad\" $selected>$ciudad</option>";
                        }
                        ?>
                    </select>


                    <label><i class="fas fa-align-left"></i> Descripción:</label>
                    <textarea name="descripcion" rows="4" placeholder="Cuéntanos más sobre ti..."><?php echo htmlspecialchars($usuario['descripcion'] ?? ''); ?></textarea>

                    <label><i class="fas fa-lock"></i> Nueva Contraseña:</label>
                    <input type="password" name="password" placeholder="Déjala en blanco si no deseas cambiarla.">

                    <!-- Intereses -->
                    <div class="input-container">
                        <i class="fas fa-heart"></i>
                        <label class="checkbox-label">Intereses:</label>
                        <div class="checkbox-options">
                            <?php
                            $interesesUsuario = isset($usuario['intereses']) ? iterator_to_array($usuario['intereses']) : [];
                            $opcionesIntereses = [
                                'educacion' => 'Educación',
                                'medio_ambiente' => 'Medio Ambiente',
                                'animal' => 'Animal',
                                'salud' => 'Salud',
                                'social' => 'Trabajo Social',
                                'tecnologia' => 'Tecnología'
                            ];
                            foreach ($opcionesIntereses as $valor => $etiqueta): ?>
                                <label>
                                    <input type="checkbox" name="intereses[]" value="<?php echo $valor; ?>" <?php echo in_array($valor, $interesesUsuario) ? 'checked' : ''; ?>>
                                    <?php echo $etiqueta; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Habilidades y disponibilidad -->
                    <label><i class="fas fa-tools"></i> Habilidades:</label>
                    <input type="text" name="habilidades" value="<?php echo htmlspecialchars($usuario['habilidades'] ?? ''); ?>">

                    <div class="select-container">
                        <i class="fas fa-calendar-alt"></i>
                        <select name="disponibilidad" required>
                            <option value="" disabled <?php echo ($usuario['disponibilidad'] ?? '') == '' ? 'selected' : ''; ?>>Selecciona tu disponibilidad</option>
                            <?php
                            $opcionesDisponibilidad = ['mañana' => 'Mañana', 'tarde' => 'Tarde', 'noche' => 'Noche'];
                            foreach ($opcionesDisponibilidad as $valor => $etiqueta): ?>
                                <option value="<?php echo $valor; ?>" <?php echo ($valor == ($usuario['disponibilidad'] ?? '')) ? 'selected' : ''; ?>>
                                    <?php echo $etiqueta; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit">Actualizar</button>
                </form>
            </div>
        </div>

        <!-- Oportunidades Postuladas -->
        <h3>Oportunidades Postuladas</h3>
        <div class="success-message" id="cancel-success">Postulación cancelada exitosamente.</div>
        <div class="opportunities">
            <?php if (empty($oportunidades)): ?>
                <p>No te has postulado aún.</p>
            <?php else: ?>
                <?php foreach ($oportunidades as $op): ?>
                    <div class="opportunity">
                        <h4><?php echo htmlspecialchars($op['titulo']); ?></h4>
                        <p><strong>Descripción:</strong> <?php echo htmlspecialchars($op['descripcion']); ?></p>
                        <p><strong>Organización:</strong> <?php echo htmlspecialchars($op['nombre_organizacion']); ?></p>
                        <p><strong>Fecha:</strong> <?php echo $op['fecha_inicio']->toDateTime()->modify('+1 day')->format('d-m-Y'); ?></p>

                        <?php
                        // Copia de la oportunidad con fecha formateada para el JS
                        $opConFecha = $op;
                        $opConFecha['fecha_inicio'] = $op['fecha_inicio']->toDateTime()->format('Y-m-d');
                        $opConFecha['nombre_organizacion'] = $op['nombre_organizacion']; // AÑADIDO
                        ?>
                        <button class="view-button" onclick="verDetalles(<?php echo htmlspecialchars(json_encode($opConFecha), ENT_QUOTES, 'UTF-8'); ?>)">Ver detalles</button>
                        <button class="view-button" style="background-color:#dc3545" onclick="cancelarPostulacion('<?php echo $op['_id']; ?>')">Cancelar postulación</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Oportunidades Asistidas -->
        <h3>Oportunidades Asistidas</h3>
        <div class="opportunities">
            <?php if (empty($asistidas)): ?>
                <p>No has asistido a ninguna oportunidad todavía.</p>
            <?php else: ?>
                <?php foreach ($asistidas as $op): ?>
                    <div class="opportunity">
                        <h4><?php echo htmlspecialchars($op['titulo']); ?></h4>
                        <p><strong>Descripción:</strong> <?php echo htmlspecialchars($op['descripcion']); ?></p>
                        <p><strong>Fecha:</strong> <?php echo $op['fecha_inicio']->toDateTime()->modify('+1 day')->format('d-m-Y'); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>


        <h2>Certificados de Participación</h2>
        <?php if (count($asistidas) > 0): ?>
            <ul class="certificados-lista">
                <?php foreach ($asistidas as $asis): ?>
                    <li class="certificado-item">
                        <strong><?php echo htmlspecialchars($asis['titulo']); ?></strong><br>
                        <span>Fecha:
                            <?php
                            if ($asis['fecha_inicio'] instanceof MongoDB\BSON\UTCDateTime) {
                                echo $asis['fecha_inicio']->toDateTime()->format('d-m-Y');
                            } else {
                                echo htmlspecialchars($asis['fecha_inicio']);
                            }
                            ?>
                        </span><br>
                        <span>Organización: <?php echo htmlspecialchars($asis['nombre_organizacion']); ?></span><br>
                        <a href="generar_certificado.php?id=<?php echo $asis['_id']; ?>" class="btn-ver-certificado" target="_blank">
                            Ver Certificado
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Aún no tienes certificados. Participa en oportunidades y registra tu asistencia para obtenerlos.</p>
        <?php endif; ?>
    </div>




    <!-- Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <div id="modal-body"></div>
        </div>
    </div>

    <script>
        function verDetalles(data) {
            const body = document.getElementById("modal-body");

            // Asegura que la fecha se procese correctamente desde el formato Mongo
            let fechaFormateada = "No disponible";
            if (data.fecha_inicio) {
                const fecha = new Date(data.fecha_inicio);
                fechaFormateada = fecha.toLocaleDateString('es-ES', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }

            body.innerHTML = `
        <h3>${data.titulo}</h3>
        <p><strong>Organización:</strong> ${data.nombre_organizacion}</p>
        <img src="${data.imagen}" alt="Imagen" style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 8px; margin-bottom: 15px;">
        <p><strong>Descripción:</strong> ${data.descripcion}</p>
        <p><strong>Categoría:</strong> ${data.categoria}</p>
        <p><strong>Duración:</strong> ${data.duracion} día(s)</p>
        <p><strong>Tipo de actividad:</strong> ${data.tipo_actividad}</p>
        <p><strong>Fecha de inicio:</strong> ${fechaFormateada}</p>
        <p><strong>Ubicación:</strong> ${data.ubicacion}</p>
        <p><strong>Ubicación URL:</strong> <a href="${data.url_ubicacion}" target="_blank">Ver en Google Maps</a></p>
    `;

            document.getElementById("modal").style.display = "block";
        }


        function cerrarModal() {
            document.getElementById("modal").style.display = "none";
        }

        function cancelarPostulacion(idOportunidad) {
            if (!confirm("¿Estás seguro de que deseas cancelar esta postulación?")) return;
            fetch('cancelar_postulacion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id_oportunidad=${idOportunidad}`
                })
                .then(res => res.text())
                .then(msg => {
                    if (msg === "OK") {
                        document.getElementById("cancel-success").style.display = "block";
                        setTimeout(() => location.reload(), 1500);
                    }
                });
        }
    </script>

</body>

</html>