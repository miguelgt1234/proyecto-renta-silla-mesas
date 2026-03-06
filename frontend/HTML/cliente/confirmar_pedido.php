<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../../backend/controllers/google_calendar_handler.php';
require_once __DIR__ . '/../../../backend/controllers/fcm_handler.php';

$googleConfig = require __DIR__ . '/../../../backend/config/google_apis.php';
$googleMapsApiKey = $googleConfig['maps_api_key'] ?? 'TU_API_KEY_DE_GOOGLE_MAPS';

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

            $pedidoApiData = [
                'id_pedido' => $idPedido,
                'cliente_nombre' => $cliente['nombre'] ?? 'Cliente',
                'fecha_entrega' => date('c', strtotime($fechaEntrega)),
                'fecha_recogida' => date('c', strtotime($fechaRecogida)),
                'direccion' => $direccion,
                'total' => $total
            ];

            $calendarHandler = new GoogleCalendarHandler();
            $resultadoCalendar = $calendarHandler->crearEventoPedido($pedidoApiData);

            // Token de dispositivo (placeholder: aquí podrías leerlo de BD por id_cliente)
            $deviceToken = $_POST['fcm_device_token'] ?? null;
            $fcmHandler = new FcmHandler();
            $resultadoFcm = $fcmHandler->enviarNotificacionPedido($deviceToken, $pedidoApiData);

            $conexion->commit();
            guardar_carrito([]);
            $mensaje = 'pedido confirmado correctamente. revisa tus pedidos para ver el detalle.';

            if (!($resultadoCalendar['success'] ?? false)) {
                $mensaje .= ' Google Calendar API pendiente: ' . ($resultadoCalendar['message'] ?? 'sin detalle') . '.';
            }
            if (!($resultadoFcm['success'] ?? false)) {
                $mensaje .= ' FCM pendiente: ' . ($resultadoFcm['message'] ?? 'sin detalle') . '.';
            }
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

            <input type="hidden" name="fcm_device_token" id="fcmDeviceToken">
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
            const centro = { lat: 19.4326, lng: -99.1332 };
            const mapa = new google.maps.Map(document.getElementById('mapa'), {
                center: centro,
                zoom: 12,
            });

            const marcador = new google.maps.Marker({
                position: centro,
                map: mapa,
                draggable: true,
            });

            function actualizarCampos(posicion) {
                const lat = posicion.lat();
                const lng = posicion.lng();
                document.getElementById('latitud').value = lat;
                document.getElementById('longitud').value = lng;
                inputDireccion.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            }

            actualizarCampos(marcador.getPosition());

            mapa.addListener('click', (evento) => {
                marcador.setPosition(evento.latLng);
                actualizarCampos(evento.latLng);
            });

            marcador.addListener('dragend', () => {
                actualizarCampos(marcador.getPosition());
            });
        }

        window.initMap = initMap;
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($googleMapsApiKey) ?>&callback=initMap"></script>

</body>
</html>