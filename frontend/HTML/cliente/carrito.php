<?php
session_start();
if (!isset($_SESSION['cliente'])) {
    header('Location: inicio_de_sesion.php');
    exit;
}
$carrito = $_SESSION['carrito'] ?? [];
$total = 0;
foreach ($carrito as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="carrito.css">
    <title>Carrito</title>
</head>
<body>
<div id="contenedorCarrito">
    <div id="headerCarrito">
        <h1>Carrito</h1>
        <button onclick="location.href='../../../backend/controllers/catalago.php'">Catálogo</button>
        <button disabled>Carrito</button>
        <button onclick="location.href='pedido.php'">Mis pedidos</button>
        <button onclick="location.href='perfil.html'">Perfil</button>
    </div>

    <div id="listaCarrito">
        <?php if (empty($carrito)): ?>
            <p>No hay productos en tu carrito.</p>
        <?php else: ?>
            <?php foreach ($carrito as $item): ?>
                <div class="itemCarrito" data-id="<?= (int) $item['id_producto'] ?>">
                    <p><strong><?= htmlspecialchars($item['nombre']) ?></strong></p>
                    <p>Cantidad: <?= (int) $item['cantidad'] ?></p>
                    <p>Precio unitario: $<?= number_format($item['precio'], 2) ?></p>
                    <button onclick="modificar(<?= (int)$item['id_producto'] ?>, 'disminuir')">- Disminuir</button>
                    <button onclick="modificar(<?= (int)$item['id_producto'] ?>, 'eliminar')">Eliminar</button>
                </div>
            <?php endforeach; ?>
            <p><strong>Total estimado por día: $<?= number_format($total, 2) ?></strong></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($carrito)): ?>
    <div id="accionesCarrito">
        <button onclick="location.href='confirmar_pedido.php'">Confirmar pedido</button>
    </div>
    <?php endif; ?>
</div>

<script>
function modificar(id, accion) {
    const formData = new URLSearchParams();
    formData.append('accion', accion);
    formData.append('id_producto', id);
    if (accion === 'actualizar') formData.append('cantidad', '1');

    fetch('../../../backend/controllers/carrito_acciones.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData.toString()
    }).then(() => location.reload());
}
</script>
</body>
</html>
