<?php
require_once __DIR__ . '/auth.php';

if (!isset($productos) || !isset($categorias)) {
    require_once __DIR__ . '/../../../backend/controllers/CatalogoController.php';
    $controllerCatalogo = new CatalogoController();
    $tipoSeleccionado = $_GET['tipo'] ?? null;
    $productos = $controllerCatalogo->obtenerProductos($tipoSeleccionado);
    $categorias = $controllerCatalogo->obtenerCategorias();
}


$rutaCatalogoActual = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
if (!$rutaCatalogoActual) {
    $rutaCatalogoActual = url_cliente('catalogo.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar_carrito') {
    if (!usuario_autenticado()) {
        header('Location: ' . url_login($_SERVER['REQUEST_URI'] ?? $rutaCatalogoActual));
        exit;
    }

    $idProducto = (int) ($_POST['id_producto'] ?? 0);
    $cantidad = max(1, (int) ($_POST['cantidad'] ?? 1));

    if ($idProducto > 0) {
        $carrito = obtener_carrito();
        $actual = $carrito[$idProducto] ?? 0;

        $stockProducto = 0;
        foreach ($productos as $productoItem) {
            if ((int) $productoItem['id_producto'] === $idProducto) {
                $stockProducto = (int) $productoItem['stock_total'];
                break;
            }
        }

        if ($stockProducto > 0) {
            $carrito[$idProducto] = min($stockProducto, $actual + $cantidad);
            guardar_carrito($carrito);
            $_SESSION['mensaje_carrito'] = 'producto agregado al carrito';
        }
    }

    $redireccion = $rutaCatalogoActual;
    $tipoRedireccion = $_POST['tipo'] ?? ($_GET['tipo'] ?? '');
    if ($tipoRedireccion !== '') {
        $redireccion .= '?tipo=' . urlencode((string) $tipoRedireccion);
    }

    header('Location: ' . $redireccion);
    exit;
}

$mensajeCarrito = $_SESSION['mensaje_carrito'] ?? '';
unset($_SESSION['mensaje_carrito']);
$clienteAutenticado = usuario_autenticado();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="<?= htmlspecialchars(url_cliente('catalogo.css')) ?>">
    <title>Catálogo</title>
</head>
<body>

    <div id="contenedorCatalogo">

        <div id="headerCatalogo">
            <h1 id="tituloCatalogo">Catálogo de Productos</h1>
            <button id="btnCatalogo" disabled>Catálogo</button>
            <button id="btnCarrito" onclick="location.href='<?= htmlspecialchars(url_cliente('carrito.php')) ?>'">Carrito</button>
            <button id="btnMisPedidos" onclick="location.href='<?= htmlspecialchars(url_cliente('pedido.php')) ?>'">Mis Pedidos</button>
            <button id="btnPerfil" onclick="location.href='<?= htmlspecialchars(url_cliente('perfil.html')) ?>'">Perfil</button>
            <?php if ($clienteAutenticado): ?>
                <button id="btnCerrarSesion" onclick="location.href='<?= htmlspecialchars(url_cliente('inicio_de_sesion.php?logout=1')) ?>'">Cerrar sesión</button>
            <?php else: ?>
                <button id="btnIniciarSesion" onclick="location.href='<?= htmlspecialchars(url_login($_SERVER['REQUEST_URI'] ?? $rutaCatalogoActual)) ?>'">Iniciar sesión</button>
            <?php endif; ?>
        </div>

        <div id="seccionBienvenidaFiltro">
            <div id="contenedorBienvenida">
                <h2 id="textoBienvenida">Bienvenido Cliente</h2>
                <p id="descripcionBienvenida">
                    Explora nuestro catálogo y renta mesas y sillas para tu evento.
                </p>
                <?php if ($mensajeCarrito): ?>
                    <p><?= htmlspecialchars($mensajeCarrito) ?></p>
                <?php endif; ?>
            </div>

            <div id="contenedorFiltro">
                <form id="formFiltro" method="GET">
                    <label id="labelFiltroTipo" for="selectTipoProducto">Filtrar por tipo:</label>
                    <select id="selectTipoProducto" name="tipo">
                        <option value="">Todos</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option
                                value="<?= $categoria['id_categoria'] ?>"
                                <?= ((string) $tipoSeleccionado === (string) $categoria['id_categoria']) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($categoria['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button id="btnFiltrar" type="submit">Buscar</button>
                </form>
            </div>
        </div>

        <div id="seccionProductos">
            <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $producto): ?>
                    <div class="productoCard">
                        <div class="imagenProducto">
                            <img
                                class="imgProducto"
                                src="/proyecto-renta-silla-mesas/uploads/<?= htmlspecialchars($producto['imagen']) ?>"
                                alt="Imagen del producto"
                            >
                        </div>

                        <div class="infoProducto">
                            <h3 class="nombreProducto"><?= htmlspecialchars($producto['nombre']) ?></h3>
                            <p class="precioProducto">$<?= number_format($producto['precio_renta_dia'], 2) ?> por día</p>
                            <p class="stockProducto">unidades disponibles: <?= (int) $producto['stock_total'] ?></p>
                        </div>

                        <div class="accionesProducto">
                            <form method="POST" action="<?= htmlspecialchars($rutaCatalogoActual) ?>" class="formAgregarCarrito" onsubmit="return capturarCantidad(event, <?= (int) $producto['id_producto'] ?>, <?= (int) $producto['stock_total'] ?>)">
                                <input type="hidden" name="accion" value="agregar_carrito">
                                <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto'] ?>">
                                <input type="hidden" name="cantidad" id="cantidad-<?= (int) $producto['id_producto'] ?>" value="1">
                                <?php if (!empty($tipoSeleccionado)): ?>
                                    <input type="hidden" name="tipo" value="<?= htmlspecialchars((string) $tipoSeleccionado) ?>">
                                <?php endif; ?>
                                <button class="btnAgregar" type="submit">Agregar</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay productos disponibles.</p>
            <?php endif; ?>
        </div>

    </div>

    <script>
        function capturarCantidad(event, idProducto, stockDisponible) {
            const autenticado = <?= $clienteAutenticado ? 'true' : 'false' ?>;

            if (!autenticado) {
                event.preventDefault();
                window.location.href = '<?= htmlspecialchars(url_login($_SERVER['REQUEST_URI'] ?? $rutaCatalogoActual)) ?>';
                return false;
            }

            const cantidadTexto = prompt('¿cuántas unidades deseas agregar?', '1');
            if (cantidadTexto === null) {
                event.preventDefault();
                return false;
            }

            const cantidad = Number.parseInt(cantidadTexto, 10);
            if (!Number.isInteger(cantidad) || cantidad <= 0) {
                alert('ingresa una cantidad válida.');
                event.preventDefault();
                return false;
            }

            if (cantidad > stockDisponible) {
                alert('la cantidad solicitada supera el stock disponible.');
                event.preventDefault();
                return false;
            }

            document.getElementById('cantidad-' + idProducto).value = String(cantidad);
            return true;
        }
    </script>

</body>
</html>
