<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$id_empresa_admin = $_SESSION['id_empresa'];

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

// Procesar el formulario cuando se envíe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documento = trim($_POST['documento']);
    $nombres = trim($_POST['nombres']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $clave = trim($_POST['clave']);
    $id_rol = 3; // Rol de "Usuario"

    // Validar los datos
    if (empty($documento) || empty($nombres) || empty($telefono) || empty($email) || empty($clave)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El email no es válido.";
    } else {
        // Verificar si el documento o email ya existen
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE documento = ? OR email = ?");
        $stmt->execute([$documento, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "El documento o email ya están registrados.";
        } else {
            // Encriptar la clave
            $clave_encriptada = password_hash($clave, PASSWORD_BCRYPT);

            // Insertar el nuevo usuario
            $stmt = $pdo->prepare("INSERT INTO usuarios (documento, nombres, telefono, email, clave, id_rol, id_empresa) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$documento, $nombres, $telefono, $email, $clave_encriptada, $id_rol, $id_empresa_admin]);

            // Redirigir al panel de Admin con un mensaje de éxito
            header("Location: admin.php?mensaje=Usuario creado exitosamente");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevo Usuario - Sistema de Asesorías de Estilo</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilo.css" rel="stylesheet">
</head>
<body>
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
        <h2>Crear Nuevo Usuario</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="crear_usuario.php">
            <div class="mb-3">
                <label for="documento" class="form-label">Documento</label>
                <input type="text" class="form-control" id="documento" name="documento" required>
            </div>
            <div class="mb-3">
                <label for="nombres" class="form-label">Nombres</label>
                <input type="text" class="form-control" id="nombres" name="nombres" required>
            </div>
            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" id="telefono" name="telefono" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="clave" class="form-label">Clave</label>
                <input type="password" class="form-control" id="clave" name="clave" required>
            </div>
            <button type="submit" class="btn btn-success">Crear Usuario</button>
            <a href="admin.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>