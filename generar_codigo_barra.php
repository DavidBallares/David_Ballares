<?php
// Habilitar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir la librería de generación de códigos de barras
require 'vendor/autoload.php'; // Si usaste Composer
// Si usaste la opción manual, descomenta la siguiente línea y ajusta la ruta:
// require 'lib/BarcodeGeneratorPNG.php';

use Picqer\Barcode\BarcodeGeneratorPNG;

// Obtener el código de barras desde la URL
$codigo = $_GET['codigo'] ?? '';
if (!$codigo) {
    // Si no se proporciona un código, mostrar una imagen de error o salir
    header('HTTP/1.1 400 Bad Request');
    exit('Código no proporcionado');
}

try {
    // Generar el código de barras
    $generator = new BarcodeGeneratorPNG();
    $barcodeImage = $generator->getBarcode($codigo, $generator::TYPE_CODE_128);

    // Enviar la imagen como respuesta
    header('Content-Type: image/png');
    echo $barcodeImage;
} catch (Exception $e) {
    // Si hay un error, mostrar un mensaje
    header('HTTP/1.1 500 Internal Server Error');
    exit('Error al generar el código de barras: ' . $e->getMessage());
}
exit;