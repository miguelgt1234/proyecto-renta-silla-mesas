<?php

require_once __DIR__ . '/CatalogoController.php';

$controller = new CatalogoController();

$tipoSeleccionado = $_GET['tipo'] ?? null;

$productos = $controller->obtenerProductos($tipoSeleccionado);
$categorias = $controller->obtenerCategorias();

/* Cargar vista */
require_once __DIR__ . '/../../frontend/HTML/cliente/catalogo.php';