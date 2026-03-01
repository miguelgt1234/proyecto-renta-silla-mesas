<?php
require_once __DIR__ . '/../DAO/ProductoDAO.php';
require_once __DIR__ . '/../DAO/categoriaDAO.php';

class CatalogoController {

    private $productoDAO;
    private $categoriaDAO;

    public function __construct() {
        $this->productoDAO = new ProductoDAO();
        $this->categoriaDAO = new CategoriaDAO();
    }

    public function obtenerProductos($tipo = null) {
        return $this->productoDAO->obtenerProductosDisponibles($tipo);
    }

    public function obtenerCategorias() {
        return $this->categoriaDAO->obtenerTodas();
    }
}