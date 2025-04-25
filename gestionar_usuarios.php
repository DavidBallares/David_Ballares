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
$query = "SELECT u.*, e.nombre_empresa 
          FROM usuarios u 
          JOIN empresas e ON u.id_empresa = e.id_empresa 
          WHERE u.id_rol = 3 AND u.id_empresa = ? 
          AND (u.documento LIKE ? OR u.nombres LIKE ? OR e.nombre_empresa LIKE ?)";
$searchTerm = "%$search%";
$stmt = $pdo->prepare($query);
$stmt->execute([$id_empresa, $searchTerm, $searchTerm, $searchTerm]);
$usuarios = $stmt->fetchAll();

// Procesar eliminación de usuarios
if (isset($_POST['eliminar_usuario'])) {
    $documento_usuario = $_POST['documento'];
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE documento = ? AND id_rol = 3 AND id_empresa = ?");
    $stmt->execute([$documento_usuario, $id_empresa]);
    header("Location: gestionar_usuarios.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - Sistema de Asesorías de Estilo</title>
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
        <h2>Gestionar Usuarios</h2>
        <!-- Formulario de búsqueda -->
        <form method="GET" class="mb-3">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Buscar por documento, nombre o empresa" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </form>
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>Documento</th>
                    <th>Nombres</th>
                    <th>Empresa</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($usuario['documento']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['nombres']); ?></td>
                        <td><?php echo htmlspecialchars($usuario['nombre_empresa']); ?></td>
                        <td>
                            <a href="editar_usuario.php?documento=<?php echo htmlspecialchars($usuario['documento']); ?>" class="btn btn-sm btn-primary">Editar</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
                                <input type="hidden" name="documento" value="<?php echo htmlspecialchars($usuario['documento']); ?>">
                                <button type="submit" name="eliminar_usuario" class="btn btn-sm btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="crear_usuario.php" class="btn btn-success">Crear Nuevo Usuario</a>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>