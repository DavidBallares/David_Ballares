<?php
// Conexión a la base de datos
$host = '127.0.0.1';
$db = 'estilo_personal_hombres';
$user = 'root'; // Cambia según tu configuración
$pass = ''; // Cambia según tu configuración
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

// Eliminar datos actuales
$pdo->exec("DELETE FROM citas");
$pdo->exec("DELETE FROM asesores");
$pdo->exec("DELETE FROM usuarios");
$pdo->exec("DELETE FROM rol");
$pdo->exec("DELETE FROM estado");
$pdo->exec("DELETE FROM licencia");
$pdo->exec("DELETE FROM empresa");
$pdo->exec("DELETE FROM tipo_licencia");

// Generar el hash para la clave "password123"
$clave = "password123";
$hash = password_hash($clave, PASSWORD_DEFAULT);
echo "Hash generado para 'password123': " . $hash . "\n";

// Insertar tipos de licencia
$pdo->exec("INSERT INTO tipo_licencia (id_tipo_licencia, nombre_tipo_licencia, costo) VALUES
(1, 'Demo (3 días)', 0),
(2, '6 Meses', 50000),
(3, '1 Año', 90000),
(4, '2 Años', 150000)");

// Insertar empresas
$pdo->exec("INSERT INTO empresa (id_empresa, nombre_empresa) VALUES
(1, 'EstiloPersonalSA'),
(2, 'ClienteModaXYZ')");

// Función para generar un id_licencia único de 10 dígitos
function generarIdLicencia($pdo) {
    do {
        $id_licencia = mt_rand(1000000000, 9999999999);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM licencia WHERE id_licencia = ?");
        $stmt->execute([$id_licencia]);
        $existe = $stmt->fetchColumn();
    } while ($existe > 0);
    return $id_licencia;
}

// Insertar licencias
$licencias = [
    ['fecha_inicial' => '2025-04-23 00:00:00', 'fecha_final' => '2030-04-23 00:00:00', 'id_empresa' => 1, 'id_tipo_licencia' => 4, 'id_estado' => 1],
    ['fecha_inicial' => '2025-04-20 00:00:00', 'fecha_final' => '2025-04-23 00:00:00', 'id_empresa' => 2, 'id_tipo_licencia' => 1, 'id_estado' => 2],
];

$licencias_ids = []; // Para guardar los id_licencia generados

foreach ($licencias as $index => $licencia) {
    $id_licencia = generarIdLicencia($pdo);
    $licencias_ids[$index] = $id_licencia; // Guardar el id_licencia para usarlo después
    
    $stmt = $pdo->prepare("INSERT INTO licencia (id_licencia, fecha_inicial, fecha_final, id_empresa, id_tipo_licencia, id_estado) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $id_licencia,
        $licencia['fecha_inicial'],
        $licencia['fecha_final'],
        $licencia['id_empresa'],
        $licencia['id_tipo_licencia'],
        $licencia['id_estado']
    ]);

    // Insertar en rol
    $rol = ($index == 0) ? 'Superadmin' : 'Admin';
    $stmt = $pdo->prepare("INSERT INTO rol (id_rol, roles, id_licencia) VALUES (?, ?, ?)");
    $stmt->execute([$index + 1, $rol, $id_licencia]);

    // Insertar en estado
    $stmt = $pdo->prepare("INSERT INTO estado (id_estado, estado, id_licencia) VALUES (?, ?, ?)");
    $stmt->execute([$index + 1, $licencia['id_estado'] == 1 ? 'Vigente' : 'Vencida', $id_licencia]);
}

// Insertar usuarios con el hash correcto
$stmt = $pdo->prepare("INSERT INTO usuarios (documento, nombres, telefono, email, clave, id_empresa) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute(['1000000001', 'Superadmin User', '3001234567', 'superadmin@estilo.com', $hash, 1]);
$stmt->execute(['2000000002', 'Admin User', '3109876543', 'admin@modaxyz.com', $hash, 2]);

// Insertar asesores de ejemplo
$pdo->exec("INSERT INTO asesores (id_asesor, nombre, especialidad, cod_barra) VALUES
(1, 'Andrés Salazar', 'Perfumes', 'ASESOR-1-20250423'),
(2, 'María López', 'Moda', 'ASESOR-2-20250423')");

// Insertar citas de ejemplo
$pdo->exec("INSERT INTO citas (id_cita, nombre, documento_usuario, asesor_id, fecha, hora) VALUES
(1, 'Cita de Prueba 1', '1000000001', 1, '2025-04-24', '10:00:00'),
(2, 'Cita de Prueba 2', '2000000002', 2, '2025-04-25', '14:00:00')");

echo "Datos insertados correctamente.";
?>