<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../frontend/HTML/cliente/inicio_de_sesion.php');
    exit;
}

$correo = trim($_POST['correo'] ?? '');
$password = trim($_POST['contrasena'] ?? '');

try {
    $conexion = Conexion::conectar();
    $stmt = $conexion->prepare('SELECT * FROM clientes WHERE correo = :correo LIMIT 1');
    $stmt->bindValue(':correo', $correo);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente || !password_verify($password, $cliente['password'])) {
        $_SESSION['error_login'] = 'Correo o contraseña inválidos.';
        header('Location: ../../frontend/HTML/cliente/inicio_de_sesion.php');
        exit;
    }

    $_SESSION['cliente'] = [
        'id_cliente' => (int) $cliente['id_cliente'],
        'nombres' => $cliente['nombres'],
        'correo' => $cliente['correo'],
        'telefono' => $cliente['telefono']
    ];

    header('Location: ../../backend/controllers/catalago.php');
    exit;
} catch (Throwable $e) {
    $_SESSION['error_login'] = 'No fue posible iniciar sesión.';
    header('Location: ../../frontend/HTML/cliente/inicio_de_sesion.php');
    exit;
}
