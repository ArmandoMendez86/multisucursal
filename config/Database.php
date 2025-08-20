<?php
// Archivo: /config/Database.php

class Database
{

    //Localhost

    /*  
     private $host = 'localhost';
    private $db_name = 'update_mega';
     private $username = 'root';
     private $password = 'Linux861215';
     private $conn; 
     */
    private $host = 'localhost';
    private $db_name = 'megapart_update';
    private $username = 'megapart_update';
    private $password = 'Update861215#-';
    private $conn;



    private static $instance = null;

    private function __construct()
    {
        $this->conn = null;
        try {
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8';
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("SET time_zone = '-06:00'");
        } catch (PDOException $e) {
            echo 'Error de Conexión: ' . $e->getMessage();
            exit;
        }
    }

    private function __clone() {}

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }
}
