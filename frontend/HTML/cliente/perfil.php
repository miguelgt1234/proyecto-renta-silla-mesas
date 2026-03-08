<?php
require_once __DIR__ . '/auth.php';

requerir_autenticacion();

$cliente = obtener_cliente_autenticado();
$conexion = obtener_conexion_app();

$stmt = $conexion->prepare(
    'SELECT nombres, apellido_paterno, apellido_materno, correo, telefono, fecha_registro
     FROM clientes WHERE id_cliente = :id'
);
$stmt->bindValue(':id', (int) $cliente['id_cliente'], PDO::PARAM_INT);
$stmt->execute();
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

$nombreCompleto = trim(
    ($datos['nombres'] ?? '') . ' ' .
    ($datos['apellido_paterno'] ?? '') . ' ' .
    ($datos['apellido_materno'] ?? '')
);

$iniciales = '';
foreach (explode(' ', $nombreCompleto) as $palabra) {
    if ($palabra !== '') $iniciales .= strtoupper($palabra[0]);
    if (strlen($iniciales) >= 2) break;
}

$fechaRegistro = isset($datos['fecha_registro'])
    ? date('d/m/Y', strtotime($datos['fecha_registro']))
    : '—';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="perfil.css">
    <title>Perfil</title>
</head>

<body>

<div id="contenedorPerfil">

    <div id="headerPerfil">
        <h1>Mi perfil</h1>
        <button onclick="location.href='../../../backend/controllers/catalago.php'">Catálogo</button>
        <button onclick="location.href='carrito.php'">Carrito</button>
        <button onclick="location.href='pedido.php'">Mis pedidos</button>
        <button disabled>Perfil</button>
    </div>

    <div id="datosPerfil">

        <div class="avatarPerfil"><?= htmlspecialchars($iniciales) ?></div>

        <h2 style="color:#4e342e; margin: 0 0 5px 0; font-size:1.6rem;">
            <?= htmlspecialchars($nombreCompleto) ?>
        </h2>
        <p style="color:#8d6e63; margin:0; font-size:0.9rem;">Cliente desde <?= $fechaRegistro ?></p>

        <div class="gridDatos">

            <div class="datoCard">
                <div class="labelDato">Nombres</div>
                <div class="valorDato"><?= htmlspecialchars($datos['nombres'] ?? '—') ?></div>
            </div>

            <div class="datoCard">
                <div class="labelDato">Apellido paterno</div>
                <div class="valorDato"><?= htmlspecialchars($datos['apellido_paterno'] ?? '—') ?></div>
            </div>

            <div class="datoCard">
                <div class="labelDato">Apellido materno</div>
                <div class="valorDato"><?= htmlspecialchars($datos['apellido_materno'] ?? '—') ?></div>
            </div>

            <div class="datoCard">
                <div class="labelDato">Correo electrónico</div>
                <div class="valorDato"><?= htmlspecialchars($datos['correo'] ?? '—') ?></div>
            </div>

            <div class="datoCard">
                <div class="labelDato">Teléfono</div>
                <div class="valorDato"><?= htmlspecialchars($datos['telefono'] ?? '—') ?></div>
            </div>

            <div class="datoCard">
                <div class="labelDato">Fecha de registro</div>
                <div class="valorDato"><?= $fechaRegistro ?></div>
            </div>

        </div>
    </div>

    <div id="accionesPerfil">
        <button onclick="location.href='cerrar_sesion.php'">Cerrar sesión</button>
    </div>

</div>

</body>
</html>