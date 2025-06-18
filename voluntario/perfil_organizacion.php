<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;

if (!isset($_GET['id'])) {
  echo "Organización no especificada.";
  exit();
}

$idOrg = $_GET['id'];
$org = $database->usuarios->findOne([
  '_id' => new ObjectId($idOrg),
  'tipo_usuario' => 'organizacion'
]);

if (!$org) {
  echo "Organización no encontrada.";
  exit();
}

// Buscar oportunidades creadas por esta organización
$oportunidades = $database->oportunidades->find([
  'creado_por' => new ObjectId($idOrg)
]);

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Perfil de la Organización</title>
  <link rel="stylesheet" href="../css/publ_profile.css">
</head>

<body>

  <?php include 'navbar_voluntario.php'; ?>

  <div class="perfil-container">

    <div class="perfil-header">
      <?php
      $fotoPerfil = !empty($org['foto_perfil']) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/uploads/" . $org['foto_perfil'])
        ? "/uploads/" . htmlspecialchars($org['foto_perfil'])
        : "/img/perfil_default.png";

      ?>
      <img src="<?= $fotoPerfil ?>" alt="Logo de la organización">
      <div>
        <h2><?= htmlspecialchars($org['nombre']) ?></h2>
        <p><strong>Organización:</strong> <?= htmlspecialchars($org['organizacion']) ?></p>
        <p><strong>Correo:</strong> <?= htmlspecialchars($org['email']) ?></p>
        <p><strong>Teléfono:</strong> <?= htmlspecialchars($org['telefono'] ?? 'No disponible') ?></p>
        <p><strong>Ciudad:</strong> <?= htmlspecialchars($org['ciudad'] ?? 'No disponible') ?></p>
      </div>
    </div>

    <div class="seccion">
      <h3>Sobre la organización</h3>
      <p><?= nl2br(htmlspecialchars($org['descripcion'] ?? 'No se ha agregado una descripción.')) ?></p>
    </div>

    <div class="seccion">
      <h3>Oportunidades publicadas</h3>

      <div class="grid-oportunidades">
        <?php foreach ($oportunidades as $oportunidad): ?>
          <div class="card-oportunidad">
            <img src="<?= htmlspecialchars($oportunidad['imagen']) ?>" alt="Imagen de la oportunidad">
            <div class="card-contenido">
              <h4><?= htmlspecialchars($oportunidad['titulo']) ?></h4>
              <p><strong>Ubicación:</strong> <?= htmlspecialchars($oportunidad['ubicacion']) ?></p>
              <p><strong>Inicio:</strong> <?= date('d/m/Y', strtotime($oportunidad['fecha_inicio']->toDateTime()->format('Y-m-d'))) ?></p>
              <p><strong>Duración:</strong> <?= htmlspecialchars($oportunidad['duracion']) ?> horas</p>
              <p><strong>Descripción:</strong> <?= htmlspecialchars($oportunidad['descripcion']) ?></p>
              <a href="<?= htmlspecialchars($oportunidad['url_ubicacion']) ?>" target="_blank">Ver ubicación</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</body>

</html>