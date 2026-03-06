<?php
require_once __DIR__ . '/auth.php';

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidoPaterno = trim($_POST['apellido_paterno'] ?? '');
    $apellidoMaterno = trim($_POST['apellido_materno'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nombres && $apellidoPaterno && $correo && $telefono && $password) {
        $conexion = obtener_conexion_app();
        $valida = $conexion->prepare('SELECT id_cliente FROM clientes WHERE correo = :correo LIMIT 1');
        $valida->bindValue(':correo', $correo);
        $valida->execute();

        if ($valida->fetch()) {
            $error = 'ya existe una cuenta con ese correo';
        } else {
            $sql = 'INSERT INTO clientes (nombres, apellido_paterno, apellido_materno, correo, telefono, password)
                    VALUES (:nombres, :apellido_paterno, :apellido_materno, :correo, :telefono, :password)';
            $stmt = $conexion->prepare($sql);
            $stmt->bindValue(':nombres', $nombres);
            $stmt->bindValue(':apellido_paterno', $apellidoPaterno);
            $stmt->bindValue(':apellido_materno', $apellidoMaterno ?: null);
            $stmt->bindValue(':correo', $correo);
            $stmt->bindValue(':telefono', $telefono);
            $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT));
            $stmt->execute();

            $mensaje = 'cuenta creada correctamente, ahora inicia sesión';
        }
    } else {
        $error = 'completa todos los campos obligatorios';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="<?= htmlspecialchars(url_cliente('registro.css')) ?>">
    <title>Registro</title>
</head>
<body>

<header>
    <h1>Registro de Usuario</h1>
</header>

<main id="tarjeta-registro">
    <header>
        <h1>Registro de Cliente</h1>
    </header>

    <?php if ($mensaje): ?>
        <p><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <div class="row">
            <div class="input-group">
                <label for="nombres">Nombres</label>
                <div class="input-wrapper">
                    <input type="text" name="nombres" id="nombres" required>
                </div>
            </div>
            <div class="input-group">
                <label for="apellido_paterno">Apellido Paterno</label>
                <div class="input-wrapper">
                    <input type="text" name="apellido_paterno" id="apellido_paterno" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="input-group">
                <label for="apellido_materno">Apellido Materno</label>
                <div class="input-wrapper">
                    <input type="text" name="apellido_materno" id="apellido_materno">
                </div>
            </div>
            <div class="input-group">
                <label for="telefono">Teléfono</label>
                <div class="input-wrapper">
                    <input type="tel" name="telefono" id="telefono" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="input-group">
                <label for="correo">Correo Electrónico</label>
                <div class="input-wrapper">
                    <input type="email" name="correo" id="correo" required>
                </div>
            </div>
            <div class="input-group">
                <label for="password">Contraseña</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="password" required>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-registro">Registrarse</button>
        <p class="login-link">¿ya tienes cuenta? <a href="<?= htmlspecialchars(url_cliente('inicio_de_sesion.php')) ?>">inicia sesión</a></p>
    </form>
</main>

</body>
</html>
