<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../frontend/HTML/cliente/registro.php');
    exit;
}

$nombres = trim($_POST['nombre'] ?? '');
$apellidoPaterno = trim($_POST['apellidoPaterno'] ?? '');
$apellidoMaterno = trim($_POST['apellidoMaterno'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$password = $_POST['password'] ?? '';

if ($nombres === '' || $apellidoPaterno === '' || $correo === '' || $telefono === '' || $password === '') {
    $_SESSION['error_registro'] = 'Completa todos los campos requeridos.';
    header('Location: ../../frontend/HTML/cliente/registro.php');
    exit;
}

try {
    $conexion = Conexion::conectar();
    $stmt = $conexion->prepare('INSERT INTO clientes (nombres, apellido_paterno, apellido_materno, correo, telefono, password) VALUES (:nombres, :ap, :am, :correo, :telefono, :password)');
    $stmt->execute([
        ':nombres' => $nombres,
        ':ap' => $apellidoPaterno,
        ':am' => $apellidoMaterno,
        ':correo' => $correo,
        ':telefono' => $telefono,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    $_SESSION['registro_ok'] = 'Cuenta creada correctamente. Inicia sesión.';
    header('Location: ../../frontend/HTML/cliente/inicio_de_sesion.php');
    exit;
} catch (Throwable $e) {
    $_SESSION['error_registro'] = 'No fue posible crear la cuenta. Verifica si el correo ya existe.';
    header('Location: ../../frontend/HTML/cliente/registro.php');
    exit;
}
