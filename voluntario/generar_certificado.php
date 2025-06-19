<?php
require '../vendor/autoload.php';
require '../conexion.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use MongoDB\BSON\ObjectId;

// Validación de parámetros
if (!isset($_GET['id']) || !isset($_GET['voluntario'])) {
    die("ID de oportunidad o voluntario no válidos.");
}

try {
    $idOportunidad = new ObjectId($_GET['id']);
    $idVoluntario = new ObjectId($_GET['voluntario']);
} catch (Exception $e) {
    die("IDs inválidos.");
}

// Buscar la postulación correspondiente (acepta booleano o string)
$postulacion = $database->postulaciones->findOne([
    'id_oportunidad' => $idOportunidad,
    'id_usuario' => $idVoluntario,
    '$or' => [
        ['asistio' => true],
        ['asistio' => 'true']
    ]
]);

if (!$postulacion) {
    die("No se encontró una postulación válida para generar el certificado.");
}

$nombreVoluntario = $postulacion['nombre_usuario'];
$correoVoluntario = $postulacion['correo_usuario'] ?? '';
$nombreOrg = $postulacion['nombre_organizacion'] ?? 'Organización';
$titulo = $postulacion['nombre_oportunidad'] ?? 'Oportunidad';

// Buscar la oportunidad para extraer fecha y duración
$oportunidad = $database->oportunidades->findOne([
    '_id' => $idOportunidad
]);

$duracion = $oportunidad['duracion'] ?? 'Duración no disponible';
$fecha = ($oportunidad['fecha_inicio'] instanceof MongoDB\BSON\UTCDateTime)
    ? $oportunidad['fecha_inicio']->toDateTime()->format('d-m-Y')
    : 'Fecha no disponible';

// Cargar logo en base64
$logoBase64 = base64_encode(file_get_contents(__DIR__ . '/../img/logo.png'));
$logoSrc = 'data:image/png;base64,' . $logoBase64;

// HTML del certificado
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: "Georgia", serif;
            background: #fff;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .certificado {
            border: 10px solid #2c3e50;
            padding: 50px 40px;
            margin: 30px;
        }
        .logo-container {
            margin-bottom: 20px;
        }
        .logo-container img {
            width: 100px;
        }
        .certificado h1 {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .certificado h2 {
            font-size: 22px;
            margin: 10px 0;
            color: #34495e;
        }
        .certificado p {
            font-size: 18px;
            line-height: 1.6;
            margin: 20px 40px;
            color: #2c3e50;
        }
        .certificado .firma {
            margin-top: 50px;
            font-size: 16px;
            color: #7f8c8d;
        }
        .certificado .footer {
            margin-top: 30px;
            font-size: 14px;
            color: #95a5a6;
        }
        .certificado .linea {
            border-top: 2px solid #2c3e50;
            width: 200px;
            margin: 30px auto 5px;
        }
    </style>
</head>
<body>
    <div class="certificado">
        <div class="logo-container">
            <img src="' . $logoSrc . '" alt="Logo">
        </div>

        <h1>Certificado de Participación</h1>

        <p>Se certifica que</p>
        <h2>' . htmlspecialchars($nombreVoluntario) . '</h2>
        <p>ha participado activamente en la oportunidad de voluntariado titulada</p>
        <h2>"' . htmlspecialchars($titulo) . '"</h2>
        <p>organizada por <strong>' . htmlspecialchars($nombreOrg) . '</strong> el día <strong>' . $fecha . '</strong>.</p>
        <p>Duración de la participación: <strong>' . htmlspecialchars($duracion) . ' horas</strong>.</p>
        <p>Este certificado se emite como reconocimiento a su valiosa contribución y compromiso con la causa.</p>

        <p>Con agradecimiento por su compromiso y dedicación al servicio de la comunidad.</p>

        <div class="firma">
            <div class="linea"></div>
            Firma de la organización
        </div>

        <div class="footer">
            Este certificado ha sido generado automáticamente desde la plataforma de voluntariado Volunteroo.
        </div>
    </div>
</body>
</html>
';

// Renderizar PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("certificado_$nombreVoluntario.pdf", ["Attachment" => false]);
exit;
