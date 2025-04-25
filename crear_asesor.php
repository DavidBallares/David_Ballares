<?php
session_start();

// Verificar que el usuario sea Admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

// Obtener el id_empresa del Admin actual
if (!isset($_SESSION['id_empresa'])) {
    die("Error: No se encontró el id_empresa del Admin en la sesión. Por favor, inicia sesión nuevamente.");
}
$id_empresa_admin = $_SESSION['id_empresa'];

// Conexión a la base de datos
$host = '127.0.0.1';
$db = 'estilo_personal_hombres';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}


require 'vendor/autoload.php';
use Picqer\Barcode\BarcodeGeneratorPNG;

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['documento'] ?? '');
    $especialidad = trim($_POST['especialidad'] ?? '');

    if (empty($nombre) || empty($especialidad)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        // Obtener el próximo ID de asesor
        $stmt = $pdo->query("SELECT MAX(id_asesor) AS max_id FROM asesores");
        $row = $stmt->fetch();
        $id_asesor = ($row['max_id'] ?? 0) + 1;

        // Generar el valor del código de barras (formato: ASESOR-ID-FECHA)
        $fecha = date('Ymd'); // Ejemplo: 20250425
        $cod_barra = "ASESOR-$id_asesor-$fecha";

        // Generar la imagen del código de barras
        $generator = new BarcodeGeneratorPNG();
        $barcodeImage = $generator->getBarcode($cod_barra, $generator::TYPE_CODE_128);
        $barcodePath = "barcodes/asesor_$id_asesor.png";
        file_put_contents($barcodePath, $barcodeImage);

        // Insertar el nuevo asesor con el id_empresa del Admin
        $stmt = $pdo->prepare("INSERT INTO asesores (id_asesor, nombre, especialidad, cod_barra, id_empresa) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_asesor, $nombre, $especialidad, $cod_barra, $id_empresa_admin]);

        header("Location: admin.php?mensaje=Asesor creado exitosamente");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Asesor - Sistema de Asesorías de Estilo</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilo.css" rel="stylesheet">
</head>
<body onload="frm1.documento.focus()">
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">Sistema de Asesorías</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin.php">Volver al Panel</a>
                <a class="nav-link" href="logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h2>Crear Nuevo Asesor</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST" name="frm1">
            <div class="mb-3">
                <label for="documento" class="form-label">Nombre del Asesor</label>
                <input type="text" class="form-control" id="documento" name="documento" required>
            </div>
            <div class="mb-3">
                <label for="especialidad" class="form-label">Especialidad</label>
                <input type="text" class="form-control" id="especialidad" name="especialidad" required>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                <button type="submit" class="btn btn-success">Crear Asesor</button>
                <a href="admin.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>