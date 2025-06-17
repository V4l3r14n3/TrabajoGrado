<?php
session_start();
require '../conexion.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

use MongoDB\BSON\ObjectId;

$idUsuario = new ObjectId($_SESSION['usuario']['_id']);
$coleccionOportunidades = $database->oportunidades;

// Verificar si se recibió un ID válido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Oportunidad no encontrada.";
    header("Location: perfil.php");
    exit();
}

$idOportunidad = new ObjectId($_GET['id']);

// Permitir coincidencia con ObjectId o string en creado_por
$oportunidad = $coleccionOportunidades->findOne([
    '_id' => $idOportunidad,
    '$or' => [
        ['creado_por' => $idUsuario],
        ['creado_por' => (string) $idUsuario]
    ]
]);

if (!$oportunidad) {
    $_SESSION['error_message'] = "No tienes permiso para editar esta oportunidad.";
    header("Location: perfil.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Oportunidad</title>
    <link rel="stylesheet" href="../css/edit_oportunidad.css">
</head>

<body>
    <!-- Barra de navegación -->
    <?php include 'navbar_org.php'; ?>
    <div class="centrar-contenido">
        <div class="container">
            <h2>Editar Oportunidad</h2>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert error"><?php echo $_SESSION['error_message'];
                                            unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <form action="actualizar_oportunidad.php" method="POST">
                <input type="hidden" name="id_oportunidad" value="<?php echo $oportunidad['_id']; ?>">

                <label>Título:</label>
                <input type="text" name="titulo" value="<?php echo htmlspecialchars($oportunidad['titulo']); ?>" required>

                <label>Descripción:</label>
                <textarea name="descripcion" rows="3" required><?php echo htmlspecialchars($oportunidad['descripcion']); ?></textarea>

                <label for="ubicacion">Ciudad:</label>
                <select name="ubicacion" id="ubicacion" required>
                    <?php
                    $ciudades = ["Bogotá", "Medellín", "Cali", "Barranquilla", "Cartagena", "Bucaramanga", "Manizales", "Pereira", "Santa Marta", "Villavicencio"];
                    foreach ($ciudades as $ciudad) {
                        $selected = ($oportunidad['ubicacion'] ?? '') === $ciudad ? 'selected' : '';
                        echo "<option value=\"$ciudad\" $selected>$ciudad</option>";
                    }
                    ?>
                </select>

                <label for="url_ubicacion">Enlace de Google Maps:</label>
                <input type="url" id="url_ubicacion" name="url_ubicacion"
                    placeholder="https://maps.app.goo.gl/..."
                    value="<?php echo htmlspecialchars($oportunidad['url_ubicacion'] ?? ''); ?>" required>


                <label for="categoria">Categoría:</label>
                <select name="categoria" id="categoria" required>
                    <option value="educacion" <?php echo ($oportunidad['categoria'] == 'educacion') ? 'selected' : ''; ?>>Educación</option>
                    <option value="medio_ambiente" <?php echo ($oportunidad['categoria'] == 'medio_ambiente') ? 'selected' : ''; ?>>Medio Ambiente</option>
                    <option value="animal" <?php echo ($oportunidad['categoria'] == 'animal') ? 'selected' : ''; ?>>Animal</option>
                    <option value="salud" <?php echo ($oportunidad['categoria'] == 'salud') ? 'selected' : ''; ?>>Salud</option>
                    <option value="social" <?php echo ($oportunidad['categoria'] == 'social') ? 'selected' : ''; ?>>Trabajo Social</option>
                    <option value="tecnologia" <?php echo ($oportunidad['categoria'] == 'tecnologia') ? 'selected' : ''; ?>>Tecnología</option>
                </select>

                <label>Duración (en horas):</label>
                <input type="number" name="duracion" value="<?php echo htmlspecialchars($oportunidad['duracion']); ?>" required>

                <label for="tipo_actividad">Tipo de Actividad:</label>
                <select name="tipo_actividad" id="tipo_actividad" required>
                    <option value="presencial" <?php echo ($oportunidad['tipo_actividad'] == 'presencial') ? 'selected' : ''; ?>>Presencial</option>
                    <option value="virtual" <?php echo ($oportunidad['tipo_actividad'] == 'virtual') ? 'selected' : ''; ?>>Virtual</option>
                    <option value="hibrido" <?php echo ($oportunidad['tipo_actividad'] == 'hibrido') ? 'selected' : ''; ?>>Híbrido</option>
                </select>

                <label>Fecha de Inicio:</label>
                <input type="datetime-local" name="fecha_inicio" value="<?php
                                                                        if ($oportunidad['fecha_inicio'] instanceof MongoDB\BSON\UTCDateTime) {
                                                                            $fechaColombia = $oportunidad['fecha_inicio']->toDateTime();
                                                                            $fechaColombia->setTimezone(new DateTimeZone('America/Bogota'));
                                                                            echo $fechaColombia->format('Y-m-d\TH:i');
                                                                        } else {
                                                                            echo date('Y-m-d\TH:i', strtotime($oportunidad['fecha_inicio']));
                                                                        }
                                                                        ?>" required>


                <label>Imagen (URL):</label>
                <input type="text" name="imagen" value="<?php echo htmlspecialchars($oportunidad['imagen'] ?? ''); ?>" required>

                <button type="submit">Guardar Cambios</button>
                <a href="perfil.php" class="btn-regresar">Regresar</a>
            </form>
        </div>
    </div>
</body>

</html>