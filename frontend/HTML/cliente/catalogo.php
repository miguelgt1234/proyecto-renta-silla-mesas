<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$clienteAutenticado = isset($_SESSION['cliente']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="/proyecto-renta-silla-mesas/frontend/HTML/cliente/catalogo.css">
    <title>Catálogo</title>
</head>
<body>
    <div id="contenedorCatalogo">
        <div id="headerCatalogo">
            <h1 id="tituloCatalogo">Catálogo de Productos</h1>
            <button id="btnCatalogo" disabled>Catálogo</button>
            <button id="btnCarrito" onclick="location.href='/proyecto-renta-silla-mesas/frontend/HTML/cliente/carrito.php'">Carrito</button>
            <button id="btnMisPedidos" onclick="location.href='/proyecto-renta-silla-mesas/frontend/HTML/cliente/pedido.php'">Mis Pedidos</button>
            <button id="btnPerfil" onclick="location.href='/proyecto-renta-silla-mesas/frontend/HTML/cliente/perfil.html'">Perfil</button>
            <?php if ($clienteAutenticado): ?>
                <button onclick="location.href='/proyecto-renta-silla-mesas/backend/controllers/logout.php'">Cerrar sesión</button>
            <?php else: ?>
                <button onclick="location.href='/proyecto-renta-silla-mesas/frontend/HTML/cliente/inicio_de_sesion.php'">Iniciar sesión</button>
            <?php endif; ?>
        </div>

        <div id="seccionBienvenidaFiltro">
            <div id="contenedorBienvenida">
                <h2 id="textoBienvenida">Bienvenido Cliente</h2>
                <p id="descripcionBienvenida">Explora nuestro catálogo y renta mesas y sillas para tu evento.</p>
            </div>

            <div id="contenedorFiltro">
                <form id="formFiltro" method="GET" action="/proyecto-renta-silla-mesas/backend/controllers/catalago.php">
                    <label id="labelFiltroTipo" for="selectTipoProducto">Filtrar por tipo:</label>
                    <select id="selectTipoProducto" name="tipo">
                        <option value="">Todos</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id_categoria'] ?>" <?= ($tipoSeleccionado == $categoria['id_categoria']) ? 'selected' : '' ?>>
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
                            <img class="imgProducto" src="/proyecto-renta-silla-mesas/backend/uploads/<?= htmlspecialchars($producto['imagen']) ?>" alt="Imagen del producto">
                        </div>
                        <div class="infoProducto">
                            <h3 class="nombreProducto"><?= htmlspecialchars($producto['nombre']) ?></h3>
                            <p class="precioProducto">$<?= number_format($producto['precio_renta_dia'], 2) ?> por día</p>
                            <p class="stockProducto">Unidades disponibles: <?= (int) $producto['stock_total'] ?></p>
                        </div>
                        <div class="accionesProducto">
                            <button
                                class="btnAgregar"
                                type="button"
                                data-id="<?= (int) $producto['id_producto'] ?>"
                                data-stock="<?= (int) $producto['stock_total'] ?>"
                                onclick="agregarAlCarrito(this)">
                                Agregar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay productos disponibles.</p>
            <?php endif; ?>
        </div>
    </div>

<script>
const clienteAutenticado = <?= $clienteAutenticado ? 'true' : 'false' ?>;
function agregarAlCarrito(button) {
    if (!clienteAutenticado) {
        // redirect to login page, preserving current location for return
        const returnUrl = encodeURIComponent(window.location.href);
        window.location.href = '/proyecto-renta-silla-mesas/frontend/HTML/cliente/inicio_de_sesion.php?return=' + returnUrl;
        return;
    }

    const stock = parseInt(button.dataset.stock, 10);
    const cantidadTexto = prompt(`¿Cuántas unidades deseas agregar? (Máximo ${stock})`, '1');
    if (cantidadTexto === null) return;

    const cantidad = parseInt(cantidadTexto, 10);
    if (isNaN(cantidad) || cantidad < 1 || cantidad > stock) {
        alert('Cantidad inválida.');
        return;
    }

    const formData = new URLSearchParams();
    formData.append('accion', 'agregar');
    formData.append('id_producto', button.dataset.id);
    formData.append('cantidad', cantidad.toString());

    fetch('../../../backend/controllers/carrito_acciones.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    }).then(r => r.json()).then(data => {
        alert(data.mensaje || 'Producto agregado.');
    }).catch(() => alert('No fue posible agregar al carrito.'));
}
</script>
</body>
</html>
