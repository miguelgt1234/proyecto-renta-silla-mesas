<?php
require_once __DIR__ . '/auth.php';
requerir_autenticacion();

$cliente = obtener_cliente_autenticado();
$conexion = obtener_conexion_app();

$sqlPedidos = 'SELECT p.id_pedido, p.fecha_pedido, p.fecha_entrega, p.fecha_recogida, p.estado, p.costo_total, d.calle
              FROM pedidos p
              INNER JOIN direcciones_guardadas_cliente d ON d.id_direccion_cliente = p.id_direccion_cliente
              WHERE p.id_cliente = :id_cliente
              ORDER BY p.fecha_pedido DESC';
$stmtPedidos = $conexion->prepare($sqlPedidos);
$stmtPedidos->bindValue(':id_cliente', (int) $cliente['id_cliente'], PDO::PARAM_INT);
$stmtPedidos->execute();
$pedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);
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
        <button onclick="location.href='perfil.php'">Perfil</button>
    </div>

    <div id="tablaPedidos">
        <?php if (empty($pedidos)): ?>
            <p>aún no has generado pedidos.</p>
        <?php else: ?>
            <table border="1" cellpadding="6" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>pedido</th>
                        <th>fecha creación</th>
                        <th>entrega</th>
                        <th>finalización</th>
                        <th>dirección</th>
                        <th>estado</th>
                        <th>total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <td>#<?= (int) $pedido['id_pedido'] ?></td>
                        <td><?= htmlspecialchars($pedido['fecha_pedido']) ?></td>
                        <td><?= htmlspecialchars($pedido['fecha_entrega']) ?></td>
                        <td><?= htmlspecialchars($pedido['fecha_recogida']) ?></td>
                        <td><?= htmlspecialchars($pedido['calle']) ?></td>
                        <td><?= htmlspecialchars($pedido['estado']) ?></td>
                        <td>$<?= number_format((float) $pedido['costo_total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
