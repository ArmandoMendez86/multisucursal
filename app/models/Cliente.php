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

    public function getClientsForDataTable($request)
    {
        $baseQuery = " FROM " . $this->table_name;
        $columns = ['nombre', 'telefono', 'email', 'deuda_actual'];

        $searchQuery = "";
        if (!empty($request['search']['value'])) {
            $searchValue = $request['search']['value'];
            $searchQuery = " WHERE (nombre LIKE :search_value OR telefono LIKE :search_value OR email LIKE :search_value)";
        }

        $stmtTotal = $this->conn->prepare("SELECT COUNT(id) as total " . $baseQuery);
        $stmtTotal->execute();
        $recordsTotal = $stmtTotal->fetchColumn();

        $stmtFiltered = $this->conn->prepare("SELECT COUNT(id) as total " . $baseQuery . $searchQuery);
        if (!empty($searchQuery)) {
            $stmtFiltered->bindValue(':search_value', '%' . $searchValue . '%', PDO::PARAM_STR);
        }
        $stmtFiltered->execute();
        $recordsFiltered = $stmtFiltered->fetchColumn();

        $orderQuery = "";
        if (isset($request['order']) && count($request['order'])) {
            $orderColumnIndex = $request['order'][0]['column'];
            $orderColumnName = $columns[$orderColumnIndex];
            $orderDir = $request['order'][0]['dir'];
            $orderQuery = " ORDER BY " . $orderColumnName . " " . $orderDir;
        } else {
            $orderQuery = " ORDER BY nombre ASC";
        }
        
        $limitQuery = "";
        if (isset($request['length']) && $request['length'] != -1) {
            $limitQuery = " LIMIT :limit OFFSET :offset";
        }

        $query = "SELECT id, nombre, telefono, email, deuda_actual " . $baseQuery . $searchQuery . $orderQuery . $limitQuery;
        $stmtData = $this->conn->prepare($query);

        if (!empty($searchQuery)) {
            $stmtData->bindValue(':search_value', '%' . $searchValue . '%', PDO::PARAM_STR);
        }
        if (!empty($limitQuery)) {
            $stmtData->bindValue(':limit', (int)$request['length'], PDO::PARAM_INT);
            $stmtData->bindValue(':offset', (int)$request['start'], PDO::PARAM_INT);
        }
        
        $stmtData->execute();
        $clients = $stmtData->fetchAll(PDO::FETCH_ASSOC);
        
        $data = [];
        foreach ($clients as $client) {
            $deuda = (float)($client['deuda_actual'] ?? 0);
            $hasDebt = $deuda > 0;

            $acciones = '';
            if ($hasDebt) {
                $acciones .= '<button class="payment-btn text-green-400 hover:text-green-300 mr-3" title="Registrar Abono"><i class="fas fa-dollar-sign"></i></button>';
            }
            $acciones .= '<button class="edit-btn text-blue-400 hover:text-blue-300 mr-3" title="Editar"><i class="fas fa-pencil-alt"></i></button>';
            $acciones .= '<button class="delete-btn text-red-500 hover:text-red-400" title="Eliminar"><i class="fas fa-trash-alt"></i></button>';

            $data[] = [
                "id" => $client['id'],
                "nombre" => htmlspecialchars($client['nombre']),
                "telefono" => htmlspecialchars($client['telefono'] ?? 'N/A'),
                "email" => htmlspecialchars($client['email'] ?? 'N/A'),
                // --- MODIFICACIÓN: Enviar el número en crudo ---
                "deuda_actual" => $deuda,
                "acciones" => $acciones
            ];
        }

        return [
            "draw" => intval($request['draw']),
            "recordsTotal" => intval($recordsTotal),
            "recordsFiltered" => intval($recordsFiltered),
            "data" => $data
        ];
    }

    public function getAll()
    {
        $query = "SELECT id, nombre, telefono, email, deuda_actual FROM " . $this->table_name . " ORDER BY nombre ASC";
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
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cliente) {
            $cliente['direcciones'] = $this->getDirecciones($id);
            $cliente['precios_especiales'] = $this->getPreciosEspeciales($id);
        }
        return $cliente;
    }

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
            if (isset($data['direcciones']) && is_array($data['direcciones'])) {
                $this->guardarDirecciones($idCliente, $data['direcciones']);
            }
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
            if (isset($data['direcciones']) && is_array($data['direcciones'])) {
                $this->guardarDirecciones($id, $data['direcciones']);
            }
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

    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function search($term)
    {
        $query = "SELECT id, nombre, rfc, telefono FROM " . $this->table_name . " WHERE nombre LIKE :term OR rfc LIKE :term OR telefono LIKE :term LIMIT 10";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $term . "%";
        $stmt->bindParam(':term', $searchTerm);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarAbono($idCliente, $monto, $metodoPago, $idUsuario)
    {
        $cliente = $this->getById($idCliente);
        if (!$cliente) {
            throw new Exception("Cliente no encontrado.");
        }
        if ($monto > $cliente['deuda_actual']) {
            throw new Exception("El monto del abono no puede ser mayor a la deuda actual de $" . $cliente['deuda_actual']);
        }
        $this->conn->beginTransaction();
        try {
            $query_update = "UPDATE " . $this->table_name . " SET deuda_actual = deuda_actual - :monto WHERE id = :id_cliente";
            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->bindParam(':monto', $monto);
            $stmt_update->bindParam(':id_cliente', $idCliente);
            $stmt_update->execute();
            if ($stmt_update->rowCount() === 0) {
                throw new Exception("No se pudo actualizar la deuda del cliente.");
            }
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
            throw $e;
        }
    }

    private function getPreciosEspeciales($id_cliente)
    {
        $query = "SELECT id_producto, precio_especial FROM " . $this->special_prices_table . " WHERE id_cliente = :id_cliente";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_cliente', $id_cliente);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    private function guardarPreciosEspeciales($id_cliente, $precios)
    {
        $stmt_delete = $this->conn->prepare("DELETE FROM " . $this->special_prices_table . " WHERE id_cliente = :id_cliente");
        $stmt_delete->bindParam(':id_cliente', $id_cliente);
        $stmt_delete->execute();
        $query = "INSERT INTO " . $this->special_prices_table . " (id_cliente, id_producto, precio_especial) VALUES (:id_cliente, :id_producto, :precio_especial)";
        $stmt_insert = $this->conn->prepare($query);
        foreach ($precios as $id_producto => $precio) {
            $precio_limpio = filter_var($precio, FILTER_VALIDATE_FLOAT);
            if ($precio_limpio !== false && $precio_limpio > 0) {
                $stmt_insert->bindParam(':id_cliente', $id_cliente);
                $stmt_insert->bindParam(':id_producto', $id_producto);
                $stmt_insert->bindParam(':precio_especial', $precio_limpio);
                $stmt_insert->execute();
            }
        }
    }

    private function getDirecciones($id_cliente)
    {
        $query = "SELECT * FROM " . $this->address_table . " WHERE id_cliente = :id_cliente";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_cliente', $id_cliente);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function guardarDirecciones($id_cliente, $direcciones)
    {
        $stmt_delete = $this->conn->prepare("DELETE FROM " . $this->address_table . " WHERE id_cliente = :id_cliente");
        $stmt_delete->bindParam(':id_cliente', $id_cliente);
        $stmt_delete->execute();
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

    public function setSpecialPrice($id_cliente, $id_producto, $precio_especial)
    {
        $query = "INSERT INTO cliente_precios_especiales (id_cliente, id_producto, precio_especial) VALUES (:id_cliente, :id_producto, :precio_especial) ON DUPLICATE KEY UPDATE precio_especial = VALUES(precio_especial)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt->bindParam(':precio_especial', $precio_especial, PDO::PARAM_STR);
        return $stmt->execute();
    }

    public function updateClientCredit($id_cliente, $amount)
    {
        $query = "UPDATE " . $this->table_name . " SET limite_credito = limite_credito - :amount, deuda_actual = deuda_actual + :amount WHERE id = :id_cliente AND tiene_credito = 1 AND (limite_credito - deuda_actual) >= :amount";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    public function decreaseClientCredit($id_cliente, $amount)
    {
        $query = "UPDATE " . $this->table_name . " SET deuda_actual = deuda_actual - :amount WHERE id = :id_cliente AND tiene_credito = 1 AND deuda_actual >= :amount";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }
}
