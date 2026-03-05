<?php
session_start();
require_once __DIR__ . '/../../../backend/config/conexion.php';
if (!isset($_SESSION['cliente'])) {
    header('Location: inicio_de_sesion.php');
    exit;
}

$pedidos = [];
try {
    $conexion = Conexion::conectar();
    $stmt = $conexion->prepare('SELECT p.id_pedido, p.fecha_pedido, p.fecha_entrega, p.fecha_recogida, p.estado, p.costo_total, d.calle FROM pedidos p INNER JOIN direcciones_guardadas_cliente d ON d.id_direccion_cliente = p.id_direccion_cliente WHERE p.id_cliente = :id ORDER BY p.fecha_pedido DESC');
    $stmt->bindValue(':id', $_SESSION['cliente']['id_cliente'], PDO::PARAM_INT);
    $stmt->execute();
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $pedidos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="pedido.css">
    <title>Mis pedidos</title>
</head>
<body>
<div id="contenedorPedidos">
    <div id="headerPedidos">
        <h1>Mis pedidos</h1>
        <button onclick="location.href='../../../backend/controllers/catalago.php'">Catálogo</button>
        <button onclick="location.href='carrito.php'">Carrito</button>
        <button disabled>Mis pedidos</button>
        <button onclick="location.href='perfil.html'">Perfil</button>
    </div>

    <div id="tablaPedidos">
        <?php if (empty($pedidos)): ?>
            <p>Aún no tienes pedidos registrados.</p>
        <?php else: ?>
            <table border="1" cellpadding="8">
                <thead><tr><th>Pedido</th><th>Fecha</th><th>Entrega</th><th>Fin</th><th>Dirección</th><th>Estado</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td>#<?= (int)$p['id_pedido'] ?></td>
                        <td><?= htmlspecialchars($p['fecha_pedido']) ?></td>
                        <td><?= htmlspecialchars($p['fecha_entrega']) ?></td>
                        <td><?= htmlspecialchars($p['fecha_recogida']) ?></td>
                        <td><?= htmlspecialchars($p['calle']) ?></td>
                        <td><?= htmlspecialchars($p['estado']) ?></td>
                        <td>$<?= number_format($p['costo_total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
