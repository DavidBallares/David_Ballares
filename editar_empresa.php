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

$id_empresa = $_GET['id_empresa'] ?? null;
if (!$id_empresa) {
    header("Location: superadmin.php");
    exit();
}

// Obtener datos de la empresa y su licencia
$stmt = $pdo->prepare("SELECT e.*, l.fecha_inicial, l.fecha_final, l.id_estado, l.id_tipo_licencia 
                       FROM empresas e 
                       JOIN licencia l ON e.id_empresa = l.id_empresa 
                       WHERE e.id_empresa = ?");
$stmt->execute([$id_empresa]);
$empresa = $stmt->fetch();

if (!$empresa) {
    header("Location: superadmin.php");
    exit();
}

// Obtener los tipos de licencia para el formulario
$stmt = $pdo->query("SELECT * FROM tipo_licencia");
$tipos_licencia = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_empresa = $_POST['nombre_empresa'] ?? '';
    $fecha_inicial = $_POST['fecha_inicial'] ?? '';
    $fecha_final = $_POST['fecha_final'] ?? '';
    $id_tipo_licencia = $_POST['id_tipo_licencia'] ?? '';
    $id_estado = $_POST['id_estado'] ?? 1;

    if ($nombre_empresa && $fecha_inicial && $fecha_final && $id_tipo_licencia) {
        // Actualizar la empresa
        $stmt = $pdo->prepare("UPDATE empresas 
                               SET nombre_empresa = ? 
                               WHERE id_empresa = ?");
        $stmt->execute([$nombre_empresa, $id_empresa]);

        // Actualizar la licencia
        $stmt = $pdo->prepare("UPDATE licencia 
                               SET fecha_inicial = ?, fecha_final = ?, id_tipo_licencia = ?, id_estado = ? 
                               WHERE id_empresa = ?");
        $stmt->execute([$fecha_inicial, $fecha_final, $id_tipo_licencia, $id_estado, $id_empresa]);

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
    <title>Editar Empresa - Sistema de Asesorías de Estilo</title>
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
        <h2>Editar Empresa</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="nombre_empresa" class="form-label">Nombre de la Empresa</label>
                <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" value="<?php echo htmlspecialchars($empresa['nombre_empresa']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="fecha_inicial" class="form-label">Fecha Inicio Licencia</label>
                <input type="datetime-local" class="form-control" id="fecha_inicial" name="fecha_inicial" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($empresa['fecha_inicial']))); ?>" required>
            </div>
            <div class="mb-3">
                <label for="fecha_final" class="form-label">Fecha Fin Licencia</label>
                <input type="datetime-local" class="form-control" id="fecha_final" name="fecha_final" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($empresa['fecha_final']))); ?>" required>
            </div>
            <div class="mb-3">
                <label for="id_tipo_licencia" class="form-label">Tipo de Licencia</label>
                <select class="form-control" id="id_tipo_licencia" name="id_tipo_licencia" required>
                    <?php foreach ($tipos_licencia as $tipo): ?>
                        <option value="<?php echo htmlspecialchars($tipo['id_tipo_licencia']); ?>" 
                                <?php echo $empresa['id_tipo_licencia'] == $tipo['id_tipo_licencia'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipo['nombre_tipo_licencia']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="id_estado" class="form-label">Estado de la Licencia</label>
                <select class="form-control" id="id_estado" name="id_estado" required>
                    <option value="1" <?php echo $empresa['id_estado'] == 1 ? 'selected' : ''; ?>>Vigente</option>
                    <option value="2" <?php echo $empresa['id_estado'] == 2 ? 'selected' : ''; ?>>Vencida</option>
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