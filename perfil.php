<?php
session_start();

if (!isset($_SESSION['rol'])) {
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

$documento = $_SESSION['documento'];
$stmt = $pdo->prepare("SELECT u.*, r.roles AS rol 
                       FROM usuarios u 
                       JOIN rol r ON u.id_rol = r.id_rol 
                       WHERE u.documento = ?");
$stmt->execute([$documento]);
$usuario = $stmt->fetch();

if (!$usuario) {
    session_destroy();
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres = $_POST['nombres'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $email = $_POST['email'] ?? '';
    $clave = $_POST['clave'] ?? '';

    if ($nombres && $telefono && $email && $clave) {
        $clave_hash = password_hash($clave, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE usuarios 
                               SET nombres = ?, telefono = ?, email = ?, clave = ? 
                               WHERE documento = ?");
        $stmt->execute([$nombres, $telefono, $email, $clave_hash, $documento]);
        header("Location: perfil.php");
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
    <title>Mi Perfil - Sistema de Asesorías de Estilo</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilo.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">Sistema de Asesorías</a>
            <div class="navbar-nav ms-auto">
                <?php if ($_SESSION['rol'] == 'Superadmin'): ?>
                    <a class="nav-link" href="superadmin.php">Volver al Panel</a>
                <?php elseif ($_SESSION['rol'] == 'Admin'): ?>
                    <a class="nav-link" href="admin.php">Volver al Panel</a>
                <?php else: ?>
                    <a class="nav-link" href="usuario.php">Volver al Panel</a>
                <?php endif; ?>
                <a class="nav-link" href="logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h2>Mi Perfil</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="documento" class="form-label">Documento</label>
                <input type="text" class="form-control" id="documento" value="<?php echo htmlspecialchars($usuario['documento']); ?>" disabled>
            </div>
            <div class="mb-3">
                <label for="nombres" class="form-label">Nombres</label>
                <input type="text" class="form-control" id="nombres" name="nombres" value="<?php echo htmlspecialchars($usuario['nombres']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="clave" class="form-label">Nueva Clave</label>
                <input type="password" class="form-control" id="clave" name="clave" placeholder="Ingresa una nueva clave" required>
            </div>
            <div class="mb-3">
                <label for="rol" class="form-label">Rol</label>
                <input type="text" class="form-control" id="rol" value="<?php echo htmlspecialchars($usuario['rol']); ?>" disabled>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>