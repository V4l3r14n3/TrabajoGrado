<?php
require '../vendor/autoload.php';
require '../conexion.php';
use Dompdf\Dompdf;
use MongoDB\BSON\ObjectId;

if (!isset($_GET['id'])) {
    die("ID no válido.");
}

$idOportunidad = new ObjectId($_GET['id']);
$oportunidad = $database->oportunidades->findOne(['_id' => $idOportunidad]);
$postulaciones = $database->postulaciones->find(['id_oportunidad' => $idOportunidad])->toArray();

$logoPath = '../img/logo.png';
$logoBase64 = @file_get_contents($logoPath);
if ($logoBase64 === false) {
    die("No se pudo cargar el logo.");
}
$logo = 'data:image/png;base64,' . base64_encode($logoBase64);

$html = '
<style>
    body { font-family: Arial, sans-serif; }
    .header { text-align: center; margin-bottom: 20px; }
    .header img { width: 120px; }
    h1 { text-align: center; color: #2c3e50; }
    .info { margin-bottom: 20px; }
    .info p { margin: 4px 0; }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    th, td {
        border: 1px solid #aaa;
        padding: 8px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
</style>

<div class="header">
    <img src="' . $logo . '" alt="Logo Volunteero">
</div>

<h1>Lista de Asistencia</h1>

<div class="info">
    <p><strong>Oportunidad:</strong> ' . htmlspecialchars($oportunidad['titulo']) . '</p>
    <p><strong>Organización Encargada:</strong> ' . htmlspecialchars($oportunidad['nombre_organizacion']) . '</p>
    <p><strong>Ciudad:</strong> ' . htmlspecialchars($oportunidad['ubicacion']) . '</p>
    <p><strong>Fecha de Inicio:</strong> ' . (
        $oportunidad['fecha_inicio'] instanceof MongoDB\BSON\UTCDateTime
            ? $oportunidad['fecha_inicio']->toDateTime()->format('d-m-Y H:i')
            : htmlspecialchars($oportunidad['fecha_inicio'])
    ) . '</p>
    <p><strong>Tipo de Actividad:</strong> ' . htmlspecialchars($oportunidad['tipo_actividad']) . '</p>
</div>

<table>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Asistió</th>
        </tr>
    </thead>
    <tbody>';

foreach ($postulaciones as $postulacion) {
    $nombre = htmlspecialchars($postulacion['nombre_usuario'] ?? 'Desconocido');
    $email = htmlspecialchars($postulacion['correo_usuario'] ?? '');
    $asistio = isset($postulacion['asistio']) && $postulacion['asistio'] ? 'Sí' : 'No';
    $html .= "<tr><td>$nombre</td><td>$email</td><td>$asistio</td></tr>";
}

$html .= '
    </tbody>
</table>

<div style="margin-top: 40px; text-align: center; font-size: 14px; color: #555;">
    <p>Gracias por ser parte del cambio con <strong>Volunteero</strong>.</p>
    <p>' . (!empty($oportunidad['organizacion']) ? 'Organizado por: <strong>' . htmlspecialchars($oportunidad['organizacion']) . '</strong>' : '') . '</p>
</div>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
ob_end_clean(); // Limpiar salida previa si hay
$dompdf->stream("asistencia_" . preg_replace("/[^a-zA-Z0-9]/", "_", $oportunidad['titulo']) . ".pdf", ["Attachment" => false]);
