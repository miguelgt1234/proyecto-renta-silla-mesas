<?php

class Producto {

    private $id_producto;
    private $nombre;
    private $descripcion;
    private $precio_renta_dia;
    private $stock_total;
    private $imagen;
    private $estado;
    private $id_categoria;

    public function __construct($nombre, $descripcion, $precio, $stock, $imagen, $id_categoria) {
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->precio_renta_dia = $precio;
        $this->stock_total = $stock;
        $this->imagen = $imagen;
        $this->estado = "disponible";
        $this->id_categoria = $id_categoria;
    }

    // GETTERS
    public function getNombre() { return $this->nombre; }
    public function getDescripcion() { return $this->descripcion; }
    public function getPrecioRentaDia() { return $this->precio_renta_dia; }
    public function getStockTotal() { return $this->stock_total; }
    public function getImagen() { return $this->imagen; }
    public function getEstado() { return $this->estado; }
    public function getIdCategoria() { return $this->id_categoria; }

    // SETTERS
    public function setNombre($nombre) { $this->nombre = $nombre; }
    public function setDescripcion($descripcion) { $this->descripcion = $descripcion; }
    public function setPrecio($precio) { $this->precio_renta_dia = $precio; }
    public function setStock($stock) { $this->stock_total = $stock; }
    public function setImagen($imagen) { $this->imagen = $imagen; }
    public function setEstado($estado) { $this->estado = $estado; }
    public function setIdCategoria($id_categoria) { $this->id_categoria = $id_categoria; }
}