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

        <section>
            <h3>Integración Google Calendar y Firebase Cloud Messaging</h3>
            <a id="linkCalendar" target="_blank" rel="noopener">Crear evento rápido en Google Calendar</a>
        </section>
    </div>

    <script>
        const linkCalendar = document.getElementById('linkCalendar');
        const fechaEntrega = document.getElementById('fechaEntrega');
        const fechaRecogida = document.getElementById('fechaRecogida');
        const inputDireccion = document.getElementById('direccion');

        function formatoCalendar(valor) {
            return valor.replace(/[-:]/g, '').replace('T', '') + '00';
        }

        function actualizarEnlaceCalendar() {
            if (!fechaEntrega.value || !fechaRecogida.value) {
                linkCalendar.href = '#';
                return;
            }

            const inicio = formatoCalendar(fechaEntrega.value);
            const fin = formatoCalendar(fechaRecogida.value);
            const texto = encodeURIComponent('renta de mobiliario');
            const detalles = encodeURIComponent('pedido creado en renta de sillas y mesas');
            linkCalendar.href = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${texto}&dates=${inicio}/${fin}&details=${detalles}`;
        }

        fechaEntrega.addEventListener('change', actualizarEnlaceCalendar);
        fechaRecogida.addEventListener('change', actualizarEnlaceCalendar);

        function initMap() {
            const mapaContenedor = document.getElementById('mapa');
            if (!window.google || !window.google.maps) {
                const mensaje = `
                    <div style="padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">
                        <strong>Error de carga de Google Maps</strong><br>
                        Verifica que:<br>
                        1. Tu API key sea válida<br>
                        2. Tengas habilitadas: Maps JavaScript API, Geocoding API<br>
                        3. El dominio esté autorizado en Google Cloud Console<br>
                        4. No haya restricciones de IP o referrer<br>
                        <small>Abre la consola (F12) para más detalles</small>
                    </div>
                `;
                mapaContenedor.innerHTML = mensaje;
                console.error('Google Maps no cargó. Detalles:', { google: window.google, maps: window.google?.maps });
                return;
            }

            const centro = { lat: 19.4326, lng: -99.1332 };
            const mapa = new google.maps.Map(mapaContenedor, {
                center: centro,
                zoom: 12
            });

            let marcador = new google.maps.Marker({ position: centro, map: mapa });
            document.getElementById('latitud').value = centro.lat;
            document.getElementById('longitud').value = centro.lng;

            // Utilizar Geocoder para obtener dirección
            const geocoder = new google.maps.Geocoder();

            function actualizarDireccion(ubicacion) {
                geocoder.geocode({ location: ubicacion }, (results, status) => {
                    if (status === google.maps.GeocoderStatus.OK && results && results.length > 0) {
                        // Usar la dirección formateada del primer resultado
                        inputDireccion.value = results[0].formatted_address;
                    } else if (status === google.maps.GeocoderStatus.ZERO_RESULTS) {
                        // Si no encuentra resultados, usar la dirección de nivel inferior
                        console.warn('No se encontraron resultados de geocodificación');
                        inputDireccion.value = `${ubicacion.lat().toFixed(6)}, ${ubicacion.lng().toFixed(6)}`;
                    } else {
                        console.warn('Error de geocodificación:', status);
                        inputDireccion.value = `${ubicacion.lat().toFixed(6)}, ${ubicacion.lng().toFixed(6)}`;
                    }
                });
            }

            // Obtener dirección inicial
            actualizarDireccion(centro);

            mapa.addListener('click', (evento) => {
                const ubicacion = evento.latLng;
                marcador.setPosition(ubicacion);
                document.getElementById('latitud').value = ubicacion.lat();
                document.getElementById('longitud').value = ubicacion.lng();
                actualizarDireccion(ubicacion);
            });
        }

        window.initMap = initMap;
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBFYpRdvQXuQJw5FQtt4O8RkmOJBAGypR0&callback=initMap"></script>

</body>
<script type="module">
  // Import the functions you need from the SDKs you need
  import { initializeApp } from "https://www.gstatic.com/firebasejs/12.10.0/firebase-app.js";
  import { getAnalytics } from "https://www.gstatic.com/firebasejs/12.10.0/firebase-analytics.js";
  // TODO: Add SDKs for Firebase products that you want to use
  // https://firebase.google.com/docs/web/setup#available-libraries

  // Your web app's Firebase configuration
  // For Firebase JS SDK v7.20.0 and later, measurementId is optional
  const firebaseConfig = {
    apiKey: "AIzaSyBv-v4-himeBF5cR4qmEsZBQPtIkfDjMxc",
    authDomain: "sistemarenta-489401.firebaseapp.com",
    projectId: "sistemarenta-489401",
    storageBucket: "sistemarenta-489401.firebasestorage.app",
    messagingSenderId: "326104073981",
    appId: "1:326104073981:web:b51f8437c6be2c5770e094",
    measurementId: "G-6KYBNN555Y"
  };

  // Initialize Firebase
  const app = initializeApp(firebaseConfig);
  const analytics = getAnalytics(app);
</script>
</html>
