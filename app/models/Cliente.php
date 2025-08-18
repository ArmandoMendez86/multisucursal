<?php
// Archivo: /app/models/Cliente.php

require_once __DIR__ . '/../../config/Database.php';

class Cliente
{
    private $conn;
    private $table_name = "clientes";
    private $address_table = "cliente_direcciones";
    private $special_prices_table = "cliente_precios_especiales";
    private $payments_table = "pagos_clientes";

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function getAll()
    {
        $query = "SELECT id, nombre, telefono, email, deuda_actual FROM " . $this->table_name . " ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets a client and ALL their addresses and special prices.
     */
    public function getById($id)
    {
        // Get client data
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cliente) {
            // Get all addresses
            $cliente['direcciones'] = $this->getDirecciones($id);
            // Get special prices
            $cliente['precios_especiales'] = $this->getPreciosEspeciales($id);
        }

        return $cliente;
    }

    /**
     * Creates a client and saves their multiple addresses.
     */
    public function create($data)
    {
        $this->conn->beginTransaction();
        try {
            $query_cliente = "INSERT INTO " . $this->table_name . " (nombre, rfc, telefono, email, tiene_credito, limite_credito) VALUES (:nombre, :rfc, :telefono, :email, :tiene_credito, :limite_credito)";
            $stmt_cliente = $this->conn->prepare($query_cliente);
            $stmt_cliente->bindParam(':nombre', $data['nombre']);
            $stmt_cliente->bindParam(':rfc', $data['rfc']);
            $stmt_cliente->bindParam(':telefono', $data['telefono']);
            $stmt_cliente->bindParam(':email', $data['email']);
            $stmt_cliente->bindParam(':tiene_credito', $data['tiene_credito'], PDO::PARAM_BOOL);
            $stmt_cliente->bindParam(':limite_credito', $data['limite_credito']);
            $stmt_cliente->execute();
            $idCliente = $this->conn->lastInsertId();

            // Save addresses
            if (isset($data['direcciones']) && is_array($data['direcciones'])) {
                $this->guardarDirecciones($idCliente, $data['direcciones']);
            }

            // Save special prices
            if (isset($data['precios']) && is_array($data['precios'])) {
                $this->guardarPreciosEspeciales($idCliente, $data['precios']);
            }

            $this->conn->commit();
            return $idCliente;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Updates a client and synchronizes their multiple addresses.
     */
    public function update($id, $data)
    {
        $this->conn->beginTransaction();
        try {
            $query_cliente = "UPDATE " . $this->table_name . " SET nombre = :nombre, rfc = :rfc, telefono = :telefono, email = :email, tiene_credito = :tiene_credito, limite_credito = :limite_credito WHERE id = :id";
            $stmt_cliente = $this->conn->prepare($query_cliente);
            $stmt_cliente->bindParam(':id', $id);
            $stmt_cliente->bindParam(':nombre', $data['nombre']);
            $stmt_cliente->bindParam(':rfc', $data['rfc']);
            $stmt_cliente->bindParam(':telefono', $data['telefono']);
            $stmt_cliente->bindParam(':email', $data['email']);
            $stmt_cliente->bindParam(':tiene_credito', $data['tiene_credito'], PDO::PARAM_BOOL);
            $stmt_cliente->bindParam(':limite_credito', $data['limite_credito']);
            $stmt_cliente->execute();

            // Synchronize addresses
            if (isset($data['direcciones']) && is_array($data['direcciones'])) {
                $this->guardarDirecciones($id, $data['direcciones']);
            }

            // Synchronize special prices
            if (isset($data['precios']) && is_array($data['precios'])) {
                $this->guardarPreciosEspeciales($id, $data['precios']);
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
    /**
     * Deletes a client. The DB handles cascading deletion of addresses.
     */
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Searches clients by a term (name, rfc, phone).
     */
    public function search($term)
    {
        $query = "SELECT id, nombre, rfc, telefono 
                  FROM " . $this->table_name . " 
                  WHERE nombre LIKE :term 
                  OR rfc LIKE :term 
                  OR telefono LIKE :term
                  LIMIT 10";

        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $term . "%";
        $stmt->bindParam(':term', $searchTerm);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registers a payment for a client's debt.
     * @param int $idCliente
     * @param float $monto
     * @param string $metodoPago
     * @param int $idUsuario
     * @return bool
     * @throws Exception
     */
    public function registrarAbono($idCliente, $monto, $metodoPago, $idUsuario)
    {
        // First, check the current debt to ensure the payment is not greater.
        $cliente = $this->getById($idCliente);
        if (!$cliente) {
            throw new Exception("Cliente no encontrado.");
        }
        if ($monto > $cliente['deuda_actual']) {
            throw new Exception("El monto del abono no puede ser mayor a la deuda actual de $" . $cliente['deuda_actual']);
        }

        $this->conn->beginTransaction();
        try {
            // 1. Update client's debt
            $query_update = "UPDATE " . $this->table_name . " SET deuda_actual = deuda_actual - :monto WHERE id = :id_cliente";
            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->bindParam(':monto', $monto);
            $stmt_update->bindParam(':id_cliente', $idCliente);
            $stmt_update->execute();

            if ($stmt_update->rowCount() === 0) {
                throw new Exception("No se pudo actualizar la deuda del cliente.");
            }

            // 2. Insert payment record
            $query_insert = "INSERT INTO " . $this->payments_table . " (id_cliente, id_usuario, monto, metodo_pago) VALUES (:id_cliente, :id_usuario, :monto, :metodo_pago)";
            $stmt_insert = $this->conn->prepare($query_insert);
            $stmt_insert->bindParam(':id_cliente', $idCliente);
            $stmt_insert->bindParam(':id_usuario', $idUsuario);
            $stmt_insert->bindParam(':monto', $monto);
            $stmt_insert->bindParam(':metodo_pago', $metodoPago);
            $stmt_insert->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e; // Re-throw the exception to be caught by the controller
        }
    }


    /**
     * Gets all special prices for a client.
     */
    private function getPreciosEspeciales($id_cliente)
    {
        $query = "SELECT id_producto, precio_especial FROM " . $this->special_prices_table . " WHERE id_cliente = :id_cliente";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_cliente', $id_cliente);
        $stmt->execute();
        // Returns an associative array [id_producto => precio_especial]
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Saves (inserts or updates) special prices for a client.
     */
    private function guardarPreciosEspeciales($id_cliente, $precios)
    {
        // First, delete all previous prices for this client to avoid conflicts.
        $stmt_delete = $this->conn->prepare("DELETE FROM " . $this->special_prices_table . " WHERE id_cliente = :id_cliente");
        $stmt_delete->bindParam(':id_cliente', $id_cliente);
        $stmt_delete->execute();

        // Prepare the query to insert new prices.
        $query = "INSERT INTO " . $this->special_prices_table . " (id_cliente, id_producto, precio_especial) VALUES (:id_cliente, :id_producto, :precio_especial)";
        $stmt_insert = $this->conn->prepare($query);

        foreach ($precios as $id_producto => $precio) {
            // Only insert if the price is a valid positive number.
            $precio_limpio = filter_var($precio, FILTER_VALIDATE_FLOAT);
            if ($precio_limpio !== false && $precio_limpio > 0) {
                $stmt_insert->bindParam(':id_cliente', $id_cliente);
                $stmt_insert->bindParam(':id_producto', $id_producto);
                $stmt_insert->bindParam(':precio_especial', $precio_limpio);
                $stmt_insert->execute();
            }
        }
    }

    /**
     * Auxiliary method: Gets all addresses for a client.
     */
    private function getDirecciones($id_cliente)
    {
        $query = "SELECT * FROM " . $this->address_table . " WHERE id_cliente = :id_cliente";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_cliente', $id_cliente);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Auxiliary method: Synchronizes client addresses (deletes old ones and inserts new ones).
     */
    private function guardarDirecciones($id_cliente, $direcciones)
    {
        // Delete existing addresses to synchronize
        $stmt_delete = $this->conn->prepare("DELETE FROM " . $this->address_table . " WHERE id_cliente = :id_cliente");
        $stmt_delete->bindParam(':id_cliente', $id_cliente);
        $stmt_delete->execute();

        // Insert new addresses
        $query = "INSERT INTO " . $this->address_table . " (id_cliente, direccion, ciudad, estado, codigo_postal, principal) VALUES (:id_cliente, :direccion, :ciudad, :estado, :codigo_postal, :principal)";
        $stmt_insert = $this->conn->prepare($query);

        foreach ($direcciones as $dir) {
            if (!empty($dir['direccion'])) {
                $stmt_insert->bindParam(':id_cliente', $id_cliente);
                $stmt_insert->bindParam(':direccion', $dir['direccion']);
                $stmt_insert->bindParam(':ciudad', $dir['ciudad']);
                $stmt_insert->bindParam(':estado', $dir['estado']);
                $stmt_insert->bindParam(':codigo_postal', $dir['codigo_postal']);
                $stmt_insert->bindParam(':principal', $dir['principal'], PDO::PARAM_BOOL);
                $stmt_insert->execute();
            }
        }
    }

    /**
     * Sets a special price for a specific product for a client.
     * If a special price already exists, it updates it. Otherwise, it inserts it.
     * @param int $id_cliente
     * @param int $id_producto
     * @param float $precio_especial
     * @return bool
     */
    public function setSpecialPrice($id_cliente, $id_producto, $precio_especial)
    {
        // Use INSERT ... ON DUPLICATE KEY UPDATE to handle insertion/update
        $query = "INSERT INTO cliente_precios_especiales (id_cliente, id_producto, precio_especial)
                  VALUES (:id_cliente, :id_producto, :precio_especial)
                  ON DUPLICATE KEY UPDATE precio_especial = VALUES(precio_especial)";
        // The UNIQUE key `idx_cliente_producto` in `cliente_precios_especiales`
        // (id_cliente, id_producto) is crucial for ON DUPLICATE KEY UPDATE to work.
        // According to your multi.sql, this key already exists.

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt->bindParam(':precio_especial', $precio_especial, PDO::PARAM_STR); // Use PARAM_STR for decimal

        return $stmt->execute();
    }

    /**
     * Updates the client's credit limit and current debt after a credit payment.
     *
     * @param int $id_cliente The ID of the client.
     * @param float $amount The amount to deduct from credit limit and add to current debt.
     * @return bool True on success, false on failure.
     */
    public function updateClientCredit($id_cliente, $amount)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET limite_credito = limite_credito - :amount, 
                      deuda_actual = deuda_actual + :amount 
                  WHERE id = :id_cliente AND tiene_credito = 1 AND (limite_credito - deuda_actual) >= :amount";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    /**
     * Decreases the client's current debt. Used when a credit sale is cancelled.
     *
     * @param int $id_cliente The ID of the client.
     * @param float $amount The amount to decrease from current debt.
     * @return bool True on success, false on failure.
     */
    public function decreaseClientCredit($id_cliente, $amount)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET deuda_actual = deuda_actual - :amount 
                  WHERE id = :id_cliente AND tiene_credito = 1 AND deuda_actual >= :amount";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        
        return $stmt->execute() && $stmt->rowCount() > 0;
    }
}
