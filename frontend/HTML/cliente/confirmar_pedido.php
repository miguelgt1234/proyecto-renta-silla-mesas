<?php
session_start();
require_once __DIR__ . '/../../../backend/config/conexion.php';

if (!isset($_SESSION['cliente'])) {
    header('Location: inicio_de_sesion.php');
    exit;
}

$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    header('Location: carrito.php');
    exit;
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $direccion = trim($_POST['direccion'] ?? '');
    $fechaEntrega = trim($_POST['fechaEntrega'] ?? '');
    $fechaFin = trim($_POST['fechaFin'] ?? '');
    $latitud = trim($_POST['latitud'] ?? '');
    $longitud = trim($_POST['longitud'] ?? '');

    if ($direccion === '' || $fechaEntrega === '' || $fechaFin === '') {
        $error = 'Completa todos los campos obligatorios.';
    } else {
        try {
            $conexion = Conexion::conectar();
            $conexion->beginTransaction();

            $partesDireccion = array_map('trim', explode(',', $direccion));
            $calle = $partesDireccion[0] ?? $direccion;
            $colonia = $partesDireccion[1] ?? null;
            $ciudad = $partesDireccion[2] ?? null;
            $estado = $partesDireccion[3] ?? null;

            $stmtDir = $conexion->prepare('INSERT INTO direcciones_guardadas_cliente (id_cliente, calle, colonia, ciudad, estado, latitud, longitud) VALUES (:id_cliente, :calle, :colonia, :ciudad, :estado, :latitud, :longitud)');
            $stmtDir->execute([
                ':id_cliente' => $_SESSION['cliente']['id_cliente'],
                ':calle' => $calle,
                ':colonia' => $colonia,
                ':ciudad' => $ciudad,
                ':estado' => $estado,
                ':latitud' => $latitud !== '' ? $latitud : null,
                ':longitud' => $longitud !== '' ? $longitud : null,
            ]);
            $idDireccion = (int) $conexion->lastInsertId();

            $total = 0;
            foreach ($carrito as $item) {
                $total += $item['precio'] * $item['cantidad'];
            }

            $stmtPedido = $conexion->prepare('INSERT INTO pedidos (fecha_entrega, fecha_recogida, costo_total, id_cliente, id_direccion_cliente) VALUES (:entrega, :recogida, :total, :id_cliente, :id_direccion)');
            $stmtPedido->execute([
                ':entrega' => $fechaEntrega,
                ':recogida' => $fechaFin,
                ':total' => $total,
                ':id_cliente' => $_SESSION['cliente']['id_cliente'],
                ':id_direccion' => $idDireccion,
            ]);
            $idPedido = (int) $conexion->lastInsertId();

            $stmtDetalle = $conexion->prepare('INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES (:id_pedido, :id_producto, :cantidad, :precio, :subtotal)');
            foreach ($carrito as $item) {
                $subtotal = $item['precio'] * $item['cantidad'];
                $stmtDetalle->execute([
                    ':id_pedido' => $idPedido,
                    ':id_producto' => $item['id_producto'],
                    ':cantidad' => $item['cantidad'],
                    ':precio' => $item['precio'],
                    ':subtotal' => $subtotal,
                ]);
            }

            $stmtNoti = $conexion->prepare('INSERT INTO notificaciones (tipo, mensaje, id_pedido, id_cliente) VALUES ("push", :mensaje, :id_pedido, :id_cliente)');
            $stmtNoti->execute([
                ':mensaje' => 'Tu pedido ha sido creado correctamente. (Pendiente integrar Firebase Cloud Messaging)',
                ':id_pedido' => $idPedido,
                ':id_cliente' => $_SESSION['cliente']['id_cliente'],
            ]);

            $conexion->commit();
            $_SESSION['carrito'] = [];
            $mensaje = 'Pedido confirmado. Revisa la sección Mis pedidos.';
        } catch (Throwable $e) {
            if (isset($conexion) && $conexion->inTransaction()) {
                $conexion->rollBack();
            }
            $error = 'No se pudo confirmar el pedido.';
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
        <button onclick="location.href='../../../backend/controllers/catalago.php'">Catálogo</button>
        <button onclick="location.href='carrito.php'">Carrito</button>
        <button onclick="location.href='pedido.php'">Mis pedidos</button>
        <button onclick="location.href='perfil.html'">Perfil</button>
    </div>

    <div id="resumenPedido">
        <?php foreach ($carrito as $item): ?>
            <p><?= htmlspecialchars($item['nombre']) ?> x <?= (int)$item['cantidad'] ?></p>
        <?php endforeach; ?>
    </div>

    <?php if ($mensaje): ?><p><?= htmlspecialchars($mensaje) ?></p><?php endif; ?>
    <?php if ($error): ?><p><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <form method="post">
        <div>
            <label for="direccion">Dirección de entrega</label>
            <input type="text" name="direccion" id="direccion" required>
        </div>
        <div>
            <label for="fechaEntrega">Fecha y hora de entrega</label>
            <input type="datetime-local" name="fechaEntrega" id="fechaEntrega" required>
        </div>
        <div>
            <label for="fechaFin">Fecha y hora de finalización</label>
            <input type="datetime-local" name="fechaFin" id="fechaFin" required>
        </div>
        <input type="hidden" name="latitud" id="latitud">
        <input type="hidden" name="longitud" id="longitud">
        <div>
            <p><strong>Google Maps/Calendar:</strong> Reemplaza <code>YOUR_GOOGLE_MAPS_API_KEY</code> para habilitar autocompletado y mapa.</p>
            <div id="map" style="width:100%;height:240px;background:#e9ecef"></div>
        </div>
        <button type="submit">Confirmar pedido</button>
    </form>

    <section>
        <h3>Cómo configurar APIs</h3>
        <ol>
            <li>En Google Cloud Console crea un proyecto y habilita Maps JavaScript API, Places API y Calendar API.</li>
            <li>Crea una API Key restringida por dominio para Maps.</li>
            <li>Configura OAuth consent screen y credenciales OAuth 2.0 para Calendar.</li>
            <li>Para Firebase Cloud Messaging, crea proyecto Firebase, registra app web y copia config + VAPID key.</li>
            <li>Usa Cloud Functions o backend para enviar la notificación al token FCM del cliente.</li>
        </ol>
    </section>
</div>
<script>
function initMap() {
  const defaultPos = { lat: 19.4326, lng: -99.1332 };
  const map = new google.maps.Map(document.getElementById('map'), { zoom: 11, center: defaultPos });
  const marker = new google.maps.Marker({ map, position: defaultPos, draggable: true });
  marker.addListener('dragend', (e) => {
    document.getElementById('latitud').value = e.latLng.lat();
    document.getElementById('longitud').value = e.latLng.lng();
  });
}
</script>
<script async src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&libraries=places&callback=initMap"></script>
</body>
</html>
