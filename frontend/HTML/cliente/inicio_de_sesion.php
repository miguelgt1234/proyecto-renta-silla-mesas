<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../administrador/inicio_de_sesion.css">
    <title>Iniciar sesión</title>
</head>
<body>
<main>
    <section id="lado-izquierdo"></section>
    <section id="lado-derecho">
        <div class="logo-icon"></div>
        <h1>INICIAR SESIÓN</h1>
        <?php if (!empty($_SESSION['error_login'])): ?>
            <p><?= htmlspecialchars($_SESSION['error_login']); unset($_SESSION['error_login']); ?></p>
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
        <p>¿No tienes cuenta? <a href="registro.php">Crear cuenta</a></p>
    </section>
</main>
</body>
</html>
