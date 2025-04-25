<?php
session_start();

// Verificar que el usuario sea Superadmin o Admin
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Superadmin', 'Admin'])) {
    header("Location: index.php");
    exit();
}

// Conexión a la base de datos
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

// Obtener el id_empresa del usuario actual (para Admin)
$id_empresa = null;
if ($_SESSION['rol'] === 'Admin') {
    $documento = $_SESSION['documento'];
    $stmt = $pdo->prepare("SELECT id_empresa FROM usuarios WHERE documento = ?");
    $stmt->execute([$documento]);
    $usuario = $stmt->fetch();
    $id_empresa = $usuario['id_empresa'];
}

// 1. Total de citas por asesor
$query_citas_asesor = "SELECT a.nombre AS nombre_asesor, COUNT(c.id_cita) AS total_citas 
                       FROM asesores a 
                       LEFT JOIN citas c ON a.id_asesor = c.asesor_id";
if ($_SESSION['rol'] === 'Admin') {
    $query_citas_asesor .= " WHERE a.id_empresa = ? 
                             GROUP BY a.id_asesor, a.nombre";
    $stmt_citas_asesor = $pdo->prepare($query_citas_asesor);
    $stmt_citas_asesor->execute([$id_empresa]);
} else {
    $query_citas_asesor .= " GROUP BY a.id_asesor, a.nombre";
    $stmt_citas_asesor = $pdo->query($query_citas_asesor);
}
$citas_por_asesor = $stmt_citas_asesor->fetchAll();

// 2. Total de citas por empresa (solo para Superadmin)
$citas_por_empresa = [];
if ($_SESSION['rol'] === 'Superadmin') {
    $stmt_citas_empresa = $pdo->query("SELECT e.nombre_empresa, COUNT(c.id_cita) AS total_citas 
                                       FROM empresas e 
                                       LEFT JOIN usuarios u ON e.id_empresa = u.id_empresa 
                                       LEFT JOIN citas c ON u.documento = c.documento_usuario 
                                       GROUP BY e.id_empresa, e.nombre_empresa");
    $citas_por_empresa = $stmt_citas_empresa->fetchAll();
}

// 3. Citas próximas (en los próximos 7 días)
$fecha_actual = date('Y-m-d');
$fecha_limite = date('Y-m-d', strtotime('+7 days'));
$query_citas_proximas = "SELECT c.*, u.nombres AS nombre_usuario, a.nombre AS nombre_asesor 
                         FROM citas c 
                         JOIN usuarios u ON c.documento_usuario = u.documento 
                         JOIN asesores a ON c.asesor_id = a.id_asesor 
                         WHERE c.fecha BETWEEN ? AND ?";
if ($_SESSION['rol'] === 'Admin') {
    $query_citas_proximas .= " AND u.id_empresa = ?";
    $stmt_citas_proximas = $pdo->prepare($query_citas_proximas);
    $stmt_citas_proximas->execute([$fecha_actual, $fecha_limite, $id_empresa]);
} else {
    $stmt_citas_proximas = $pdo->prepare($query_citas_proximas);
    $stmt_citas_proximas->execute([$fecha_actual, $fecha_limite]);
}
$citas_proximas = $stmt_citas_proximas->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Sistema de Asesorías de Estilo</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilo.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">Sistema de Asesorías</a>
            <div class="navbar-nav ms-auto">
                <?php if ($_SESSION['rol'] === 'Superadmin'): ?>
                    <a class="nav-link" href="superadmin.php">Panel Superadmin</a>
                <?php else: ?>
                    <a class="nav-link" href="admin.php">Panel Admin</a>
                <?php endif; ?>
                <a class="nav-link" href="logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h2>Estadísticas del Sistema</h2>

        <!-- 1. Total de citas por asesor -->
        <div class="mt-4">
            <h3>Total de Citas por Asesor</h3>
            <?php if (empty($citas_por_asesor)): ?>
                <p>No hay citas registradas.</p>
            <?php else: ?>
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Asesor</th>
                            <th>Total de Citas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($citas_por_asesor as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nombre_asesor']); ?></td>
                                <td><?php echo htmlspecialchars($row['total_citas']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- 2. Total de citas por empresa (solo para Superadmin) -->
        <?php if ($_SESSION['rol'] === 'Superadmin'): ?>
            <div class="mt-4">
                <h3>Total de Citas por Empresa</h3>
                <?php if (empty($citas_por_empresa)): ?>
                    <p>No hay citas registradas.</p>
                <?php else: ?>
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Empresa</th>
                                <th>Total de Citas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($citas_por_empresa as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['nombre_empresa']); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_citas']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- 3. Citas próximas -->
        <div class="mt-4">
            <h3>Citas Próximas (Próximos 7 Días)</h3>
            <?php if (empty($citas_proximas)): ?>
                <p>No hay citas programadas para los próximos 7 días.</p>
            <?php else: ?>
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre de la Cita</th>
                            <th>Usuario</th>
                            <th>Asesor</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($citas_proximas as $cita): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cita['id_cita']); ?></td>
                                <td><?php echo htmlspecialchars($cita['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($cita['nombre_usuario']); ?></td>
                                <td><?php echo htmlspecialchars($cita['nombre_asesor']); ?></td>
                                <td><?php echo htmlspecialchars($cita['fecha']); ?></td>
                                <td><?php echo htmlspecialchars($cita['hora']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="mt-4">
            <?php if ($_SESSION['rol'] === 'Superadmin'): ?>
                <a href="superadmin.php" class="btn btn-primary">Volver al Panel Superadmin</a>
            <?php else: ?>
                <a href="admin.php" class="btn btn-primary">Volver al Panel Admin</a>
            <?php endif; ?>
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>