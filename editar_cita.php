<?php
session_start();

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

// Verificar el estado de la licencia (solo para Admin)
$licencia_vencida = false;
if ($_SESSION['rol'] === 'Admin') {
    $documento = $_SESSION['documento'];
    $stmt = $pdo->prepare("SELECT id_empresa FROM usuarios WHERE documento = ?");
    $stmt->execute([$documento]);
    $usuario = $stmt->fetch();
    $id_empresa = $usuario['id_empresa'];

    $stmt = $pdo->prepare("SELECT l.id_estado, e.estado 
                           FROM licencia l 
                           JOIN estado e ON l.id_estado = e.id_estado 
                           WHERE l.id_empresa = ?");
    $stmt->execute([$id_empresa]);
    $licencia = $stmt->fetch();
    $licencia_vencida = $licencia['id_estado'] == 2;
}

// Obtener el ID de la cita
$id_cita = $_GET['id_cita'] ?? null;
if (!$id_cita) {
    header("Location: citas.php");
    exit();
}

// Obtener los datos de la cita
$stmt = $pdo->prepare("SELECT * FROM citas WHERE id_cita = ?");
$stmt->execute([$id_cita]);
$cita = $stmt->fetch();

if (!$cita) {
    header("Location: citas.php");
    exit();
}

// Obtener usuarios y asesores para los dropdowns
$usuarios = $pdo->query("SELECT documento, nombres FROM usuarios")->fetchAll();
$asesores = $pdo->query("SELECT id_asesor, nombre FROM asesores")->fetchAll();

// Procesar el formulario de edición (solo si la licencia no está vencida o si es Superadmin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!$licencia_vencida || $_SESSION['rol'] === 'Superadmin')) {
    $nombre = $_POST['nombre'] ?? '';
    $documento_usuario = $_POST['documento_usuario'] ?? '';
    $asesor_id = $_POST['asesor_id'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';

    if ($nombre && $documento_usuario && $asesor_id && $fecha && $hora) {
        // Validar que la fecha sea futura
        $fecha_actual = date('Y-m-d'); // Fecha actual: 2025-04-24
        if ($fecha < $fecha_actual) {
            $error = "La fecha de la cita debe ser posterior a hoy ($fecha_actual).";
        } else {
            // Verificar disponibilidad del asesor (excluyendo la cita actual)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM citas 
                                   WHERE asesor_id = ? 
                                   AND fecha = ? 
                                   AND hora = ? 
                                   AND id_cita != ?");
            $stmt->execute([$asesor_id, $fecha, $hora, $id_cita]);
            $citas_existentes = $stmt->fetchColumn();

            if ($citas_existentes > 0) {
                $error = "El asesor ya tiene una cita programada para el $fecha a las $hora.";
            } else {
                $stmt = $pdo->prepare("UPDATE citas 
                                       SET nombre = ?, documento_usuario = ?, asesor_id = ?, fecha = ?, hora = ? 
                                       WHERE id_cita = ?");
                $stmt->execute([$nombre, $documento_usuario, $asesor_id, $fecha, $hora, $id_cita]);
                header("Location: citas.php");
                exit();
            }
        }
    } else {
        $error = "Por favor, completa todos los campos del formulario.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cita - Sistema de Asesorías de Estilo</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/estilo.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">Sistema de Asesorías</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="citas.php">Volver a Citas</a>
                <a class="nav-link" href="logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h2>Editar Cita</h2>
        <?php if ($licencia_vencida && $_SESSION['rol'] === 'Admin'): ?>
            <div class="alert alert-warning" role="alert">
                <strong>¡Atención!</strong> Tu licencia está vencida. No puedes editar citas.
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre de la Cita</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($cita['nombre']); ?>" required 
                       <?php if ($licencia_vencida && $_SESSION['rol'] === 'Admin') echo 'disabled'; ?>>
            </div>
            <div class="mb-3">
                <label for="documento_usuario" class="form-label">Usuario</label>
                <select class="form-select" id="documento_usuario" name="documento_usuario" required 
                        <?php if ($licencia_vencida && $_SESSION['rol'] === 'Admin') echo 'disabled'; ?>>
                    <option value="">Seleccione un usuario</option>
                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?php echo htmlspecialchars($usuario['documento']); ?>" 
                                <?php if ($usuario['documento'] == $cita['documento_usuario']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($usuario['nombres']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="asesor_id" class="form-label">Asesor</label>
                <select class="form-select" id="asesor_id" name="asesor_id" required 
                        <?php if ($licencia_vencida && $_SESSION['rol'] === 'Admin') echo 'disabled'; ?>>
                    <option value="">Seleccione un asesor</option>
                    <?php foreach ($asesores as $asesor): ?>
                        <option value="<?php echo htmlspecialchars($asesor['id_asesor']); ?>" 
                                <?php if ($asesor['id_asesor'] == $cita['asesor_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($asesor['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="fecha" class="form-label">Fecha</label>
                <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo htmlspecialchars($cita['fecha']); ?>" required 
                       min="<?php echo date('Y-m-d'); ?>" 
                       <?php if ($licencia_vencida && $_SESSION['rol'] === 'Admin') echo 'disabled'; ?>>
            </div>
            <div class="mb-3">
                <label for="hora" class="form-label">Hora</label>
                <input type="time" class="form-control" id="hora" name="hora" value="<?php echo htmlspecialchars($cita['hora']); ?>" required 
                       <?php if ($licencia_vencida && $_SESSION['rol'] === 'Admin') echo 'disabled'; ?>>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary" 
                        <?php if ($licencia_vencida && $_SESSION['rol'] === 'Admin') echo 'disabled'; ?>>Guardar Cambios</button>
            </div>
        </form>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>