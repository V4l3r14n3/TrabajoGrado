<?php
session_start();

require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

$coleccion = $database->oportunidades;
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = trim($_POST["titulo"] ?? '');
    $descripcion = trim($_POST["descripcion"] ?? '');
    $ubicacion = trim($_POST["ubicacion"] ?? '');
    $url_ubicacion = trim($_POST["url_ubicacion"] ?? '');
    $categoria = trim($_POST["categoria"] ?? '');
    $duracion = isset($_POST["duracion"]) ? intval($_POST["duracion"]) : 0;
    $tipo_actividad = trim($_POST["tipo_actividad"] ?? '');
    $fecha_inicio = $_POST["fecha_inicio"] ?? '';
    $imagen = trim($_POST["imagen"] ?? '');

    // Imagen por defecto si no se ingresa una
    $imagen_por_defecto = "https://static.vecteezy.com/system/resources/previews/004/726/030/original/warning-upload-error-icon-with-cloud-vector.jpg";
    if (!$imagen) {
        $imagen = $imagen_por_defecto;
    }

    // Validaciones
    if (!$titulo || !$descripcion || !$ubicacion || !$url_ubicacion || !$categoria || !$duracion || !$tipo_actividad || !$fecha_inicio) {
        $mensaje = "Todos los campos son obligatorios, excepto la imagen.";
    } elseif (!filter_var($url_ubicacion, FILTER_VALIDATE_URL)) {
        $mensaje = "La ubicación debe ser un enlace válido.";
    } elseif ($imagen && !filter_var($imagen, FILTER_VALIDATE_URL)) {
        $mensaje = "La imagen debe ser un enlace válido.";
    } elseif (!isset($_SESSION["usuario"]["_id"])) {
        $mensaje = "No se encontró la organización. Asegúrate de haber iniciado sesión.";
    } else {
        // CORRECCIÓN DE FECHA: interpretar como hora local Bogotá y convertir a UTC
        try {
            $fechaLocal = new DateTimeImmutable($fecha_inicio, new DateTimeZone('America/Bogota'));
            $fechaUTC = $fechaLocal->setTimezone(new DateTimeZone('UTC'));
            $fechaInicioUTC = new UTCDateTime($fechaUTC->getTimestamp() * 1000);
        } catch (Exception $e) {
            $mensaje = "Error al procesar la fecha: " . $e->getMessage();
        }

        // Si no hubo errores en la fecha
        if (empty($mensaje)) {
            $nuevaOportunidad = [
                "titulo" => $titulo,
                "descripcion" => $descripcion,
                "ubicacion" => $ubicacion,
                "url_ubicacion" => $url_ubicacion,
                "categoria" => $categoria,
                "duracion" => $duracion,
                "tipo_actividad" => $tipo_actividad,
                "fecha_inicio" => $fechaInicioUTC,
                "imagen" => $imagen,
                "creado_por" => new ObjectId($_SESSION["usuario"]["_id"]),
                "nombre_organizacion" => $_SESSION["usuario"]["organizacion"],
                "fecha_creacion" => new UTCDateTime()
            ];

            try {
                $resultado = $coleccion->insertOne($nuevaOportunidad);
                if ($resultado->getInsertedCount() > 0) {
                    $mensaje = "✅ Oportunidad creada con éxito.";
                } else {
                    $mensaje = "❌ Hubo un problema al guardar la oportunidad.";
                }
            } catch (Exception $e) {
                $mensaje = "❌ Error al insertar en la base de datos: " . $e->getMessage();
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Publicar Oportunidad</title>
    <link rel="stylesheet" href="../css/oportunidades.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>

    <?php include 'navbar_org.php'; ?>

    <div class="container mt-5">
        <h2>Registrar Nueva Oportunidad</h2>

        <?php if (!empty($mensaje)): ?>
            <div class="alert <?php echo (strpos($mensaje, '✅') !== false) ? 'alert-success' : 'alert-danger'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form action="publicar.php" method="POST">
            <div class="mb-3">
                <label for="titulo" class="form-label">Título de la Oportunidad</label>
                <input type="text" class="form-control" id="titulo" name="titulo" required>
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required></textarea>
            </div>

            <div class="mb-3">
                <label for="ubicacion">Ciudad</label>
                <select name="ubicacion" id="ubicacion" required>
                    <option value="">Selecciona una ciudad</option>
                    <option value="Bogotá">Bogotá</option>
                    <option value="Medellín">Medellín</option>
                    <option value="Cali">Cali</option>
                    <option value="Barranquilla">Barranquilla</option>
                    <option value="Cartagena">Cartagena</option>
                    <option value="Bucaramanga">Bucaramanga</option>
                    <option value="Manizales">Manizales</option>
                    <option value="Pereira">Pereira</option>
                    <option value="Santa Marta">Santa Marta</option>
                    <option value="Villavicencio">Villavicencio</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="url_ubicacion" class="form-label">URL Ubicación</label>
                <input type="url" class="form-control" id="url_ubicacion" name="url_ubicacion"
                    pattern="https?://.+" placeholder="Ej: https://maps.google.com/..." required>
            </div>

            <div class="mb-3">
                <label for="fecha_inicio" class="form-label">Fecha y Hora de Inicio</label>
                <input type="datetime-local" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                <small class="text-muted">Debe ser una fecha y hora futura.</small>

            </div>

            <div class="mb-3">
                <label for="categoria" class="form-label">Categoría</label>
                <select class="form-select" id="categoria" name="categoria" required>
                    <option value="educacion">Educación</option>
                    <option value="medio_ambiente">Medio Ambiente</option>
                    <option value="animal">Animal</option>
                    <option value="salud">Salud</option>
                    <option value="social">Trabajo Social</option>
                    <option value="tecnologia">Tecnología</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="duracion" class="form-label">Duración (en horas)</label>
                <input type="number" class="form-control" id="duracion" name="duracion" required>
            </div>

            <div class="mb-3">
                <label for="tipo_actividad" class="form-label">Tipo de Actividad</label>
                <select class="form-select" id="tipo_actividad" name="tipo_actividad" required>
                    <option value="presencial">Presencial</option>
                    <option value="virtual">Virtual</option>
                    <option value="hibrido">Híbrido</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="imagen" class="form-label">Enlace de la Imagen (opcional)</label>
                <input type="url" class="form-control" id="imagen" name="imagen"
                    pattern="https?://.+" placeholder="Ej: https://i.imgur.com/ejemplo.jpg">
            </div>

            <button type="submit" class="btn btn-primary">Crear Oportunidad</button>
        </form>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const fechaInput = document.getElementById("fecha_inicio");

            // Establecer la fecha mínima como ahora (en zona horaria local del navegador)
            const ahora = new Date();
            ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset()); // Ajuste a local ISO
            const ahoraISO = ahora.toISOString().slice(0, 16); // Formato yyyy-MM-ddThh:mm
            fechaInput.min = ahoraISO;

            // Validación al enviar el formulario
            const formulario = document.querySelector("form");
            formulario.addEventListener("submit", function(e) {
                const seleccionada = new Date(fechaInput.value);
                const ahoraCheck = new Date();

                if (seleccionada < ahoraCheck) {
                    e.preventDefault();
                    alert("La fecha y hora de inicio no pueden estar en el pasado.");
                    fechaInput.focus();
                }
            });
        });
    </script>

</body>

</html>