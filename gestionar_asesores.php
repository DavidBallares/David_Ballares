<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Admin') {
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

// Obtener el id_empresa del Admin actual
$documento = $_SESSION['documento'];
$stmt = $pdo->prepare("SELECT id_empresa FROM usuarios WHERE documento = ?");
$stmt->execute([$documento]);
$admin = $stmt->fetch();
$id_empresa = $admin['id_empresa'];

// Filtros de búsqueda
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM asesores WHERE id_empresa = ? AND (nombre LIKE ? OR especialidad LIKE ? OR cod_barra LIKE ?)";
$searchTerm = "%$search%";
$stmt = $pdo->prepare($query);
$stmt->execute([$id_empresa, $searchTerm, $searchTerm, $searchTerm]);
$asesores = $stmt->fetchAll();

// Procesar eliminación de asesores
if (isset($_POST['eliminar_asesor'])) {
    $id_asesor = $_POST['id_asesor'];
    $stmt = $pdo->prepare("DELETE FROM asesores WHERE id_asesor = ? AND id_empresa = ?");
    $stmt->execute([$id_asesor, $id_empresa]);
    header("Location: gestionar_asesores.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Asesores - Sistema de Asesorías de Estilo</title>
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
        <h2>Gestionar Asesores</h2>
        <!-- Formulario de búsqueda -->
        <form method="GET" class="mb-3">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Buscar por nombre, especialidad o código de barras" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </form>
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Especialidad</th>
                    <th>Código de Barras</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($asesores as $asesor): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($asesor['id_asesor']); ?></td>
                        <td><?php echo htmlspecialchars($asesor['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($asesor['especialidad']); ?></td>
                        <td>
                            <img src="generar_codigo_barra.php?codigo=<?php echo urlencode($asesor['cod_barra']); ?>" 
                                 alt="Código de barras de <?php echo htmlspecialchars($asesor['nombre']); ?>" 
                                 class="barcode-img">
                            <p><?php echo htmlspecialchars($asesor['cod_barra']); ?></p>
                        </td>
                        <td>
                            <a href="editar_asesor.php?id_asesor=<?php echo htmlspecialchars($asesor['id_asesor']); ?>" class="btn btn-sm btn-primary">Editar</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este asesor?');">
                                <input type="hidden" name="id_asesor" value="<?php echo htmlspecialchars($asesor['id_asesor']); ?>">
                                <button type="submit" name="eliminar_asesor" class="btn btn-sm btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="crear_asesor.php" class="btn btn-success">Crear Nuevo Asesor</a>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>