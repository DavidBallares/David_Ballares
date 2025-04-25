<?php
session_start();

// Si el usuario ya está logueado, redirigir según su rol
if (isset($_SESSION['rol'])) {
    if ($_SESSION['rol'] == 'Superadmin') {
        header("Location: superadmin.php");
    } elseif ($_SESSION['rol'] == 'Admin') {
        header("Location: admin.php");
    } elseif ($_SESSION['rol'] == 'Usuario') {
        header("Location: usuario.php");
    }
    exit();
}

// Si no está logueado, redirigir a login.php
header("Location: login.php");
exit();
?>