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


$documento = $_SESSION['documento'];
$stmt = $pdo->prepare("SELECT id_empresa FROM usuarios WHERE documento = ?");
$stmt->execute([$documento]);
$admin = $stmt->fetch();
$id_empresa = $admin['id_empresa'];

$stmt = $pdo->prepare("SELECT l.id_estado, l.fecha_final 
                       FROM licencia l 
                       WHERE l.id_empresa = ?");
$stmt->execute([$id_empresa]);
$licencia = $stmt->fetch();
$licencia_vencida = $licencia && ($licencia['id_estado'] == 2 || strtotime($licencia['fecha_final']) < time());

// Obtener citas próximas (dentro de las próximas 24 horas) para la empresa
$currentDateTime = date('Y-m-d H:i:s');
$tomorrowDateTime = date('Y-m-d H:i:s', strtotime('+1 day'));
$stmt = $pdo->prepare("SELECT c.*, u.nombres AS nombre_usuario, a.nombre AS nombre_asesor 
                       FROM citas c 
                       JOIN usuarios u ON c.documento_usuario = u.documento 
                       JOIN asesores a ON c.asesor_id = a.id_asesor 
                       WHERE u.id_empresa = ? 
                       AND CONCAT(c.fecha, ' ', c.hora) BETWEEN ? AND ?");
$stmt->execute([$id_empresa, $currentDateTime, $tomorrowDateTime]);
$notificaciones = $stmt->fetchAll();

// Obtener asesores de la empresa del Admin
$stmt = $pdo->prepare("SELECT a.id_asesor, a.nombre, a.especialidad, a.cod_barra 
                       FROM asesores a 
                       WHERE a.id_empresa = ?");
$stmt->execute([$id_empresa]);
$asesores = $stmt->fetchAll();

// Procesar eliminación de asesores
if (isset($_POST['eliminar_asesor'])) {
    $id_asesor = $_POST['id_asesor'];
    // Eliminar la imagen del código de barras
    $barcodePath = "barcodes/asesor_$id_asesor.png";
    if (file_exists($barcodePath)) {
        unlink($barcodePath);
    }
    // Eliminar el asesor
    $stmt = $pdo->prepare("DELETE FROM asesores WHERE id_asesor = ?");
    $stmt->execute([$id_asesor]);
    header("Location: admin.php?mensaje=Asesor eliminado exitosamente");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Admin - Sistema de Asesorías de Estilo</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilo.css" rel="stylesheet">
    <style>
        .table-responsive {
            overflow-x: auto;
        }
        .table th, .table td {
            white-space: nowrap;
        }
        .barcode-img {
            max-height: 50px;
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
        <h2>Panel de Administrador</h2>

        <!-- Mensaje de éxito o error -->
        <?php if (isset($_GET['mensaje'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['mensaje']); ?></div>
        <?php endif; ?>

        <!-- Notificaciones -->
        <?php if (count($notificaciones) > 0): ?>
            <div class="alert alert-info" role="alert">
                <h4 class="alert-heading">Citas Próximas</h4>
                <ul>
                    <?php foreach ($notificaciones as $notificacion): ?>
                        <li>Cita de <?php echo htmlspecialchars($notificacion['nombre_usuario']); ?> 
                            con <?php echo htmlspecialchars($notificacion['nombre_asesor']); ?> 
                            el <?php echo htmlspecialchars($notificacion['fecha']); ?> 
                            a las <?php echo htmlspecialchars($notificacion['hora']); ?>.</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($licencia_vencida): ?>
            <div class="alert alert-danger" role="alert">
                Tu licencia está vencida. Contacta al Superadmin para renovarla.
            </div>
        <?php else: ?>
            <!-- Sección de Asesores -->
            <h3>Gestión de Asesores</h3>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID Asesor</th>
                            <th>Nombre</th>
                            <th>Especialidad</th>
                            <th>Código de Barra</th>
                            <th>Imagen</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asesores as $asesor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($asesor['id_asesor']); ?></td>
                                <td><?php echo htmlspecialchars($asesor['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($asesor['especialidad']); ?></td>
                                <td><?php echo htmlspecialchars($asesor['cod_barra']); ?></td>
                                <td>
                                    <img src="barcodes/asesor_<?php echo htmlspecialchars($asesor['id_asesor']); ?>.png" alt="Código de barras" class="barcode-img">
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
            </div>
            <a href="crear_asesor.php" class="btn btn-success mb-3">Crear Nuevo Asesor</a>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title">Gestionar Usuarios</h5>
                            <p class="card-text">Administra los usuarios de tu empresa.</p>
                            <a href="gestionar_usuarios.php" class="btn btn-primary">Ir a Usuarios</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="card-title">Estadísticas</h5>
                            <p class="card-text">Consulta estadísticas de tu empresa.</p>
                            <a href="estadisticas.php" class="btn btn-primary">Ver Estadísticas</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>