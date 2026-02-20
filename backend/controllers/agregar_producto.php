<?php

require_once __DIR__ . '/../modelos/producto.php';
require_once __DIR__ . '/../DAO/ProductoDAO.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    //Capturar datos
    $nombre = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    $precio = floatval($_POST["precio"]);
    $stock = intval($_POST["stock"]);
    $id_categoria = intval($_POST["categoria"]);

    if (
        empty($nombre) ||
        empty($descripcion) ||
        $precio < 0 ||
        $stock < 0 ||
        empty($id_categoria)
    ) {
        die("Datos inválidos.");
    }

    //Subir imagen
    $imagenNombre = null;

    if (isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] === 0) {

        $directorio = __DIR__ . '/../uploads/';

        // Crear carpeta si no existe
        if (!file_exists($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $imagenNombre = time() . "_" . basename($_FILES["imagen"]["name"]);
        $rutaCompleta = $directorio . $imagenNombre;

        move_uploaded_file($_FILES["imagen"]["tmp_name"], $rutaCompleta);
    } else {
        die("Error al subir imagen.");
    }

    //Calcular estado automáticamente
    $estado = ($stock > 0) ? "disponible" : "fuera_stock";

    $producto = new Producto(
        $nombre,
        $descripcion,
        $precio,
        $stock,
        $imagenNombre,
        $estado,
        $id_categoria
    );

    $productoDAO = new ProductoDAO();
    $resultado = $productoDAO->insertar($producto);

    if ($resultado) {
       header("Location: /proyecto-renta-silla-mesas/frontend/HTML/administrador/agregar_productos.html?success=1");
exit();
    } else {
        echo "Error al agregar producto ❌";
    }
}