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

// Obtener todas las empresas para el formulario
$stmt = $pdo->query("SELECT * FROM empresas");
$empresas = $stmt->fetchAll();

// Verificar si hay empresas disponibles
if (empty($empresas)) {
    $error = "No hay empresas disponibles. Crea una empresa primero.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documento = $_POST['documento'] ?? '';
    $nombres = $_POST['nombres'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $email = $_POST['email'] ?? '';
    $clave = $_POST['clave'] ?? '';
    $id_empresa = isset($_POST['id_empresa']) ? trim($_POST['id_empresa']) : '';

    // Depuración: Mostrar los valores recibidos
    $debug = [];
    if (empty($documento)) $debug[] = "Documento está vacío";
    if (empty($nombres)) $debug[] = "Nombres está vacío";
    if (empty($telefono)) $debug[] = "Teléfono está vacío";
    if (empty($email)) $debug[] = "Email está vacío";
    if (empty($clave)) $debug[] = "Clave está vacía";
    if ($id_empresa === '' || $id_empresa === '0') $debug[] = "ID Empresa no es válido (valor recibido: '" . htmlspecialchars($id_empresa) . "')";

    if ($documento && $nombres && $telefono && $email && $clave && $id_empresa !== '' && $id_empresa !== '0') {
        // Verificar si el id_empresa existe en la tabla empresas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM empresas WHERE id_empresa = ?");
        $stmt->execute([$id_empresa]);
        if ($stmt->fetchColumn() == 0) {
            $error = "La empresa seleccionada no es válida (id_empresa: " . htmlspecialchars($id_empresa) . ").";
        } else {
            $clave_hash = password_hash($clave, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (documento, nombres, telefono, email, clave, id_rol, id_empresa) 
                                   VALUES (?, ?, ?, ?, ?, 2, ?)");
            $stmt->execute([$documento, $nombres, $telefono, $email, $clave_hash, $id_empresa]);
            header("Location: superadmin.php");
            exit();
        }
    } else {
        $error = "Por favor, completa todos los campos. Problemas detectados: " . implode(", ", $debug);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Admin - Sistema de Asesorías de Estilo</title>
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
        <h2>Crear Nuevo Admin</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST" id="adminForm">
            <div class="mb-3">
                <label for="documento" class="form-label">Documento</label>
                <input type="text" class="form-control" id="documento" name="documento" autocomplete="off" required>
            </div>
            <div class="mb-3">
                <label for="nombres" class="form-label">Nombres</label>
                <input type="text" class="form-control" id="nombres" name="nombres" autocomplete="name" required>
            </div>
            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" id="telefono" name="telefono" autocomplete="tel" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" autocomplete="email" required>
            </div>
            <div class="mb-3">
                <label for="clave" class="form-label">Clave</label>
                <input type="password" class="form-control" id="clave" name="clave" autocomplete="new-password" required>
            </div>
            <div class="mb-3">
                <label for="id_empresa" class="form-label">Empresa</label>
                <select class="form-control" id="id_empresa" name="id_empresa" required>
                    <?php if (count($empresas) > 0): ?>
                        <?php foreach ($empresas as $empresa): ?>
                            <option value="<?php echo htmlspecialchars($empresa['id_empresa']); ?>"
                                    <?php echo $empresa['id_empresa'] == ($empresas[0]['id_empresa']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($empresa['nombre_empresa']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">No hay empresas disponibles</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Crear Admin</button>
            </div>
        </form>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('adminForm').addEventListener('submit', function(event) {
            const idEmpresa = document.getElementById('id_empresa').value;
            if (idEmpresa === '' || idEmpresa === '0') {
                event.preventDefault();
                alert('Por favor, selecciona una empresa válida.');
            }
        });
    </script>
</body>
</html>