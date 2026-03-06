<?php
require_once __DIR__ . '/auth.php';
requerir_autenticacion();

$cliente = obtener_cliente_autenticado();
$carrito = obtener_carrito();

if (empty($carrito)) {
    header('Location: carrito.php');
    exit;
}

$conexion = obtener_conexion_app();
$ids = array_map('intval', array_keys($carrito));
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "SELECT id_producto, nombre, precio_renta_dia, stock_total FROM productos WHERE id_producto IN ($placeholders)";
$stmt = $conexion->prepare($sql);
foreach ($ids as $index => $id) {
    $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
}
$stmt->execute();

$productosCarrito = [];
$total = 0;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cantidad = $carrito[(int) $row['id_producto']] ?? 0;
    $subtotal = $cantidad * (float) $row['precio_renta_dia'];
    $total += $subtotal;
    $productosCarrito[] = [
        'id_producto' => (int) $row['id_producto'],
        'nombre' => $row['nombre'],
        'precio' => (float) $row['precio_renta_dia'],
        'stock_total' => (int) $row['stock_total'],
        'cantidad' => $cantidad,
        'subtotal' => $subtotal
    ];
}

$error = '';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $direccion = trim($_POST['direccion'] ?? '');
    $fechaEntrega = $_POST['fecha_entrega'] ?? '';
    $fechaRecogida = $_POST['fecha_recogida'] ?? '';
    $latitud = $_POST['latitud'] ?? null;
    $longitud = $_POST['longitud'] ?? null;

    if ($direccion === '' || $fechaEntrega === '' || $fechaRecogida === '') {
        $error = 'completa todos los campos para confirmar el pedido';
    } elseif (strtotime($fechaEntrega) === false || strtotime($fechaRecogida) === false || strtotime($fechaEntrega) >= strtotime($fechaRecogida)) {
        $error = 'la fecha de entrega debe ser anterior a la fecha de finalización';
    } else {
        try {
            $conexion->beginTransaction();

            foreach ($productosCarrito as $producto) {
                if ($producto['cantidad'] > $producto['stock_total']) {
                    throw new RuntimeException('no hay stock suficiente para ' . $producto['nombre']);
                }
            }

            $sqlDireccion = 'INSERT INTO direcciones_guardadas_cliente (id_cliente, calle, latitud, longitud)
                            VALUES (:id_cliente, :calle, :latitud, :longitud)';
            $stmtDireccion = $conexion->prepare($sqlDireccion);
            $stmtDireccion->bindValue(':id_cliente', (int) $cliente['id_cliente'], PDO::PARAM_INT);
            $stmtDireccion->bindValue(':calle', $direccion);
            $stmtDireccion->bindValue(':latitud', $latitud !== '' ? $latitud : null);
            $stmtDireccion->bindValue(':longitud', $longitud !== '' ? $longitud : null);
            $stmtDireccion->execute();
            $idDireccion = (int) $conexion->lastInsertId();

            $sqlPedido = 'INSERT INTO pedidos (fecha_entrega, fecha_recogida, costo_total, id_cliente, id_direccion_cliente)
                        VALUES (:fecha_entrega, :fecha_recogida, :costo_total, :id_cliente, :id_direccion_cliente)';
            $stmtPedido = $conexion->prepare($sqlPedido);
            $stmtPedido->bindValue(':fecha_entrega', date('Y-m-d H:i:s', strtotime($fechaEntrega)));
            $stmtPedido->bindValue(':fecha_recogida', date('Y-m-d H:i:s', strtotime($fechaRecogida)));
            $stmtPedido->bindValue(':costo_total', $total);
            $stmtPedido->bindValue(':id_cliente', (int) $cliente['id_cliente'], PDO::PARAM_INT);
            $stmtPedido->bindValue(':id_direccion_cliente', $idDireccion, PDO::PARAM_INT);
            $stmtPedido->execute();
            $idPedido = (int) $conexion->lastInsertId();

            $sqlDetalle = 'INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal)
                          VALUES (:id_pedido, :id_producto, :cantidad, :precio_unitario, :subtotal)';
            $stmtDetalle = $conexion->prepare($sqlDetalle);

            $sqlStock = 'UPDATE productos SET stock_total = stock_total - :cantidad WHERE id_producto = :id_producto';
            $stmtStock = $conexion->prepare($sqlStock);

            foreach ($productosCarrito as $producto) {
                $stmtDetalle->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
                $stmtDetalle->bindValue(':id_producto', $producto['id_producto'], PDO::PARAM_INT);
                $stmtDetalle->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
                $stmtDetalle->bindValue(':precio_unitario', $producto['precio']);
                $stmtDetalle->bindValue(':subtotal', $producto['subtotal']);
                $stmtDetalle->execute();

                $stmtStock->bindValue(':cantidad', $producto['cantidad'], PDO::PARAM_INT);
                $stmtStock->bindValue(':id_producto', $producto['id_producto'], PDO::PARAM_INT);
                $stmtStock->execute();
            }

            $sqlNotificacion = 'INSERT INTO notificaciones (tipo, mensaje, id_pedido, id_cliente)
                               VALUES (:tipo, :mensaje, :id_pedido, :id_cliente)';
            $stmtNotificacion = $conexion->prepare($sqlNotificacion);
            $stmtNotificacion->bindValue(':tipo', 'push');
            $stmtNotificacion->bindValue(':mensaje', 'tu pedido ha sido creado correctamente');
            $stmtNotificacion->bindValue(':id_pedido', $idPedido, PDO::PARAM_INT);
            $stmtNotificacion->bindValue(':id_cliente', (int) $cliente['id_cliente'], PDO::PARAM_INT);
            $stmtNotificacion->execute();

            $conexion->commit();
            guardar_carrito([]);
            $mensaje = 'pedido confirmado correctamente. revisa tus pedidos para ver el detalle.';
        } catch (Throwable $exception) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            $error = 'no fue posible confirmar el pedido: ' . $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="confirmar_pedido.css">
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    >
    <title>Confirmar Pedido</title>
</head>
<body>

    <div id="contenedorConfirmar">
        <div id="headerConfirmar">
            <h1>Confirmar Pedido</h1>
            <button onclick="location.href='catalogo.php'">Catálogo</button>
            <button onclick="location.href='carrito.php'">Carrito</button>
            <button onclick="location.href='pedido.php'">Mis pedidos</button>
            <button onclick="location.href='perfil.html'">Perfil</button>
        </div>

        <?php if ($error): ?><p><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <?php if ($mensaje): ?><p><?= htmlspecialchars($mensaje) ?></p><?php endif; ?>

        <div id="resumenPedido">
            <h2>Resumen del carrito</h2>
            <?php foreach ($productosCarrito as $producto): ?>
                <p><?= htmlspecialchars($producto['nombre']) ?> - <?= (int) $producto['cantidad'] ?> unidad(es) - $<?= number_format($producto['subtotal'], 2) ?></p>
            <?php endforeach; ?>
            <p><strong>Total estimado por día: $<?= number_format($total, 2) ?></strong></p>
        </div>

        <form method="post">
            <div>
                <label for="direccion">Dirección de entrega</label>
                <input type="text" name="direccion" id="direccion" required>
            </div>

            <div>
                <label for="fechaEntrega">Fecha y hora de entrega</label>
                <input type="datetime-local" name="fecha_entrega" id="fechaEntrega" required>
            </div>

            <div>
                <label for="fechaRecogida">Fecha y hora de finalización</label>
                <input type="datetime-local" name="fecha_recogida" id="fechaRecogida" required>
            </div>

            <input type="hidden" name="latitud" id="latitud">
            <input type="hidden" name="longitud" id="longitud">

            <button type="submit">Confirmar pedido</button>
        </form>

        <section>
            <h3>Mapa de referencia</h3>
            <div id="mapa" style="width: 100%; height: 250px; background: #e9ecef; margin-bottom: 10px;"></div>
        </section>

    </div>

    <script>
        const inputDireccion = document.getElementById('direccion');
        function initMap() {
            const centro = [19.4326, -99.1332];
            const mapa = L.map('mapa').setView(centro, 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(mapa);

            let marcador = L.marker(centro).addTo(mapa);
            document.getElementById('latitud').value = centro[0];
            document.getElementById('longitud').value = centro[1];
            inputDireccion.value = `${centro[0].toFixed(6)}, ${centro[1].toFixed(6)}`;

            mapa.on('click', (evento) => {
                const { lat, lng } = evento.latlng;
                marcador.setLatLng([lat, lng]);
                document.getElementById('latitud').value = lat;
                document.getElementById('longitud').value = lng;
                inputDireccion.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            });
        }

        window.addEventListener('DOMContentLoaded', initMap);
    </script>
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>

</body>
</html>
