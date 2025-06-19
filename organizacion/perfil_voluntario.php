<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

// Validar ID en la URL
if (!isset($_GET['id'])) {
    echo "ID del voluntario no proporcionado.";
    exit();
}

$idVoluntario = $_GET['id'];

try {
    $voluntario = $database->usuarios->findOne(['_id' => new ObjectId($idVoluntario)]);
} catch (Exception $e) {
    echo "ID inválido.";
    exit();
}

if (!$voluntario || $voluntario['tipo_usuario'] !== 'voluntario') {
    echo "Voluntario no encontrado.";
    exit();
}

// Foto de perfil con soporte para Cloudinary o local
if (!empty($voluntario['foto_perfil'])) {
    if (filter_var($voluntario['foto_perfil'], FILTER_VALIDATE_URL)) {
        $fotoPerfil = $voluntario['foto_perfil']; // URL de Cloudinary
    } elseif (file_exists(__DIR__ . '/../uploads/' . $voluntario['foto_perfil'])) {
        $fotoPerfil = '../uploads/' . htmlspecialchars($voluntario['foto_perfil']); // Imagen local
    } else {
        $fotoPerfil = '../img/default_user.png'; // Imagen no encontrada
    }
} else {
    $fotoPerfil = '../img/default_user.png'; // No tiene foto
}

// Contar asistencias reales desde 'postulaciones'
$cantidad_asistencias = $database->postulaciones->countDocuments([
    'id_usuario' => new ObjectId($idVoluntario),
    'asistio' => true
]);

// Obtener certificados basados en asistencias confirmadas
$postulacionesAsistidas = $database->postulaciones->find([
    'id_usuario' => new ObjectId($idVoluntario),
    'asistio' => true
]);

$asistidas = [];

foreach ($postulacionesAsistidas as $postulacion) {
    $idOportunidad = $postulacion['id_oportunidad'] ?? null;

    if ($idOportunidad) {
        $oportunidad = $database->oportunidades->findOne(['_id' => new ObjectId($idOportunidad)]);

        if ($oportunidad) {
            $organizacion = $database->usuarios->findOne(['_id' => new ObjectId($oportunidad['creado_por'])]);

            $asistidas[] = [
                '_id' => (string)$oportunidad['_id'],
                'titulo' => $oportunidad['titulo'] ?? 'Oportunidad sin título',
                'fecha_inicio' => $oportunidad['fecha_inicio'] ?? '',
                'nombre_organizacion' => $oportunidad['nombre_organizacion'] ?? 'Organización desconocida'
            ];
        }
    }
}


// Datos personales
$intereses = $voluntario['intereses'] ?? [];
$habilidades = $voluntario['habilidades'] ?? '';
$insignias = $voluntario['insignias'] ?? [];
$certificados = $voluntario['certificados'] ?? [];

$opcionesIntereses = [
    'educacion' => 'Educación',
    'medio_ambiente' => 'Medio Ambiente',
    'animal' => 'Animal',
    'salud' => 'Salud',
    'social' => 'Trabajo Social',
    'tecnologia' => 'Tecnología'
];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?= htmlspecialchars($voluntario['nombre']) ?></title>
    <link rel="stylesheet" href="../css/publ_profile.css">
</head>

<body>

    <?php include 'navbar_org.php'; ?>

    <div class="container perfil-container">
        <div class="perfil-header">
            <img src="<?= $fotoPerfil ?>" alt="Foto de perfil">
            <div>
                <h2><?= htmlspecialchars($voluntario['nombre']) ?></h2>
                <p><strong>Correo:</strong> <?= htmlspecialchars($voluntario['email']) ?></p>
                <p><strong>Celular:</strong> <?= htmlspecialchars($voluntario['telefono']) ?></p>
                <p><strong>Ciudad:</strong> <?= htmlspecialchars($voluntario['ciudad']) ?></p>
                <p><strong>Habilidades:</strong> <?= htmlspecialchars($habilidades) ?></p>

                <?php if (!empty($intereses)): ?>
                    <p><strong>Intereses:</strong></p>
                    <ul>
                        <?php foreach ($intereses as $int): ?>
                            <li><?= htmlspecialchars($opcionesIntereses[$int] ?? $int) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><strong>Intereses:</strong> No registrados.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="seccion">
            <h3>Sobre mí</h3>
            <p><?= nl2br(htmlspecialchars($voluntario['descripcion'] ?? '')) ?></p>
        </div>

        <div class="seccion">
            <h3>Insignias</h3>

            <?php
            $insigniasEspeciales = [];

            if ($cantidad_asistencias >= 1) {
                $insigniasEspeciales[] = ['img' => '../img/avance1.jpg', 'nombre' => 'Voluntario Activo 🥉'];
            }
            if ($cantidad_asistencias >= 5) {
                $insigniasEspeciales[] = ['img' => '../img/avance5.jpg', 'nombre' => 'Voluntario Activo 🥉'];
            }
            if ($cantidad_asistencias >= 10) {
                $insigniasEspeciales[] = ['img' => '../img/avance10.jpg', 'nombre' => 'Voluntario Avanzado 🥈'];
            }
            if ($cantidad_asistencias >= 20) {
                $insigniasEspeciales[] = ['img' => '../img/avance20.jpg', 'nombre' => 'Voluntario Avanzado 🥈'];
            }
            if ($cantidad_asistencias >= 30) {
                $insigniasEspeciales[] = ['img' => '../img/avance30.jpg', 'nombre' => 'Voluntario Estrella 🥇'];
            }
            if ($cantidad_asistencias == 0) {
                $insigniasEspeciales[] = ['img' => '../img/avance0.jpg', 'nombre' => 'Voluntario Inactivo'];
            }
            ?>

            <div class="insignias-container">
                <?php
                $ultimaInsigniaIndex = count($insigniasEspeciales) - 1;
                foreach ($insigniasEspeciales as $index => $insignia):
                    $destacada = $index === $ultimaInsigniaIndex ? 'insignia-destacada' : '';
                ?>
                    <div class="insignia <?= $destacada ?> insignia-tooltip">
                        <img src="<?= $insignia['img'] ?>" alt="<?= $insignia['nombre'] ?>">
                        <p><?= $insignia['nombre'] ?></p>
                        <div class="tooltip-text">Obtenida por <?= strtolower($insignia['nombre']) ?></div>
                    </div>
                <?php endforeach; ?>

                <?php if (count($insignias) > 0): ?>
                    <?php foreach ($insignias as $ins): ?>
                        <?php
                        $rutaInsignia = strpos($ins, 'http') === 0 ? $ins : '../' . $ins;
                        $nombre = basename(parse_url($ins, PHP_URL_PATH));
                        ?>
                        <div class="insignia insignia-tooltip">
                            <img src="<?= htmlspecialchars($rutaInsignia) ?>" alt="Insignia">
                            <p><?= htmlspecialchars($nombre) ?></p>
                            <div class="tooltip-text">Insignia especial</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="seccion">
            <h3>Certificados de Participación</h3>

            <?php if (count($asistidas) > 0): ?>
                <div class="certificados-container">
                    <?php foreach ($asistidas as $asis): ?>
                        <div class="certificado">
                            <img src="../img/icono_pdf.png" alt="PDF" class="icono-pdf">
                            <strong><?= htmlspecialchars($asis['titulo']) ?></strong><br>
                            <span>
                                Fecha:
                                <?php
                                if ($asis['fecha_inicio'] instanceof MongoDB\BSON\UTCDateTime) {
                                    echo $asis['fecha_inicio']->toDateTime()->format('d-m-Y');
                                } else {
                                    echo htmlspecialchars($asis['fecha_inicio']);
                                }
                                ?>
                            </span><br>
                            <span>Organización: <?= htmlspecialchars($asis['nombre_organizacion']) ?></span><br>
                            <a href="../voluntario/generar_certificado.php?id=<?= $asis['_id'] ?>&voluntario=<?= htmlspecialchars($idVoluntario) ?>" class="btn-ver-certificado" target="_blank">
                                Ver Certificado
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No hay certificados disponibles.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>