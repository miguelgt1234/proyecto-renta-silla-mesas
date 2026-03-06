<?php
require_once __DIR__ . '/auth.php';
requerir_autenticacion();

$carrito = obtener_carrito();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProducto = (int) ($_POST['id_producto'] ?? 0);
    $accion = $_POST['accion'] ?? '';

    if ($idProducto > 0 && isset($carrito[$idProducto])) {
        if ($accion === 'eliminar') {
            unset($carrito[$idProducto]);
        }

        if ($accion === 'disminuir') {
            $carrito[$idProducto] -= 1;
            if ($carrito[$idProducto] <= 0) {
                unset($carrito[$idProducto]);
            }
        }

        guardar_carrito($carrito);
    }

    header('Location: carrito.php');
    exit;
}

$detalles = [];
$total = 0;

if (!empty($carrito)) {
    $conexion = obtener_conexion_app();
    $ids = array_map('intval', array_keys($carrito));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT id_producto, nombre, precio_renta_dia FROM productos WHERE id_producto IN ($placeholders)";
    $stmt = $conexion->prepare($sql);
    foreach ($ids as $index => $id) {
        $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
    }
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cantidad = $carrito[(int) $row['id_producto']] ?? 0;
        $subtotal = $cantidad * (float) $row['precio_renta_dia'];
        $total += $subtotal;

        $detalles[] = [
            'id_producto' => (int) $row['id_producto'],
            'nombre' => $row['nombre'],
            'precio_renta_dia' => (float) $row['precio_renta_dia'],
            'cantidad' => $cantidad,
            'subtotal' => $subtotal
        ];
    }
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
            <button onclick="location.href='catalogo.php'">Catálogo</button>
            <button disabled>Carrito</button>
            <button onclick="location.href='pedido.php'">Mis pedidos</button>
            <button onclick="location.href='perfil.html'">Perfil</button>
        </div>

        <div id="listaCarrito">
            <?php if (empty($detalles)): ?>
                <p>tu carrito está vacío.</p>
            <?php else: ?>
                <?php foreach ($detalles as $detalle): ?>
                    <article class="itemCarrito">
                        <h3><?= htmlspecialchars($detalle['nombre']) ?></h3>
                        <p>cantidad: <?= (int) $detalle['cantidad'] ?></p>
                        <p>precio por día: $<?= number_format($detalle['precio_renta_dia'], 2) ?></p>
                        <p>subtotal: $<?= number_format($detalle['subtotal'], 2) ?></p>
                        <form method="post">
                            <input type="hidden" name="id_producto" value="<?= (int) $detalle['id_producto'] ?>">
                            <button type="submit" name="accion" value="disminuir">disminuir</button>
                            <button type="submit" name="accion" value="eliminar">eliminar</button>
                        </form>
                    </article>
                <?php endforeach; ?>
                <p><strong>total estimado por día: $<?= number_format($total, 2) ?></strong></p>
            <?php endif; ?>
        </div>

        <div id="accionesCarrito">
            <button onclick="location.href='confirmar_pedido.php'" <?= empty($detalles) ? 'disabled' : '' ?>>
                Confirmar pedido
            </button>
        </div>

    </div>

</body>
</html>
