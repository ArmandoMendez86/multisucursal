<?php
// Archivo: /app/models/Sucursal.php

require_once __DIR__ . '/../../config/Database.php';

class Sucursal
{
    private $conn;
    private $table_name = "sucursales";

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function getAll()
    {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $query = "INSERT INTO " . $this->table_name . " (nombre, direccion, telefono, email) VALUES (:nombre, :direccion, :telefono, :email)";
        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $nombre = htmlspecialchars(strip_tags($data['nombre']));
        $direccion = htmlspecialchars(strip_tags($data['direccion']));
        $telefono = htmlspecialchars(strip_tags($data['telefono']));
        $email = htmlspecialchars(strip_tags($data['email']));

        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':direccion', $direccion);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':email', $email);

        return $stmt->execute();
    }

    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table_name . " SET nombre = :nombre, direccion = :direccion, telefono = :telefono, email = :email WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $nombre = htmlspecialchars(strip_tags($data['nombre']));
        $direccion = htmlspecialchars(strip_tags($data['direccion']));
        $telefono = htmlspecialchars(strip_tags($data['telefono']));
        $email = htmlspecialchars(strip_tags($data['email']));
        // El ID ya se recibe como un parámetro seguro, no es necesario extraerlo de $data

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':direccion', $direccion);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':email', $email);

        return $stmt->execute();
    }

    public function delete($id)
    {
        // Considerar restricciones de clave foránea. Si una sucursal tiene usuarios o inventario, no se podrá eliminar.
        // Se podría implementar un borrado lógico (campo 'activo') en un futuro.
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
