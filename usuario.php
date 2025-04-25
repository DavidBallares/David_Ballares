<?php
session_start();

// Verificar que el usuario sea Usuario
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Usuario') {
    header("Location: index.php");
    exit();
}

$documento_usuario = $_SESSION['documento'];

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

// Obtener citas del usuario
$stmt = $pdo->prepare("SELECT c.*, a.nombre AS nombre_asesor 
                       FROM citas c 
                       JOIN asesores a ON c.asesor_id = a.id_asesor 
                       WHERE c.documento_usuario = ?");
$stmt->execute([$documento_usuario]);
$citas = $stmt->fetchAll();

// Obtener citas próximas (dentro de las próximas 24 horas)
$currentDateTime = date('Y-m-d H:i:s');
$tomorrowDateTime = date('Y-m-d H:i:s', strtotime('+1 day'));
$stmt = $pdo->prepare("SELECT c.*, a.nombre AS nombre_asesor 
                       FROM citas c 
                       JOIN asesores a ON c.asesor_id = a.id_asesor 
                       WHERE c.documento_usuario = ? 
                       AND CONCAT(c.fecha, ' ', c.hora) BETWEEN ? AND ?");
$stmt->execute([$documento_usuario, $currentDateTime, $tomorrowDateTime]);
$notificaciones = $stmt->fetchAll();

// Obtener asesores disponibles
$stmt = $pdo->prepare("SELECT id_asesor, nombre FROM asesores WHERE id_empresa = ?");
$stmt->execute([$_SESSION['id_empresa']]);
$asesores = $stmt->fetchAll();

// Procesar creación de cita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_cita'])) {
    $nombre_cita = trim($_POST['documento'] ?? '');
    $asesor_id = $_POST['asesor_id'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';

    if (empty($nombre_cita) || empty($asesor_id) || empty($fecha) || empty($hora)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO citas (nombre, documento_usuario, asesor_id, fecha, hora) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre_cita, $documento_usuario, $asesor_id, $fecha, $hora]);
        header("Location: usuario.php?mensaje=Cita creada exitosamente");
        exit();
    }
}

// Procesar eliminación de citas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_cita'])) {
    $id_cita = $_POST['id_cita'];
    $stmt = $pdo->prepare("DELETE FROM citas WHERE id_cita = ? AND documento_usuario = ?");
    $stmt->execute([$id_cita, $documento_usuario]);
    header("Location: usuario.php?mensaje=Cita eliminada exitosamente");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Usuario - Sistema de Asesorías de Estilo</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilo.css" rel="stylesheet">
    <style>
        .table-responsive {
            overflow-x: auto;
        }
        .table th, .table td {
            white-space: nowrap;
        }
    </style>
</head>
<body onload="frm1.placa.focus()">
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
        <h2>Panel de Usuario</h2>

        <!-- Mensaje de éxito -->
        <?php if (isset($_GET['mensaje'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['mensaje']); ?></div>
        <?php endif; ?>

        <!-- Notificaciones -->
        <?php if (count($notificaciones) > 0): ?>
            <div class="alert alert-info" role="alert">
                <h4 class="alert-heading">Citas Próximas</h4>
                <ul>
                    <?php foreach ($notificaciones as $notificacion): ?>
                        <li>Tienes una cita con <?php echo htmlspecialchars($notificacion['nombre_asesor']); ?> 
                            el <?php echo htmlspecialchars($notificacion['fecha']); ?> 
                            a las <?php echo htmlspecialchars($notificacion['hora']); ?>.</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Lista de Citas -->
        <h3>Mis Citas</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nombre de la Cita</th>
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

        <!-- Formulario para crear cita -->
        <h3>Crear Nueva Cita</h3>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" name="frm1">
            <div class="mb-3">
                <label for="documento" class="form-label">Nombre de la Cita</label>
                <input type="text" class="form-control" id="documento" name="documento" required>
            </div>
            <div class="mb-3">
                <label for="asesor_id" class="form-label">Asesor</label>
                <select class="form-control" id="asesor_id" name="asesor_id" required>
                    <option value="">Seleccione un asesor</option>
                    <?php foreach ($asesores as $asesor): ?>
                        <option value="<?php echo htmlspecialchars($asesor['id_asesor']); ?>">
                            <?php echo htmlspecialchars($asesor['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="fecha" class="form-label">Fecha</label>
                <input type="date" class="form-control" id="fecha" name="fecha" required>
            </div>
            <div class="mb-3">
                <label for="hora" class="form-label">Hora</label>
                <input type="time" class="form-control" id="hora" name="hora" required>
            </div>
            <button type="submit" name="crear_cita" class="btn btn-success">Crear Cita</button>
        </form>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>