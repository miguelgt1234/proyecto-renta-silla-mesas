<?php

class Categoria {

    private $id_categoria;
    private $nombre;

    public function __construct($nombre) {
        $this->nombre = $nombre;
    }

    public function getIdCategoria() { return $this->id_categoria; }
    public function getNombre() { return $this->nombre; }

    public function setIdCategoria($id) { $this->id_categoria = $id; }
    public function setNombre($nombre) { $this->nombre = $nombre; }
}