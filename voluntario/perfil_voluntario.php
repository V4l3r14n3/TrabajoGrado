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

// Verificar foto de perfil
$fotoPerfil = isset($voluntario['foto_perfil']) && !empty($voluntario['foto_perfil']) ? '../uploads/' . $voluntario['foto_perfil'] : '../img/default_user.png';

// Contar asistencias reales desde 'postulaciones'
$cantidad_asistencias = $database->postulaciones->countDocuments([
    'id_usuario' => new ObjectId($idVoluntario),
    'asistio' => true
]);

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
    <title>Perfil de <?= htmlspecialchars($voluntario['nombre']) ?></title>
    <link rel="stylesheet" href="../css/publ_profile.css">
</head>

<body>

    <?php include 'navbar_voluntario.php'; ?>

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
                $insigniasEspeciales[] = ['img' => '../img/avance5.jpg', 'nombre' => 'Voluntario Colaborativo 🥉'];
            }
            if ($cantidad_asistencias >= 10) {
                $insigniasEspeciales[] = ['img' => '../img/avance10.jpg', 'nombre' => 'Voluntario Avanzado 🥈'];
            }
            if ($cantidad_asistencias >= 20) {
                $insigniasEspeciales[] = ['img' => '../img/avance20.jpg', 'nombre' => 'Voluntario Experto 🥈'];
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
    </div>

</body>

</html>