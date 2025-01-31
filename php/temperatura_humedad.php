<?php
session_start();
include_once 'conexion.php';

if(!isset($_SESSION['usuario'])){
    echo '
        <script>
            alert("Por favor debes de iniciar sesion primero");
            window.location = "../index.php";
        </script>
    ';
    session_destroy();
    die();
}

// Consulta para obtener los datos
$SQL = "SELECT * FROM temp_hum_amb ORDER BY fecha_registro";
$consulta = mysqli_query($con, $SQL);

$temp = [];
$hum = [];
$fechas = [];
$datos_amchart = [];

while ($resultado = mysqli_fetch_array($consulta)) {
    // Datos para los gráficos de Chart.js
    $temp[] = floatval($resultado['temperatura_ambiente']);
    $hum[] = floatval($resultado['humedad_ambiente']);
    $fechas[] = date('Y:m:d H:i:s', strtotime($resultado['fecha_registro']));
    
    // Datos para AMCharts
    $datos_amchart[] = [
        "date" => $resultado['fecha_registro'],
        "value" => floatval($resultado['temperatura_ambiente'])
    ];
}

// Convertir a JSON para usar en JavaScript
$fecha_json = json_encode($fechas);
$temp_json = json_encode($temp);
$hum_json = json_encode($hum);
$amchart_json = json_encode($datos_amchart);

// Obtener valores actuales (último registro)
$temp_actual = !empty($temp) ? end($temp) : 0;
$hum_actual = !empty($hum) ? end($hum) : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroVision</title>
	<link rel="icon" href="https://img.icons8.com/?size=100&id=80791&format=png&color=000000" type="image/x-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="icon" href="https://img.icons8.com/?size=100&id=80791&format=png&color=000000" type="image/x-icon">
    <script src="https://code.highcharts.com/highcharts.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #e2e8f0 0%, #edf2f7 100%);
        }
        .metric-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        .temperature-gradient {
            background: linear-gradient(45deg, #3b82f6, #60a5fa);
        }
        .humidity-gradient {
            background: linear-gradient(45deg, #10b981, #34d399);
        }
        h1, h2 {
            font-family: 'Arial', sans-serif;
        }
        .chart-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 20px;
        }
        .chart {
            flex: 1;
            min-width: 400px;
            height: 400px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="../bienvenido.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left mr-2"></i>Volver al menú principal
                    </a>
                </div>
                <div class="flex items-center">
                    <a href="cerrar_sesion.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800">Monitoreo Ambiental</h1>
            <p class="text-gray-600 mt-2">Datos en tiempo real de temperatura y humedad</p>
        </div>

        <!-- Current Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="metric-card temperature-gradient text-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Temperatura Actual</p>
                        <p class="text-4xl font-bold mt-2"><?= $temp_actual ?>°C</p>
        			</div>
        			<i class="fas fa-thermometer-half text-4xl opacity-90"></i>
    			</div>
			</div>

			<div class="metric-card humidity-gradient text-white p-6">
    			<div class="flex items-center justify-between">
        			<div>
            			<p class="text-sm opacity-90">Humedad Actual</p>
            			<p class="text-4xl font-bold mt-2"><?= $hum_actual ?>%</p>
        			</div>
        			<i class="fas fa-tint text-4xl opacity-90"></i>
    			</div>
			</div>
        </div>

        <!-- Gráfico Principal -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="metric-card p-6 mb-8">
                <div id="tempChart_h"></div>
            </div>
            
            <div class="metric-card p-6 mb-8">
                <div id="humChart_h"></div>
            </div>
        </div>

        <!-- Gráficos Secundarios -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="metric-card p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Distribución de Temperatura</h2>
                <canvas id="tempChart"></canvas>
            </div>
            
            <div class="metric-card p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Distribución de Humedad</h2>
                <canvas id="humChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        
        // Datos de ejemplo (reemplaza con tus datos dinámicos)
        const fechas = <?= $fecha_json ?>; // Formato: ["2023-10-01 12:00:00", "2023-10-01 13:00:00"]
        const temperaturas = <?= $temp_json ?>; // [22, 23, 24]
        const humedades = <?= $hum_json ?>; // [60, 62, 61]

        // Gráfico de Temperatura

        Highcharts.chart('tempChart_h', {
        title: { text: 'Gráfico de Temperatura' },
        xAxis: { categories: <?= $fecha_json ?>, labels: {
                    formatter: function() {
                        // Formatear la fecha para mostrar año-mes-dia hora:minuto:segundo
                        return Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', new Date(this.value).getTime());
                    }
                } },
        series: [{
            name: 'Temperatura (°C)',
            data: <?= $temp_json ?>
            }]
        });

        // Gráfico de Humedad
        Highcharts.chart('humChart_h', {
        title: { text: 'Gráfico de Humedad' },
        xAxis: { categories: <?= $fecha_json ?>, labels: {
                    formatter: function() {
                        // Formatear la fecha para mostrar año-mes-dia hora:minuto:segundo
                        return Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', new Date(this.value).getTime());
                    }
                } },
        series: [{
            name: 'Humedad (%)',
            data: <?= $hum_json ?>
            }]
        });
    </script>
 
</body>
</html>