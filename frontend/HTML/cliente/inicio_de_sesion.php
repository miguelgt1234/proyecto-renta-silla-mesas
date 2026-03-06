<?php
require_once __DIR__ . '/auth.php';

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    cerrar_sesion_cliente();
    session_start();
}

if (usuario_autenticado()) {
    $destino = $_GET['redirect'] ?? 'catalogo.php';
    header('Location: ' . $destino);
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? 'catalogo.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? 'catalogo.php';

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
    <link rel="stylesheet" href="registro.css">
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
        <?php if (!empty($_SESSION['registro_ok'])): ?>
            <p><?= htmlspecialchars($_SESSION['registro_ok']); unset($_SESSION['registro_ok']); ?></p>
        <?php endif; ?>
        <form method="post" action="../../../backend/controllers/login_cliente.php">
            <?php if (!empty($_GET['return'])): ?>
                <input type="hidden" name="return" value="<?= htmlspecialchars($_GET['return']) ?>">
            <?php endif; ?>
            <input type="email" id="txt_correo" name="correo" placeholder="Correo" required>
            <input type="password" id="txt_contrasena" name="contrasena" placeholder="Contraseña" required>
            <button type="submit" id="btn_ingresar">INGRESAR</button>
        </form>
    </main>
</body>
</html>
