<?php
session_start();

// Si el usuario ya está logueado, redirigir según su rol
if (isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] === 'Superadmin') {
        header("Location: superadmin.php");
    } elseif ($_SESSION['rol'] === 'Admin') {
        header("Location: admin.php");
    } elseif ($_SESSION['rol'] === 'Usuario') {
        header("Location: usuario.php");
    }
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documento = trim($_POST['documento'] ?? '');
    $clave = trim($_POST['clave'] ?? '');

    if (empty($documento) || empty($clave)) {
        $error = "Por favor, complete todos los campos.";
    } else {
        // Buscar el usuario en la base de datos
        $stmt = $pdo->prepare("SELECT u.*, r.roles AS nombre_rol 
                               FROM usuarios u 
                               JOIN rol r ON u.id_rol = r.id_rol 
                               WHERE u.documento = ?");
        $stmt->execute([$documento]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($clave, $usuario['clave'])) {
            // Guardar datos en la sesión
            $_SESSION['rol'] = $usuario['nombre_rol'];
            $_SESSION['id_empresa'] = $usuario['id_empresa'];
            $_SESSION['documento'] = $usuario['documento'];

            // Redirigir según el rol
            if ($usuario['nombre_rol'] === 'Superadmin') {
                header("Location: superadmin.php");
            } elseif ($usuario['nombre_rol'] === 'Admin') {
                header("Location: admin.php");
            } else {
                header("Location: usuario.php");
            }
            exit();
        } else {
            $error = "Documento o clave incorrectos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Asesorías de Estilo</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilo.css" rel="stylesheet">
</head>
<body onload="frm1.documento.focus()">
    <div class="container mt-5">
        <h2>Iniciar Sesión</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" name="frm1">
            <div class="mb-3">
                <label for="documento" class="form-label">Documento</label>
                <input type="text" class="form-control" id="documento" name="documento" required>
            </div>
            <div class="mb-3">
                <label for="clave" class="form-label">Clave</label>
                <input type="password" class="form-control" id="clave" name="clave" required>
            </div>
            <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
        </form>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>