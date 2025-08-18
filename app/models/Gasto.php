<?php
// Archivo: /app/models/Gasto.php

require_once __DIR__ . '/../../config/Database.php';

class Gasto {
    private $conn;
    private $table_name = "gastos";

    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function getAllBySucursal($id_sucursal) {
        $query = "SELECT id, fecha, categoria_gasto, descripcion, monto 
                  FROM " . $this->table_name . " 
                  WHERE id_sucursal = :id_sucursal 
                  ORDER BY fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " (id_usuario, id_sucursal, categoria_gasto, descripcion, monto) VALUES (:id_usuario, :id_sucursal, :categoria_gasto, :descripcion, :monto)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_usuario', $data['id_usuario']);
        $stmt->bindParam(':id_sucursal', $data['id_sucursal']);
        $stmt->bindParam(':categoria_gasto', $data['categoria_gasto']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        $stmt->bindParam(':monto', $data['monto']);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * --- NUEVO MÉTODO ---
     * Obtiene un solo gasto por su ID.
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * --- NUEVO MÉTODO ---
     * Actualiza un registro de gasto.
     */
    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " SET 
                    categoria_gasto = :categoria_gasto,
                    descripcion = :descripcion,
                    monto = :monto
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':categoria_gasto', $data['categoria_gasto']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        $stmt->bindParam(':monto', $data['monto']);
        return $stmt->execute();
    }

    /**
     * --- NUEVO MÉTODO ---
     * Elimina un registro de gasto.
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
