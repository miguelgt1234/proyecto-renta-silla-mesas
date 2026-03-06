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

    public function obtenerProductosDisponibles($tipo = null) {

    try {

        $sql = "SELECT p.*, c.nombre AS categoria
                FROM productos p
                INNER JOIN categorias c 
                ON p.id_categoria = c.id_categoria
                WHERE p.estado = 'disponible'";

        if ($tipo !== null && $tipo !== '') {
            $sql .= " AND p.id_categoria = :tipo";
        }

        $sql .= " ORDER BY p.precio_renta_dia DESC";

        $stmt = $this->conexion->prepare($sql);

        if ($tipo !== null && $tipo !== '') {
            $stmt->bindValue(":tipo", (int) $tipo, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        echo "Error al obtener productos: " . $e->getMessage();
        return [];
    }
    }

}