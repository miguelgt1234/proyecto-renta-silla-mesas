<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/proyecto-renta-silla-mesas/backend/config/conexion.php';

class CategoriaDAO {

    private $conexion;

    public function __construct() {
        $this->conexion = Conexion::conectar();
    }

    public function obtenerTodas() {

        try {

            $sql = "SELECT * FROM categorias";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            echo "Error al obtener categorias: " . $e->getMessage();
            return [];
        }
    }
}