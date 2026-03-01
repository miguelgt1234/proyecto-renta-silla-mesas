<?php
// Estas variables ya vienen desde el controlador:
// $productos
// $categorias
// $tipoSeleccionado
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="catalogo.css">
    <title>Catálogo</title>
</head>
<body>

    <div id="contenedorCatalogo">

        <div id="headerCatalogo">
            <h1 id="tituloCatalogo">Catálogo de Productos</h1>
            <button id="btnCatalogo" disabled>Catálogo</button>
            <button id="btnCarrito" onclick="location.href='carrito.html'">Carrito</button>
            <button id="btnMisPedidos" onclick="location.href='pedido.html'">Mis Pedidos</button>
            <button id="btnPerfil" onclick="location.href='perfil.html'">Perfil</button>
        </div>

        <!-- FILTRO -->
        <div id="seccionBienvenidaFiltro">

            <div id="contenedorBienvenida">
                <h2 id="textoBienvenida">Bienvenido Cliente</h2>
                <p id="descripcionBienvenida">
                    Explora nuestro catálogo y renta mesas y sillas para tu evento.
                </p>
            </div>

            <!-- FILTRO POR TIPO -->
            <div id="contenedorFiltro">

                <form id="formFiltro" method="GET">

                    <label id="labelFiltroTipo" for="selectTipoProducto">
                        Filtrar por tipo:
                    </label>

                    <select id="selectTipoProducto" name="tipo">
                        <option value="">Todos</option>

                        <?php foreach ($categorias as $categoria): ?>
                            <option 
                                value="<?= $categoria['id_categoria'] ?>"
                                <?= ($tipoSeleccionado == $categoria['id_categoria']) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($categoria['nombre']) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <button id="btnFiltrar" type="submit">Buscar</button>

                </form>

            </div>

        </div>

        <!-- LISTADO DE PRODUCTOS -->
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
                            <h3 class="nombreProducto">
                                <?= htmlspecialchars($producto['nombre']) ?>
                            </h3>

                            <p class="precioProducto">
                                $<?= number_format($producto['precio'], 2) ?> por día
                            </p>
                        </div>

                        <div class="accionesProducto">
                            <button class="btnAgregar">
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

</body>
</html>