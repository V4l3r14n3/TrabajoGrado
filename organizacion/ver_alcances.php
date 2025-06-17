<?php
session_start();
require '../conexion.php';
require_once '../vendor/autoload.php';

$organizacionId = new MongoDB\BSON\ObjectId($_SESSION['usuario']['_id']);

$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
$fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;

$matchStage = [
    '$match' => [
        'fecha_postulacion' => [
            '$exists' => true
        ]
    ]
];

// Agregar filtro de fechas si está definido
if ($fechaInicio && $fechaFin) {
    $matchStage['$match']['fecha_postulacion']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($fechaInicio . " 00:00:00") * 1000);
    $matchStage['$match']['fecha_postulacion']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($fechaFin . " 23:59:59") * 1000);
}

$pipeline = [
    // Hacer lookup primero
    [
        '$lookup' => [
            'from' => 'oportunidades',
            'localField' => 'id_oportunidad',
            'foreignField' => '_id',
            'as' => 'oportunidad'
        ]
    ],
    ['$unwind' => '$oportunidad'],

    // Filtrar por organización
    [
        '$match' => [
            'oportunidad.creado_por' => $organizacionId
        ]
    ]
];

// Luego, si hay fechas, aplicar el filtro
if ($fechaInicio && $fechaFin) {
    $pipeline[] = [
        '$match' => [
            'fecha_postulacion' => [
                '$gte' => new MongoDB\BSON\UTCDateTime(strtotime($fechaInicio . " 00:00:00") * 1000),
                '$lte' => new MongoDB\BSON\UTCDateTime(strtotime($fechaFin . " 23:59:59") * 1000)
            ]
        ]
    ];
}


// Gráfica diaria
$diarias = $database->postulaciones->aggregate(array_merge($pipeline, [
    [
        '$group' => [
            '_id' => [
                '$dateToString' => ['format' => "%Y-%m-%d", 'date' => '$fecha_postulacion']
            ],
            'total' => ['$sum' => 1]
        ]
    ],
    ['$sort' => ['_id' => 1]]
]));

$fechasDiarias = [];
$totalesDiarias = [];

foreach ($diarias as $doc) {
    $fechasDiarias[] = $doc->_id;
    $totalesDiarias[] = $doc->total;
}

// Gráfica semanal
$semanales = $database->postulaciones->aggregate(array_merge($pipeline, [
    [
        '$group' => [
            '_id' => [
                'year' => ['$isoWeekYear' => '$fecha_postulacion'],
                'week' => ['$isoWeek' => '$fecha_postulacion']
            ],
            'total' => ['$sum' => 1]
        ]
    ],
    ['$sort' => ['_id.year' => 1, '_id.week' => 1]]
]));

$semanas = [];
$totalesSemanales = [];

foreach ($semanales as $doc) {
    $semanas[] = $doc->_id['year'] . '-W' . str_pad($doc->_id['week'], 2, '0', STR_PAD_LEFT);
    $totalesSemanales[] = $doc->total;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alcance de Postulaciones</title>
    <link rel="stylesheet" href="../css/alcance.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include 'navbar_org.php'; ?>
<h1>📊 Gráficas de Postulaciones</h1>

<form method="GET" style="margin-bottom: 20px;">
    <label for="fecha_inicio">Desde:</label>
    <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo htmlspecialchars($fechaInicio); ?>">
    <label for="fecha_fin">Hasta:</label>
    <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo htmlspecialchars($fechaFin); ?>">
    <button type="submit">Filtrar</button>
</form>

<h2>📅 Postulaciones Diarias</h2>
<canvas id="graficaDiaria"></canvas>

<h2>📈 Postulaciones Semanales</h2>
<canvas id="graficaSemanal"></canvas>

<script>
    const ctxDiaria = document.getElementById('graficaDiaria').getContext('2d');
    new Chart(ctxDiaria, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($fechasDiarias); ?>,
            datasets: [{
                label: 'Postulaciones por Día',
                data: <?php echo json_encode($totalesDiarias); ?>,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                fill: true,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    precision: 0
                }
            }
        }
    });

    const ctxSemanal = document.getElementById('graficaSemanal').getContext('2d');
    new Chart(ctxSemanal, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($semanas); ?>,
            datasets: [{
                label: 'Postulaciones por Semana',
                data: <?php echo json_encode($totalesSemanales); ?>,
                backgroundColor: 'rgba(153, 102, 255, 0.5)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    precision: 0
                }
            }
        }
    });
</script>
</body>
</html>
