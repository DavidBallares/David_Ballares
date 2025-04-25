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

// Obtener el asesor a editar
if (!isset($_GET['id_asesor'])) {
    header("Location: admin.php");
    exit();
}
$id_asesor = $_GET['id_asesor'];

$stmt = $pdo->prepare("SELECT * FROM asesores WHERE id_asesor = ? AND id_empresa = ?");
$stmt->execute([$id_asesor, $id_empresa_admin]);
$asesor = $stmt->fetch();

if (!$asesor) {
    header("Location: admin.php");
    exit();
}

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['documento'] ?? '');
    $especialidad = trim($_POST['especialidad'] ?? '');

    if (empty($nombre) || empty($especialidad)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        $stmt = $pdo->prepare("UPDATE asesores SET nombre = ?, especialidad = ? WHERE id_asesor = ? AND id_empresa = ?");
        $stmt->execute([$nombre, $especialidad, $id_asesor, $id_empresa_admin]);
        header("Location: admin.php?mensaje=Asesor actualizado exitosamente");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Asesor - Sistema de Asesorías de Estilo</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilo.css" rel="stylesheet">
</head>
<body onload="frm1.placa.focus()">
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
        <h2>Editar Asesor</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST" name="frm1">
            <div class="mb-3">
                <label for="documento" class="form-label">Nombre del Asesor</label>
                <input type="text" class="form-control" id="documento" name="documento" value="<?php echo htmlspecialchars($asesor['nombre']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="especialidad" class="form-label">Especialidad</label>
                <input type="text" class="form-control" id="especialidad" name="especialidad" value="<?php echo htmlspecialchars($asesor['especialidad']); ?>" required>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                <button type="submit" class="btn btn-success">Guardar Cambios</button>
                <a href="admin.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>