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

$documento = $_GET['documento'] ?? null;
if (!$documento) {
    header("Location: superadmin.php");
    exit();
}

// Obtener datos del Admin
$stmt = $pdo->prepare("SELECT u.*, e.nombre_empresa 
                       FROM usuarios u 
                       JOIN empresas e ON u.id_empresa = e.id_empresa 
                       WHERE u.documento = ? AND u.id_rol = 2");
$stmt->execute([$documento]);
$admin = $stmt->fetch();

if (!$admin) {
    header("Location: superadmin.php");
    exit();
}

// Obtener todas las empresas para el formulario
$stmt = $pdo->query("SELECT * FROM empresas");
$empresas = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres = $_POST['nombres'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $email = $_POST['email'] ?? '';
    $clave = $_POST['clave'] ?? '';
    $id_empresa = $_POST['id_empresa'] ?? '';

    if ($nombres && $telefono && $email && $id_empresa) {
        if ($clave) {
            $clave_hash = password_hash($clave, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE usuarios 
                                   SET nombres = ?, telefono = ?, email = ?, clave = ?, id_empresa = ? 
                                   WHERE documento = ? AND id_rol = 2");
            $stmt->execute([$nombres, $telefono, $email, $clave_hash, $id_empresa, $documento]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios 
                                   SET nombres = ?, telefono = ?, email = ?, id_empresa = ? 
                                   WHERE documento = ? AND id_rol = 2");
            $stmt->execute([$nombres, $telefono, $email, $id_empresa, $documento]);
        }
        header("Location: superadmin.php");
        exit();
    } else {
        $error = "Por favor, completa todos los campos requeridos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Admin - Sistema de Asesorías de Estilo</title>
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
        <h2>Editar Admin</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="documento" class="form-label">Documento</label>
                <input type="text" class="form-control" id="documento" value="<?php echo htmlspecialchars($admin['documento']); ?>" disabled>
            </div>
            <div class="mb-3">
                <label for="nombres" class="form-label">Nombres</label>
                <input type="text" class="form-control" id="nombres" name="nombres" value="<?php echo htmlspecialchars($admin['nombres']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($admin['telefono']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="clave" class="form-label">Nueva Clave (opcional)</label>
                <input type="password" class="form-control" id="clave" name="clave" placeholder="Dejar en blanco para no cambiar">
            </div>
            <div class="mb-3">
                <label for="id_empresa" class="form-label">Empresa</label>
                <select class="form-control" id="id_empresa" name="id_empresa" required>
                    <?php foreach ($empresas as $empresa): ?>
                        <option value="<?php echo htmlspecialchars($empresa['id_empresa']); ?>" 
                                <?php echo $admin['id_empresa'] == $empresa['id_empresa'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($empresa['nombre_empresa']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>