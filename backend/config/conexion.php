<?php

class Conexion {

    private static $host = "localhost";
    private static $dbname = "renta_sillas_mesas";
    private static $username = "root";
    private static $password = "";

    public static function conectar() {

        try {

            $conexion = new PDO(
                "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8",
                self::$username,
                self::$password
            );

            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $conexion;

        } catch (PDOException $e) {

            die("Error de conexión: " . $e->getMessage());
        }
    }
}