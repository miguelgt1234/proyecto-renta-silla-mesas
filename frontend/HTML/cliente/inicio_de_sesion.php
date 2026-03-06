<?php
require_once __DIR__ . '/auth.php';

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    cerrar_sesion_cliente();
    session_start();
}

$redirect = normalizar_redirect($_GET['redirect'] ?? url_cliente('catalogo.php'));

if (usuario_autenticado()) {
    header('Location: ' . $redirect);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = normalizar_redirect($_POST['redirect'] ?? url_cliente('catalogo.php'));

    if ($correo !== '' && $password !== '') {
        $conexion = obtener_conexion_app();
        $sql = 'SELECT * FROM clientes WHERE correo = :correo LIMIT 1';
        $stmt = $conexion->prepare($sql);
        $stmt->bindValue(':correo', $correo);
        $stmt->execute();
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        $passwordValido = false;
        if ($cliente) {
            $passwordValido = password_verify($password, $cliente['password']) || $password === $cliente['password'];
        }

        if ($passwordValido) {
            $_SESSION['cliente'] = [
                'id_cliente' => (int) $cliente['id_cliente'],
                'nombres' => $cliente['nombres'],
                'correo' => $cliente['correo']
            ];
            header('Location: ' . $redirect);
            exit;
        }
    }

    $error = 'credenciales inválidas';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="<?= htmlspecialchars(url_cliente('registro.css')) ?>">
    <title>Iniciar sesión</title>
</head>
<body>
    <main id="tarjeta-registro">
        <header>
            <h1>Iniciar sesión</h1>
        </header>

        <?php if ($error): ?>
            <p><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

            <div class="input-group">
                <label for="correo">Correo</label>
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

            <button type="submit" class="btn-registro">Ingresar</button>
            <p class="login-link">¿aún no tienes cuenta? <a href="<?= htmlspecialchars(url_cliente('registro.php')) ?>">crear cuenta</a></p>
        </form>
    </main>
</body>
</html>
