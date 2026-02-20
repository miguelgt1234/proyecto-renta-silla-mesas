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


    public function __construct(
        $nombre,
        $descripcion,
        $precio_renta_dia,
        $stock_total,
        $imagen,
        $estado,
        $id_categoria
    ) {
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->precio_renta_dia = $precio_renta_dia;
        $this->stock_total = $stock_total;
        $this->imagen = $imagen;
        $this->estado = $estado;
        $this->id_categoria = $id_categoria;
    }

    // Getters
    public function getNombre() {
        return $this->nombre;
    }

    public function getDescripcion() {
        return $this->descripcion;
    }

    public function getPrecioRentaDia() {
        return $this->precio_renta_dia;
    }

    public function getStockTotal() {
        return $this->stock_total;
    }

    public function getImagen() {
        return $this->imagen;
    }

    public function getEstado() {
        return $this->estado;
    }

    public function getIdCategoria() {
        return $this->id_categoria;
    }

    // Setters
    public function setEstado($estado) {
        $this->estado = $estado;
    }
}