<?php

require_once __DIR__ . '/../../../backend/config/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function usuario_autenticado(): bool {
    return isset($_SESSION['cliente']);
}

function obtener_cliente_autenticado(): ?array {
    return $_SESSION['cliente'] ?? null;
}

function requerir_autenticacion(): void {
    if (!usuario_autenticado()) {
        $destino = urlencode($_SERVER['REQUEST_URI'] ?? 'catalogo.php');
        header("Location: inicio_de_sesion.php?redirect={$destino}");
        exit;
    }
}

function cerrar_sesion_cliente(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function obtener_conexion_app(): PDO {
    return Conexion::conectar();
}

function obtener_carrito(): array {
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    return $_SESSION['carrito'];
}

function guardar_carrito(array $carrito): void {
    $_SESSION['carrito'] = $carrito;
}
