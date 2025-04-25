<?php
session_start();

// Verificar que el usuario sea Superadmin
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

// Obtener todas las empresas con LEFT JOIN para incluir empresas sin licencia
$stmt = $pdo->query("SELECT e.*, l.fecha_inicial, l.fecha_final, l.id_estado 
                     FROM empresas e 
                     LEFT JOIN licencia l ON e.id_empresa = l.id_empresa");
$empresas = $stmt->fetchAll();

// Obtener todos los Admins
$stmt = $pdo->query("SELECT u.*, e.nombre_empresa 
                     FROM usuarios u 
                     JOIN empresas e ON u.id_empresa = e.id_empresa 
                     WHERE u.id_rol = 2");
$admins = $stmt->fetchAll();

// Procesar eliminación de empresas
if (isset($_POST['eliminar_empresa'])) {
    $id_empresa = $_POST['id_empresa'];
    $stmt = $pdo->prepare("DELETE FROM empresas WHERE id_empresa = ?");
    $stmt->execute([$id_empresa]);
    // También eliminar la licencia asociada
    $stmt = $pdo->prepare("DELETE FROM licencia WHERE id_empresa = ?");
    $stmt->execute([$id_empresa]);
    header("Location: superadmin.php");
    exit();
}

// Procesar eliminación de Admins
if (isset($_POST['eliminar_admin'])) {
    $documento = $_POST['documento'];
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE documento = ? AND id_rol = 2");
    $stmt->execute([$documento]);
    header("Location: superadmin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Superadmin - Sistema de Asesorías de Estilo</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilo.css" rel="stylesheet">
    <style>
        /* Ajustar el diseño de la tabla para que sea responsiva */
        .table-responsive {
            overflow-x: auto;
        }
        .table th, .table td {
            white-space: nowrap; /* Evitar que el texto se divida en varias líneas */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">Sistema de Asesorías</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="perfil.php">Mi Perfil</a>
                <a class="nav-link" href="logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h2>Panel de Superadmin</h2>

        <!-- Sección de Empresas -->
        <h3>Gestión de Empresas</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID Empresa</th>
                        <th>Nombre</th>
                        <th>Fecha Inicio Licencia</th>
                        <th>Fecha Fin Licencia</th>
                        <th>Estado Licencia</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($empresas as $empresa): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($empresa['id_empresa']); ?></td>
                            <td><?php echo htmlspecialchars($empresa['nombre_empresa']); ?></td>
                            <td>
                                <?php 
                                echo $empresa['fecha_inicial'] 
                                    ? htmlspecialchars(date('Y-m-d', strtotime($empresa['fecha_inicial']))) 
                                    : 'Sin licencia'; 
                                ?>
                            </td>
                            <td>
                                <?php 
                                echo $empresa['fecha_final'] 
                                    ? htmlspecialchars(date('Y-m-d', strtotime($empresa['fecha_final']))) 
                                    : 'Sin licencia'; 
                                ?>
                            </td>
                            <td>
                                <?php 
                                echo isset($empresa['id_estado']) 
                                    ? ($empresa['id_estado'] == 1 ? 'Vigente' : 'Vencida') 
                                    : 'Sin licencia'; 
                                ?>
                            </td>
                            <td>
                                <a href="editar_empresa.php?id_empresa=<?php echo htmlspecialchars($empresa['id_empresa']); ?>" class="btn btn-sm btn-primary">Editar</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta empresa? Esto eliminará todos los datos asociados.');">
                                    <input type="hidden" name="id_empresa" value="<?php echo htmlspecialchars($empresa['id_empresa']); ?>">
                                    <button type="submit" name="eliminar_empresa" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="crear_empresa.php" class="btn btn-success mb-3">Crear Nueva Empresa</a>

        <!-- Sección de Admins -->
        <h3>Gestión de Admins</h3>
        <div class="table-responsive">
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
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($admin['documento']); ?></td>
                            <td><?php echo htmlspecialchars($admin['nombres']); ?></td>
                            <td><?php echo htmlspecialchars($admin['nombre_empresa']); ?></td>
                            <td>
                                <a href="editar_admin.php?documento=<?php echo htmlspecialchars($admin['documento']); ?>" class="btn btn-sm btn-primary">Editar</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este Admin?');">
                                    <input type="hidden" name="documento" value="<?php echo htmlspecialchars($admin['documento']); ?>">
                                    <button type="submit" name="eliminar_admin" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="crear_admin.php" class="btn btn-success mb-3">Crear Nuevo Admin</a>

        <!-- Enlace a Estadísticas -->
        <div class="mt-4">
            <a href="estadisticas.php" class="btn btn-primary">Ver Estadísticas</a>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>