<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Superadmin') {
    header("Location: index.php");
    exit();
}

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

// Obtener los tipos de licencia para el formulario
$stmt = $pdo->query("SELECT * FROM tipo_licencia");
$tipos_licencia = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_empresa = $_POST['nombre_empresa'] ?? '';
    $fecha_inicial = $_POST['fecha_inicial'] ?? '';
    $fecha_final = $_POST['fecha_final'] ?? '';
    $id_tipo_licencia = $_POST['id_tipo_licencia'] ?? '';
    $id_estado = $_POST['id_estado'] ?? 1; // 1 = Vigente, 2 = Vencida

    if ($nombre_empresa && $fecha_inicial && $fecha_final && $id_tipo_licencia) {
        // Insertar la empresa
        $stmt = $pdo->prepare("INSERT INTO empresas (nombre_empresa) VALUES (?)");
        $stmt->execute([$nombre_empresa]);
        $id_empresa = $pdo->lastInsertId();

        // Generar un ID de licencia único (simple, para este ejemplo)
        $id_licencia = strtoupper(substr(md5(uniqid()), 0, 20));

        // Insertar la licencia asociada
        $stmt = $pdo->prepare("INSERT INTO licencia (id_licencia, fecha_inicial, fecha_final, id_empresa, id_tipo_licencia, id_estado) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_licencia, $fecha_inicial, $fecha_final, $id_empresa, $id_tipo_licencia, $id_estado]);

        header("Location: superadmin.php");
        exit();
    } else {
        $error = "Por favor, completa todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Empresa - Sistema de Asesorías de Estilo</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilo.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">Sistema de Asesorías</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="superadmin.php">Volver al Panel</a>
                <a class="nav-link" href="logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h2>Crear Nueva Empresa</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="nombre_empresa" class="form-label">Nombre de la Empresa</label>
                <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" required>
            </div>
            <div class="mb-3">
                <label for="fecha_inicial" class="form-label">Fecha Inicio Licencia</label>
                <input type="datetime-local" class="form-control" id="fecha_inicial" name="fecha_inicial" required>
            </div>
            <div class="mb-3">
                <label for="fecha_final" class="form-label">Fecha Fin Licencia</label>
                <input type="datetime-local" class="form-control" id="fecha_final" name="fecha_final" required>
            </div>
            <div class="mb-3">
                <label for="id_tipo_licencia" class="form-label">Tipo de Licencia</label>
                <select class="form-control" id="id_tipo_licencia" name="id_tipo_licencia" required>
                    <?php foreach ($tipos_licencia as $tipo): ?>
                        <option value="<?php echo htmlspecialchars($tipo['id_tipo_licencia']); ?>">
                            <?php echo htmlspecialchars($tipo['nombre_tipo_licencia']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="id_estado" class="form-label">Estado de la Licencia</label>
                <select class="form-control" id="id_estado" name="id_estado" required>
                    <option value="1">Vigente</option>
                    <option value="2">Vencida</option>
                </select>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Crear Empresa</button>
            </div>
        </form>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>