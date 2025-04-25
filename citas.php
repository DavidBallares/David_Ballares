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
$fecha = $_GET['fecha'] ?? '';
$query = "SELECT c.*, u.nombres AS nombre_usuario, a.nombre AS nombre_asesor 
          FROM citas c 
          JOIN usuarios u ON c.documento_usuario = u.documento 
          JOIN asesores a ON c.asesor_id = a.id_asesor 
          WHERE u.id_empresa = ?";
$params = [$id_empresa];

if ($search) {
    $query .= " AND (c.nombre LIKE ? OR u.nombres LIKE ? OR a.nombre LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($fecha) {
    $query .= " AND c.fecha = ?";
    $params[] = $fecha;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$citas = $stmt->fetchAll();

// Procesar eliminación de citas
if (isset($_POST['eliminar_cita'])) {
    $id_cita = $_POST['id_cita'];
    $stmt = $pdo->prepare("DELETE FROM citas c 
                           USING citas c 
                           JOIN usuarios u ON c.documento_usuario = u.documento 
                           WHERE c.id_cita = ? AND u.id_empresa = ?");
    $stmt->execute([$id_cita, $id_empresa]);
    header("Location: citas.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citas - Sistema de Asesorías de Estilo</title>
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
        <h2>Lista de Citas</h2>
        <!-- Formulario de búsqueda -->
        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Buscar por nombre de cita, usuario o asesor" value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </div>
                </div>
                <div class="col-md-6 mb-2">
                    <input type="date" class="form-control" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>">
                </div>
            </div>
        </form>
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nombre de la Cita</th>
                    <th>Usuario</th>
                    <th>Asesor</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($citas as $cita): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cita['id_cita']); ?></td>
                        <td><?php echo htmlspecialchars($cita['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($cita['nombre_usuario']); ?></td>
                        <td><?php echo htmlspecialchars($cita['nombre_asesor']); ?></td>
                        <td><?php echo htmlspecialchars($cita['fecha']); ?></td>
                        <td><?php echo htmlspecialchars($cita['hora']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta cita?');">
                                <input type="hidden" name="id_cita" value="<?php echo htmlspecialchars($cita['id_cita']); ?>">
                                <button type="submit" name="eliminar_cita" class="btn btn-sm btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>