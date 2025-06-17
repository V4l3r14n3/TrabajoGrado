<?php
session_start();
require '../conexion.php';
require '../vendor/autoload.php';

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

// Obtener opciones únicas desde MongoDB
$categorias = $database->oportunidades->distinct("categoria");
$ubicaciones = $database->oportunidades->distinct("ubicacion");
$duraciones = $database->oportunidades->distinct("duracion");

// Filtros desde GET
$categoriaSeleccionada = $_GET['categoria'] ?? null;
$ubicacionSeleccionada = $_GET['ubicacion'] ?? null;
$duracionSeleccionada = $_GET['duracion'] ?? null;
$verTodo = isset($_GET['ver_todo']);

// Filtro dinámico
$filtroBase = [];

if ($categoriaSeleccionada) {
    $filtroBase['categoria'] = $categoriaSeleccionada;
}
if ($ubicacionSeleccionada) {
    $filtroBase['ubicacion'] = $ubicacionSeleccionada;
}
if ($duracionSeleccionada) {
    $filtroBase['duracion'] = (int)$duracionSeleccionada;
}
if (!$verTodo) {
    $manana = new DateTime('tomorrow', new DateTimeZone('UTC'));
    $manana->setTime(0, 0);
    $utcManana = new UTCDateTime($manana->getTimestamp() * 1000);
    $filtroBase['fecha_inicio'] = ['$gte' => $utcManana];
}

$oportunidades = $database->oportunidades->find($filtroBase, ['sort' => ['fecha_inicio' => 1]]);

// Postulaciones del usuario
$usuarioActual = $_SESSION['usuario']['_id'] ?? null;
$postulacionesUsuario = [];

if ($usuarioActual) {
    $postulaciones = $database->postulaciones->find([
        'id_usuario' => new ObjectId($usuarioActual)
    ]);
    foreach ($postulaciones as $postulacion) {
        $postulacionesUsuario[] = (string)$postulacion['id_oportunidad'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Oportunidades</title>
    <link rel="stylesheet" href="../css/opvoluntarios.css">
</head>

<body>
    <?php include 'navbar_voluntario.php'; ?>

    <header>
        <h1>Listado de Oportunidades</h1>
    </header>

    <!-- Filtros -->
    <section class="filtros">
        <form method="GET">
            <select name="categoria">
                <option value="">-- Filtrar por Categoría --</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $cat === $categoriaSeleccionada ? 'selected' : '' ?>>
                        <?= ucwords(str_replace('_', ' ', $cat)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="ubicacion">
                <option value="">-- Filtrar por Ubicación --</option>
                <?php foreach ($ubicaciones as $ubi): ?>
                    <option value="<?= htmlspecialchars($ubi) ?>" <?= $ubi === $ubicacionSeleccionada ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ubi) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="duracion">
                <option value="">-- Filtrar por Duración (horas) --</option>
                <?php foreach ($duraciones as $dur): ?>
                    <option value="<?= htmlspecialchars($dur) ?>" <?= $dur == $duracionSeleccionada ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dur) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="ver_todo" value="1">Mostrar todo</button>
            <button type="submit">Aplicar Filtros</button>
        </form>
    </section>

    <!-- Resultados -->
    <section class="grid">
        <?php
        $oportunidadesArray = iterator_to_array($oportunidades);
        if (empty($oportunidadesArray)): ?>
            <div class="mensaje-vacio">
                <img src="../img/empty-box.png" alt="Sin oportunidades">
                <p>No hay oportunidades disponibles por ahora.</p>
            </div>
            <?php else:
            foreach ($oportunidadesArray as $oportunidad): ?>
                <div class="card">
                    <h4><?= htmlspecialchars($oportunidad['titulo']) ?></h4>
                    <p><strong>Descripción:</strong> <?= htmlspecialchars($oportunidad['descripcion']) ?></p>
                    <p><strong>Organización:</strong> <?= htmlspecialchars($oportunidad['nombre_organizacion'] ?? 'Desconocido') ?></p>
                    <p><strong>Ubicación:</strong> <?= htmlspecialchars($oportunidad['ubicacion']) ?></p>
                    <p><strong>Ubicación(URL):</strong> <a href="<?= htmlspecialchars($oportunidad['url_ubicacion']) ?>" target="_blank">Ver en Google Maps</a></p>
                    <p><strong>Categoría:</strong> <?= htmlspecialchars($oportunidad['categoria']) ?></p>
                    <p><strong>Duración:</strong> <?= htmlspecialchars($oportunidad['duracion']) ?> horas</p>
                    <p><strong>Tipo de Actividad:</strong> <?= htmlspecialchars($oportunidad['tipo_actividad']) ?></p>
                    <p><strong>Fecha de Inicio:</strong>
                        <?= ($oportunidad['fecha_inicio'] instanceof UTCDateTime)
                            ? $oportunidad['fecha_inicio']->toDateTime()->format('d-m-Y')
                            : date('d-m-Y', strtotime($oportunidad['fecha_inicio'])) ?>
                    </p>
                    <button class="btn" onclick='mostrarModal(<?= json_encode([
                                                                    "id" => (string)$oportunidad['_id'],
                                                                    "titulo" => $oportunidad["titulo"],
                                                                    "descripcion" => $oportunidad["descripcion"],
                                                                    "duracion" => $oportunidad["duracion"],
                                                                    "tipo_actividad" => $oportunidad["tipo_actividad"],
                                                                    "fecha_inicio" => ($oportunidad["fecha_inicio"] instanceof UTCDateTime)
                                                                        ? $oportunidad["fecha_inicio"]->toDateTime()->format(DateTime::ATOM)
                                                                        : date(DATE_ATOM, strtotime($oportunidad["fecha_inicio"])),
                                                                    "ubicacion" => $oportunidad["ubicacion"],
                                                                    "ubicacion(URL)" => $oportunidad["url_ubicacion"],
                                                                    "imagen" => $oportunidad["imagen"] ?? "",
                                                                    "organizacion" => $oportunidad["nombre_organizacion"] ?? "Desconocida"
                                                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>Ver detalles</button>
                </div>
        <?php endforeach;
        endif; ?>
    </section>

    <!-- Modal -->
    <div class="modal" id="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="cerrarModal()">&times;</span>
            <h3 id="modalTitulo"></h3>
            <p><strong>Organización:</strong> <span id="modalOrganizacion"></span></p>
            <img id="modalImagen" src="" style="width:100%; max-height:200px; object-fit:cover;">
            <p><strong>Descripción:</strong> <span id="modalDescripcion"></span></p>
            <p><strong>Duración:</strong> <span id="modalDuracion"></span> horas</p>
            <p><strong>Tipo de actividad:</strong> <span id="modalTipo"></span></p>
            <p><strong>Fecha:</strong> <span id="modalFecha"></span></p>
            <p><strong>Ubicación:</strong> <span id="modalUbicacion"></span></p>
            <p><strong>Ubicación(URL):</strong> <a id="modalUbicacionURL" href="#" target="_blank"></a></p>
            <button id="modalPostular" class="btn" onclick="postularse()">Postularse</button>
            <div id="mensajePostulacion" style="margin-top:10px;"></div>
        </div>
    </div>

    <script>
        const oportunidadesPostuladas = <?= json_encode($postulacionesUsuario) ?>;

        function mostrarModal(data) {
            document.getElementById('modalTitulo').innerText = data.titulo;
            document.getElementById('modalDescripcion').innerText = data.descripcion;
            document.getElementById('modalDuracion').innerText = data.duracion;
            document.getElementById('modalTipo').innerText = data.tipo_actividad;
            document.getElementById('modalOrganizacion').innerText = data.organizacion;
            document.getElementById('modalFecha').innerText = new Date(data.fecha_inicio).toLocaleDateString();
            document.getElementById('modalImagen').src = data.imagen || '../img/placeholder.png';
            document.getElementById('modalUbicacion').innerText = data.ubicacion;
            document.getElementById('modalUbicacionURL').href = data.url_ubicacion;
            document.getElementById('modalUbicacionURL').innerText = "Ver en Google Maps";

            const postularBtn = document.getElementById('modalPostular');
            const mensajePostulacion = document.getElementById('mensajePostulacion');

            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            const fechaInicio = new Date(data.fecha_inicio);
            fechaInicio.setHours(0, 0, 0, 0);

            const yaPostulado = oportunidadesPostuladas.includes(String(data.id));

            if (fechaInicio <= hoy || yaPostulado) {
                postularBtn.disabled = true;
                postularBtn.innerText = yaPostulado ? "Ya Postulado" : "No disponible";
                mensajePostulacion.innerText = yaPostulado ?
                    "Ya estás postulado a esta oportunidad." :
                    "Esta oportunidad ya no está disponible para postularse.";
                mensajePostulacion.style.color = "red";
            } else {
                postularBtn.disabled = false;
                postularBtn.innerText = "Postularse";
                mensajePostulacion.innerText = "";
                mensajePostulacion.style.color = "";
            }

            postularBtn.dataset.id = data.id;
            document.getElementById('modal').style.display = 'flex';
        }

        function cerrarModal() {
            document.getElementById('modal').style.display = 'none';
        }

        function postularse() {
            const postularBtn = document.getElementById('modalPostular');
            const idOportunidad = postularBtn.dataset.id;

            fetch('postular.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id_oportunidad=' + encodeURIComponent(idOportunidad)
                })
                .then(res => res.json())
                .then(data => {
                    const mensaje = document.getElementById('mensajePostulacion');
                    mensaje.innerText = data.message;
                    mensaje.style.color = data.success ? 'green' : 'red';

                    if (data.success) {
                        postularBtn.disabled = true;
                        postularBtn.innerText = "Ya Postulado";

                        // ✅ Actualiza el arreglo en memoria
                        if (!oportunidadesPostuladas.includes(idOportunidad)) {
                            oportunidadesPostuladas.push(idOportunidad);
                        }
                    }
                });
        }

        window.onclick = function(e) {
            if (e.target.id === "modal") cerrarModal();
        }
    </script>
</body>

</html>