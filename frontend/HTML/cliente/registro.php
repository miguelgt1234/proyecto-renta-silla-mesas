<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="registro.css">
    <title>Registro</title>
</head>
<body>
<header><h1>Registro de Usuario</h1></header>
<main id="tarjeta-registro">
    <header>
        <h1>Registro de Cliente</h1>
    </header>
    <?php if (!empty($_SESSION['error_registro'])): ?>
        <p><?= htmlspecialchars($_SESSION['error_registro']); unset($_SESSION['error_registro']); ?></p>
    <?php endif; ?>

    <form method="post" action="../../../backend/controllers/registro_cliente.php">
        <div class="row">
            <div class="input-group">
                <label for="nombre">Nombres</label>
                <div class="input-wrapper"><input type="text" name="nombre" id="nombre" required></div>
            </div>
            <div class="input-group">
                <label for="apellidoPaterno">Apellido Paterno</label>
                <div class="input-wrapper"><input type="text" name="apellidoPaterno" id="apellidoPaterno" required></div>
            </div>
        </div>
        <div class="row">
            <div class="input-group">
                <label for="apellidoMaterno">Apellido Materno</label>
                <div class="input-wrapper"><input type="text" name="apellidoMaterno" id="apellidoMaterno"></div>
            </div>
            <div class="input-group">
                <label for="telefono">Teléfono</label>
                <div class="input-wrapper"><input type="tel" name="telefono" id="telefono" required></div>
            </div>
        </div>
        <div class="row">
            <div class="input-group">
                <label for="correo">Correo Electrónico</label>
                <div class="input-wrapper"><input type="email" name="correo" id="correo" required></div>
            </div>
            <div class="input-group">
                <label for="password">Contraseña</label>
                <div class="input-wrapper"><input type="password" name="password" id="password" required></div>
            </div>
        </div>
        <button type="submit" class="btn-registro">Registrarse</button>
        <p class="login-link">¿Ya tienes cuenta? <a href="inicio_de_sesion.php">Inicia sesión</a></p>
    </form>
</main>
</body>
</html>
