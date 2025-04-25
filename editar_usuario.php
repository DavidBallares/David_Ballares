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

// Obtener el usuario a editar
if (!isset($_GET['documento'])) {
    header("Location: gestionar_usuarios.php");
    exit();
}
$documento = $_GET['documento'];

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE documento = ? AND id_empresa = ? AND id_rol = 3");
$stmt->execute([$documento, $id_empresa_admin]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header("Location: gestionar_usuarios.php");
    exit();
}

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres = trim($_POST['nombres'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $clave = trim($_POST['clave'] ?? '');

    if (empty($nombres) || empty($telefono) || empty($email)) {
        $error = "Por favor, completa todos los campos obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, ingresa un email válido.";
    } else {
        if (!empty($clave)) {
            // Actualizar con nueva clave
            $clave_encriptada = password_hash($clave, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nombres = ?, telefono = ?, email = ?, clave = ? WHERE documento = ? AND id_empresa = ?");
            $stmt->execute([$nombres, $telefono, $email, $clave_encriptada, $documento, $id_empresa_admin]);
        } else {
            // Actualizar sin cambiar la clave
            $stmt = $pdo->prepare("UPDATE usuarios SET nombres = ?, telefono = ?, email = ? WHERE documento = ? AND id_empresa = ?");
            $stmt->execute([$nombres, $telefono, $email, $documento, $id_empresa_admin]);
        }
        header("Location: gestionar_usuarios.php?mensaje=Usuario actualizado exitosamente");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Sistema de Asesorías de Estilo</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilo.css" rel="stylesheet">
</head>
<body onload="frm1.placa.focus()">
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">Sistema de Asesorías</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="gestionar_usuarios.php">Volver a Gestión de Usuarios</a>
                <a class="nav-link" href="logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h2>Editar Usuario</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST" name="frm1">
            <div class="mb-3">
                <label for="documento" class="form-label">Documento</label>
                <input type="text" class="form-control" id="documento" name="documento" value="<?php echo htmlspecialchars($usuario['documento']); ?>" disabled>
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
                <label for="clave" class="form-label">Nueva Clave (dejar en blanco para no cambiar)</label>
                <input type="password" class="form-control" id="clave" name="clave">
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                <button type="submit" class="btn btn-success">Guardar Cambios</button>
                <a href="gestionar_usuarios.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>