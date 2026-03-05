<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/conexion.php';

if (!isset($_SESSION['cliente'])) {
    http_response_code(401);
    echo json_encode(['mensaje' => 'Debes iniciar sesión para usar el carrito.']);
    exit;
}

$accion = $_POST['accion'] ?? '';
$idProducto = isset($_POST['id_producto']) ? (int) $_POST['id_producto'] : 0;
$cantidad = isset($_POST['cantidad']) ? (int) $_POST['cantidad'] : 0;

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

try {
    $conexion = Conexion::conectar();

    if ($accion === 'agregar' || $accion === 'actualizar') {
        if ($idProducto < 1 || $cantidad < 1) {
            throw new RuntimeException('Datos inválidos para carrito.');
        }

        $stmt = $conexion->prepare('SELECT id_producto, nombre, precio_renta_dia, stock_total, imagen FROM productos WHERE id_producto = :id AND estado = "disponible"');
        $stmt->bindValue(':id', $idProducto, PDO::PARAM_INT);
        $stmt->execute();
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            throw new RuntimeException('Producto no encontrado o sin disponibilidad.');
        }

        $actual = $_SESSION['carrito'][$idProducto]['cantidad'] ?? 0;
        $nuevaCantidad = $accion === 'agregar' ? $actual + $cantidad : $cantidad;

        if ($nuevaCantidad > (int) $producto['stock_total']) {
            throw new RuntimeException('No hay suficientes unidades disponibles.');
        }

        $_SESSION['carrito'][$idProducto] = [
            'id_producto' => (int) $producto['id_producto'],
            'nombre' => $producto['nombre'],
            'precio' => (float) $producto['precio_renta_dia'],
            'stock_total' => (int) $producto['stock_total'],
            'imagen' => $producto['imagen'],
            'cantidad' => $nuevaCantidad
        ];

        echo json_encode(['mensaje' => 'Carrito actualizado correctamente.']);
        exit;
    }

    if ($accion === 'disminuir') {
        if (isset($_SESSION['carrito'][$idProducto])) {
            $_SESSION['carrito'][$idProducto]['cantidad']--;
            if ($_SESSION['carrito'][$idProducto]['cantidad'] <= 0) {
                unset($_SESSION['carrito'][$idProducto]);
            }
        }
        echo json_encode(['mensaje' => 'Cantidad disminuida.']);
        exit;
    }

    if ($accion === 'eliminar') {
        unset($_SESSION['carrito'][$idProducto]);
        echo json_encode(['mensaje' => 'Producto eliminado del carrito.']);
        exit;
    }

    throw new RuntimeException('Acción no válida.');
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['mensaje' => $e->getMessage()]);
}
