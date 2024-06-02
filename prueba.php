<?php
session_start();

// Función para obtener la instrumentación inicial del script
function obtenerInstrumentacionInicial() {
    $inicio_tiempo = microtime(true); // Obtener el tiempo actual
    $memoria_inicial = memory_get_peak_usage(); // Obtener la memoria inicial
    $tiempo_de_usuario = getrusage()["ru_utime.tv_sec"] + getrusage()["ru_utime.tv_usec"] / 1e6; // Obtener el tiempo de usuario
    $tiempo_de_sistema = getrusage()["ru_stime.tv_sec"] + getrusage()["ru_stime.tv_usec"] / 1e6; // Obtener el tiempo de sistema
    
    // Devolver la instrumentación inicial como un array asociativo
    return [
        'inicio_tiempo' => $inicio_tiempo,
        'memoria_inicial' => $memoria_inicial,
        'tiempo_de_usuario_inicial' => $tiempo_de_usuario,
        'tiempo_de_sistema_inicial' => $tiempo_de_sistema
    ];
}

// Función para obtener la instrumentación final del script
function obtenerInstrumentacionFinal($instrumentacionInicial) {
    $fin_tiempo = microtime(true); // Obtener el tiempo actual
    $memoria_final = memory_get_peak_usage(); // Obtener la memoria final
    $tiempo_de_usuario_final = getrusage()["ru_utime.tv_sec"] + getrusage()["ru_utime.tv_usec"] / 1e6; // Obtener el tiempo de usuario final
    $tiempo_de_sistema_final = getrusage()["ru_stime.tv_sec"] + getrusage()["ru_stime.tv_usec"] / 1e6; // Obtener el tiempo de sistema final
    
    // Devolver la instrumentación final como un array asociativo
    return [
        'fin_tiempo' => $fin_tiempo,
        'inicio_tiempo' => $instrumentacionInicial['inicio_tiempo'],
        'memoria_usada' => $memoria_final - $instrumentacionInicial['memoria_inicial'],
        'tiempo_de_usuario' => $tiempo_de_usuario_final - $instrumentacionInicial['tiempo_de_usuario_inicial'],
        'tiempo_de_sistema' => $tiempo_de_sistema_final - $instrumentacionInicial['tiempo_de_sistema_inicial']
    ];
}

// Función para calcular la diferencia entre la instrumentación inicial y final
function calcularDiferenciaInstrumentacion($instrumentacionFinal, $archivoDescarga) {
    // Calcular la diferencia entre los tiempos y la memoria, y obtener el espacio en disco utilizado
    return [
        'tiempo_usado' => ($instrumentacionFinal['fin_tiempo'] - $instrumentacionFinal['inicio_tiempo']) * 1000, // Convertir a milisegundos
        'memoria_usada' => $instrumentacionFinal['memoria_usada'],
        'tiempo_de_usuario' => $instrumentacionFinal['tiempo_de_usuario'],
        'tiempo_de_sistema' => $instrumentacionFinal['tiempo_de_sistema'],
        'espacio_disco_utilizado' => filesize($archivoDescarga) // Tamaño del archivo generado
    ];
}

// Verificar si se está realizando una solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $genero = $_POST['genero']; // Obtener el género seleccionado
    $cantidad = (int)$_POST['cantidad']; // Obtener la cantidad de nombres a generar

    // Obtener la instrumentación inicial del script
    $instrumentacionInicial = obtenerInstrumentacionInicial();

    // Función para generar nombres aleatorios según el género y la cantidad
    function generarNombres($genero, $cantidad) {
        // Definir arrays de nombres para niños y niñas
        $nombresNinos = ["Juan", "Pedro", "Carlos", "Miguel", "José", "Luis", "Sergio", "Andrés", "Diego", "Daniel"];
        $nombresNinas = ["María", "Ana", "Laura", "Carmen", "Elena", "Lucía", "Sofía", "Marta", "Paula", "Clara"];
        
        // Seleccionar el array de nombres según el género especificado
        if ($genero === 'nino') {
            $nombres = $nombresNinos;
        } elseif ($genero === 'nina') {
            $nombres = $nombresNinas;
        } else {
            return "Género no válido. Usa 'nino' o 'nina'.";
        }

        // Verificar si la cantidad es válida
        if ($cantidad < 1 || $cantidad > 100000) {
            return "Cantidad no válida. Debe ser entre 1 y 100000.";
        }

        // Generar nombres aleatorios según la cantidad especificada
        $nombresGenerados = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $nombresGenerados[] = $nombres[array_rand($nombres)];
        }

        return $nombresGenerados; // Devolver los nombres generados
    }

    // Llamar a la función para generar nombres con el género y la cantidad especificados
    $nombresGenerados = generarNombres($genero, $cantidad);

    // Almacenar los nombres generados en la sesión
    if (is_array($nombresGenerados)) {
        $_SESSION['nombres_generados'] = $nombresGenerados;
    } else {
        $_SESSION['nombres_generados'] = [];
    }

    // Determinar el formato de descarga seleccionado (JSON o CSV)
    if ($_POST['formato'] === 'json') {
        $archivoDescarga = 'nombres.json'; // Nombre del archivo JSON
        $contenido = json_encode(['nombres' => $nombresGenerados], JSON_PRETTY_PRINT); // Convertir los nombres a formato JSON
        file_put_contents($archivoDescarga, $contenido); // Escribir los nombres en el archivo JSON
    } elseif ($_POST['formato'] === 'csv') {
        $archivoDescarga = 'nombres.csv'; // Nombre del archivo CSV
        $contenido = "Lista de Nombres:\n"; // Encabezado del archivo CSV
        foreach ($nombresGenerados as $nombre) {
            $contenido .= "$nombre\n"; // Agregar cada nombre a una línea del archivo CSV
        }
        file_put_contents($archivoDescarga, $contenido); // Escribir los nombres en el archivo CSV
    }

    // Obtener la instrumentación final del script
    $instrumentacionFinal = obtenerInstrumentacionFinal($instrumentacionInicial);

    // Calcular la diferencia entre la instrumentación inicial y final
    $_SESSION['instrumentacion_generacion'] = calcularDiferenciaInstrumentacion($instrumentacionFinal, $archivoDescarga);

    // Obtener la hora inicial para medir el tiempo de descarga
    $tiempo_inicio_descarga = microtime(true);

    header("Content-disposition: attachment; filename=$archivoDescarga");
    header("Content-type: application/octet-stream");
    readfile($archivoDescarga);

    // Obtener la hora final después de la descarga
    $tiempo_fin_descarga = microtime(true);
    $_SESSION['tiempo_descarga'] = ($tiempo_fin_descarga - $tiempo_inicio_descarga) * 1000; // Convertir a milisegundos

    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador de Nombres</title>
    <link rel="stylesheet" href="diseño.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('descargarJson').addEventListener('click', function() {
                setTimeout(() => {
                    location.reload();
                }, 1000); // Actualizar la página después de 1 segundo
            });

            document.getElementById('descargarCsv').addEventListener('click', function() {
                setTimeout(() => {
                    location.reload();
                }, 1000); // Actualizar la página después de 1 segundo
            });
        });
    </script>
</head>
<body>
<body style="background-image: url('diseño.avif'); background-size: cover;">
    <div class="container">
        <h1>Generador de Nombres</h1>

        <form method="post">
            <label for="genero">Seleccione el género:</label>
            <select name="genero" id="genero">
                <option value="nino">Niño</option>
                <option value="nina">Niña</option>
            </select>
            <br><br>
            <label for="cantidad">Cantidad de nombres (1-100000):</label>
            <input type="number" id="cantidad" name="cantidad" min="1" max="100000">
            <br><br>
            <button type="submit" name="formato" value="json" id="descargarJson">Descargar JSON</button>
            <button type="submit" name="formato" value="csv" id="descargarCsv">Descargar CSV</button>
        </form>
    </div>

    <?php if (!empty($_SESSION['instrumentacion_generacion']) && isset($_SESSION['tiempo_descarga'])): ?>
        <div class="container">
            <h2>Instrumentación del Código</h2>
            <table border="1">
                <tr>
                    <th>Parámetro</th>
                    <th>Valor</th>
                </tr>
                <tr>
                <!--Muestra el tiempo utilizado para generar los nombres en milisegundos-->
                    <td>Tiempo de Generación</td>
                    <td><?php echo $_SESSION['instrumentacion_generacion']['tiempo_usado']; ?> ms</td>  
                </tr>
                <tr>
                <!-- Muestra el tiempo de descarga en milisegundos-->
                    <td>Tiempo de Descarga</td>
                    <td><?php echo $_SESSION['tiempo_descarga']; ?> ms</td>
                </tr>
                <tr>
                <!--Muestra el tiempo de ejecución en segundos, calculado dividiendo el tiempo de generación entre 1000-->
                    <td>Tiempo de Ejecución</td>
                    <td><?php echo $_SESSION['instrumentacion_generacion']['tiempo_usado'] / 1000; ?> segundos</td>
                </tr>
                <tr>
                <!-- Muestra el consumo de memoria en bytes-->
                    <td>Consumo de Memoria</td>
                    <td><?php echo $_SESSION['instrumentacion_generacion']['memoria_usada']; ?> bytes</td>
                </tr>
                <tr>
                <!-- Muestra el espacio en disco utilizado en bytes-->
                    <td>Acceso a Disco</td>
                    <td><?php echo $_SESSION['instrumentacion_generacion']['espacio_disco_utilizado']; ?> bytes</td>
                </tr>
                <tr>
                <!-- Muestra el tiempo del sistema en segundos-->
                    <td>Tiempo de Sistema</td>
                    <td><?php echo $_SESSION['instrumentacion_generacion']['tiempo_de_sistema']; ?> segundos</td>
                </tr>
            </table>
        </div>
    <?php endif; ?>
</body>
</html>

