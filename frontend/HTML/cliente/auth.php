<?php

require_once __DIR__ . '/../../../backend/config/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const BASE_CLIENTE = '/proyecto-renta-silla-mesas/frontend/HTML/cliente/';

function url_cliente(string $ruta = ''): string {
    return BASE_CLIENTE . ltrim($ruta, '/');
}

function url_login(string $redirect = ''): string {
    $url = url_cliente('inicio_de_sesion.php');
    if ($redirect !== '') {
        $url .= '?redirect=' . urlencode($redirect);
    }
    return $url;
}

function normalizar_redirect(?string $redirect): string {
    $default = url_cliente('catalogo.php');
    if (!$redirect) {
        return $default;
    }

    if (str_starts_with($redirect, '/proyecto-renta-silla-mesas/')) {
        return $redirect;
    }

    if (!str_contains($redirect, '://')) {
        return url_cliente(ltrim($redirect, '/'));
    }

    return $default;
}

function usuario_autenticado(): bool {
    return isset($_SESSION['cliente']);
}

function obtener_cliente_autenticado(): ?array {
    return $_SESSION['cliente'] ?? null;
}

function requerir_autenticacion(): void {
    if (!usuario_autenticado()) {
        $destino = $_SERVER['REQUEST_URI'] ?? url_cliente('catalogo.php');
        header('Location: ' . url_login($destino));
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
