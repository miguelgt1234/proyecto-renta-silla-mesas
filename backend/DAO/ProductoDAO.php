<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../modelos/producto.php';

class ProductoDAO {

    private $conexion;

    public function __construct() {
        $this->conexion = Conexion::conectar();
    }

    public function insertar(Producto $producto) {

        try {

            $sql = "INSERT INTO productos 
                    (nombre, descripcion, precio_renta_dia, stock_total, imagen, estado, id_categoria)
                    VALUES 
                    (:nombre, :descripcion, :precio, :stock, :imagen, :estado, :categoria)";

            $stmt = $this->conexion->prepare($sql);

            $stmt->bindValue(":nombre", $producto->getNombre());
            $stmt->bindValue(":descripcion", $producto->getDescripcion());
            $stmt->bindValue(":precio", $producto->getPrecioRentaDia());
            $stmt->bindValue(":stock", $producto->getStockTotal());
            $stmt->bindValue(":imagen", $producto->getImagen());
            $stmt->bindValue(":estado", $producto->getEstado());
            $stmt->bindValue(":categoria", $producto->getIdCategoria());

            $stmt->execute();

            return true;

        } catch (PDOException $e) {

            echo "Error al insertar producto: " . $e->getMessage();

            return false;
        }
    }
}