<?php
require '../conexion.php';
use MongoDB\BSON\ObjectId;

if (!isset($_GET['id'])) {
    die("ID no válido.");
}

$idOportunidad = new ObjectId($_GET['id']);
$oportunidad = $database->oportunidades->findOne(['_id' => $idOportunidad]);
$postulaciones = $database->postulaciones->find(['id_oportunidad' => $idOportunidad])->toArray();

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=asistencia_" . preg_replace("/[^a-zA-Z0-9]/", "_", $oportunidad['titulo']) . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo '<table border="1" style="border-collapse: collapse; width: 100%;">';

// Título y datos de la oportunidad
echo "<tr><td colspan='3' style='font-size: 20px; font-weight: bold; background-color: #2c3e50; color: white; text-align: center;'>Lista de Asistencia</td></tr>";
echo "<tr><td colspan='3'><strong>Oportunidad:</strong> " . htmlspecialchars($oportunidad['titulo']) . "</td></tr>";
echo "<tr><td colspan='3'><strong>Ubicación:</strong> " . htmlspecialchars($oportunidad['ubicacion']) . "</td></tr>";
echo "<tr><td colspan='3'><strong>Fecha de Inicio:</strong> " . (
    $oportunidad['fecha_inicio'] instanceof MongoDB\BSON\UTCDateTime
        ? $oportunidad['fecha_inicio']->toDateTime()->format('d-m-Y H:i')
        : htmlspecialchars($oportunidad['fecha_inicio'])
) . "</td></tr>";
echo "<tr><td colspan='3'><strong>Tipo de Actividad:</strong> " . htmlspecialchars($oportunidad['tipo_actividad']) . "</td></tr>";

echo "<tr><td colspan='3'>&nbsp;</td></tr>"; // Espacio

// Cabecera
echo "<tr style='background-color: #f2f2f2; font-weight: bold;'>
        <th style='border: 1px solid #000; padding: 8px;'>Nombre</th>
        <th style='border: 1px solid #000; padding: 8px;'>Email</th>
        <th style='border: 1px solid #000; padding: 8px;'>Asistió</th>
      </tr>";

// Filas de datos
foreach ($postulaciones as $postulacion) {
    $nombre = htmlspecialchars($postulacion['nombre_usuario'] ?? 'Desconocido');
    $email = htmlspecialchars($postulacion['correo_usuario'] ?? '');
    $asistio = isset($postulacion['asistio']) && $postulacion['asistio'] ? 'Sí' : 'No';
    
    echo "<tr>
            <td style='border: 1px solid #000; padding: 6px;'>$nombre</td>
            <td style='border: 1px solid #000; padding: 6px;'>$email</td>
            <td style='border: 1px solid #000; padding: 6px;'>$asistio</td>
          </tr>";
}

echo "<tr><td colspan='3' style='padding-top: 20px; text-align: center; font-style: italic;'>Gracias por ser parte del cambio con Volunteero</td></tr>";
echo "</table>";
