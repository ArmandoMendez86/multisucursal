<?php
// Archivo: /app/models/Venta.php

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/Producto.php'; // Include Producto model to use its methods
require_once __DIR__ . '/Cliente.php'; // Include Cliente model to use its methods

class Venta
{
    private $conn;
    private $productoModel; // Declare productoModel
    private $clienteModel; // Declare clienteModel

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
        $this->productoModel = new Producto(); // Initialize productoModel
        $this->clienteModel = new Cliente(); // Initialize clienteModel
    }

    /**
     * Creates a new sale. Handles different statuses ('Completada', 'Pendiente')
     * and only deducts stock for completed sales.
     */
    public function create($data)
    {
        // Transaction is now handled in VentaController for consistency with inventory movements
        try {
            $estadoVenta = $data['estado'] ?? 'Completada';
            $idDireccion = $data['id_direccion_envio'] ?? null;
            $ivaAplicado = $data['iva_aplicado'] ?? 0;
            // FIX: Use null coalescing operator to handle missing 'payments' key for pending sales.
            $paymentsJson = !empty($data['payments']) ? json_encode($data['payments']) : null;

            // Step 2: Insert into the 'ventas' table
            $stmt_venta = $this->conn->prepare("INSERT INTO ventas (id_cliente, id_usuario, id_sucursal, id_direccion_envio, total, metodo_pago, iva_aplicado, estado) VALUES (:id_cliente, :id_usuario, :id_sucursal, :id_direccion_envio, :total, :metodo_pago, :iva_aplicado, :estado)");
            $stmt_venta->bindParam(':id_cliente', $data['id_cliente']);
            $stmt_venta->bindParam(':id_usuario', $data['id_usuario']);
            $stmt_venta->bindParam(':id_sucursal', $data['id_sucursal']);
            $stmt_venta->bindParam(':id_direccion_envio', $idDireccion);
            $stmt_venta->bindParam(':total', $data['total']);
            $stmt_venta->bindParam(':metodo_pago', $paymentsJson); // Store JSON of payments or NULL
            $stmt_venta->bindParam(':iva_aplicado', $ivaAplicado, PDO::PARAM_INT);
            $stmt_venta->bindParam(':estado', $estadoVenta);
            $stmt_venta->execute();
            $idVenta = $this->conn->lastInsertId();

            // Step 3: Insert each product into 'venta_detalles'
            $stmt_detalle = $this->conn->prepare("INSERT INTO venta_detalles (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (:id_venta, :id_producto, :cantidad, :precio_unitario, :subtotal)");

            foreach ($data['cart'] as $item) {
                $precio_unitario = $item['precio_final'];
                $subtotal_item = $item['quantity'] * $precio_unitario;

                $stmt_detalle->bindParam(':id_venta', $idVenta);
                $stmt_detalle->bindParam(':id_producto', $item['id']);
                $stmt_detalle->bindParam(':cantidad', $item['quantity']);
                $stmt_detalle->bindParam(':precio_unitario', $precio_unitario);
                $stmt_detalle->bindParam(':subtotal', $subtotal_item);
                $stmt_detalle->execute();
            }

            return $idVenta;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Updates an existing sale.
     * @param array $data The sale data to update, including id_venta.
     * @return bool True if the update was successful.
     * @throws Exception If a transaction error occurs.
     */
    public function update($data)
    {
        try {
            $idVenta = $data['id_venta'];
            $estadoVenta = $data['estado'] ?? 'Completada';
            $idDireccion = $data['id_direccion_envio'] ?? null;
            $idSucursal = $data['id_sucursal'];
            $ivaAplicado = $data['iva_aplicado'] ?? 0;
            // FIX: Use null coalescing operator to handle missing 'payments' key for pending sales.
            $paymentsJson = !empty($data['payments']) ? json_encode($data['payments']) : null;

            // 1. Get current sale to determine if status changes from Pending to Completed
            $stmt_current_sale = $this->conn->prepare("SELECT estado FROM ventas WHERE id = :id_venta FOR UPDATE");
            $stmt_current_sale->bindParam(':id_venta', $idVenta);
            $stmt_current_sale->execute();
            $currentSale = $stmt_current_sale->fetch(PDO::FETCH_ASSOC);

            if (!$currentSale) {
                throw new Exception("Venta no encontrada para actualizar.");
            }

            // 2. Delete existing sale details for the current sale
            $stmt_delete_details = $this->conn->prepare("DELETE FROM venta_detalles WHERE id_venta = :id_venta");
            $stmt_delete_details->bindParam(':id_venta', $idVenta);
            $stmt_delete_details->execute();

            // 3. Update the 'ventas' table
            $stmt_venta = $this->conn->prepare("UPDATE ventas SET id_cliente = :id_cliente, id_usuario = :id_usuario, id_sucursal = :id_sucursal, id_direccion_envio = :id_direccion_envio, total = :total, metodo_pago = :metodo_pago, iva_aplicado = :iva_aplicado, estado = :estado WHERE id = :id_venta");
            $stmt_venta->bindParam(':id_cliente', $data['id_cliente']);
            $stmt_venta->bindParam(':id_usuario', $data['id_usuario']);
            $stmt_venta->bindParam(':id_sucursal', $idSucursal);
            $stmt_venta->bindParam(':id_direccion_envio', $idDireccion);
            $stmt_venta->bindParam(':total', $data['total']);
            $stmt_venta->bindParam(':metodo_pago', $paymentsJson); // Store JSON of payments or NULL
            $stmt_venta->bindParam(':iva_aplicado', $ivaAplicado, PDO::PARAM_INT);
            $stmt_venta->bindParam(':estado', $estadoVenta);
            $stmt_venta->bindParam(':id_venta', $idVenta);
            $stmt_venta->execute();

            // 4. Insert each product into 'venta_detalles'
            $stmt_detalle = $this->conn->prepare("INSERT INTO venta_detalles (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (:id_venta, :id_producto, :cantidad, :precio_unitario, :subtotal)");

            foreach ($data['cart'] as $item) {
                $precio_unitario = $item['precio_final'];
                $subtotal_item = $item['quantity'] * $precio_unitario;

                $stmt_detalle->bindParam(':id_venta', $idVenta);
                $stmt_detalle->bindParam(':id_producto', $item['id']);
                $stmt_detalle->bindParam(':cantidad', $item['quantity']);
                $stmt_detalle->bindParam(':precio_unitario', $precio_unitario);
                $stmt_detalle->bindParam(':subtotal', $subtotal_item);
                $stmt_detalle->execute();
            }

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Gets a list of all sales with 'Pendiente' status for a branch.
     */
    public function getPendingSales($id_sucursal)
    {
        $query = "SELECT v.id, v.fecha, v.total, c.nombre as cliente_nombre 
                  FROM ventas v
                  JOIN clientes c ON v.id_cliente = c.id
                  WHERE v.estado = 'Pendiente' AND v.id_sucursal = :id_sucursal
                  ORDER BY v.fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets all sale data to load it into the POS.
     */
    public function getSaleForPOS($id_venta)
    {
        $resultado = [];
        // 1. Get sale data
        $query_venta = "SELECT v.*, c.nombre as cliente_nombre FROM ventas v JOIN clientes c ON v.id_cliente = c.id WHERE v.id = :id_venta AND v.estado = 'Pendiente'";
        $stmt_venta = $this->conn->prepare($query_venta);
        $stmt_venta->bindParam(':id_venta', $id_venta);
        $stmt_venta->execute();
        $resultado['header'] = $stmt_venta->fetch(PDO::FETCH_ASSOC);

        if (!$resultado['header']) return null;

        // 2. Get details (cart products)
        $query_items = "SELECT vd.*, p.nombre, p.precio_menudeo, p.precio_mayoreo, p.sku,
                               GROUP_CONCAT(pc.codigo_barras SEPARATOR ', ') AS codigos_barras
                        FROM venta_detalles vd
                        JOIN productos p ON vd.id_producto = p.id
                        LEFT JOIN producto_codigos pc ON p.id = pc.id_producto
                        WHERE vd.id_venta = :id_venta
                        GROUP BY vd.id, vd.id_venta, vd.id_producto, vd.cantidad, vd.precio_unitario, vd.subtotal, p.nombre, p.precio_menudeo, p.precio_mayoreo, p.sku";
        $stmt_items = $this->conn->prepare($query_items);
        $stmt_items->bindParam(':id_venta', $id_venta);
        $stmt_items->execute();
        $resultado['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        return $resultado;
    }


    /**
     * Gets all details of a sale, including the client's shipping address.
     */
    public function getDetailsForTicket($id_venta)
    {
        $resultado = [];

        // 1. Sale, branch and client address data
        $query_venta = "SELECT
                            v.id, v.fecha, v.total, v.metodo_pago, v.iva_aplicado, v.estado,
                            v.id_cliente, -- CAMBIO: Agregado id_cliente
                            c.nombre as cliente,
                            u.nombre as vendedor,
                            s.nombre as sucursal_nombre, s.direccion as sucursal_direccion, s.telefono as sucursal_telefono,
                            cd.direccion as cliente_direccion
                        FROM ventas v
                        JOIN clientes c ON v.id_cliente = c.id
                        JOIN usuarios u ON v.id_usuario = u.id
                        JOIN sucursales s ON v.id_sucursal = s.id
                        LEFT JOIN cliente_direcciones cd ON v.id_direccion_envio = cd.id
                        WHERE v.id = :id_venta";
        $stmt_venta = $this->conn->prepare($query_venta);
        $stmt_venta->bindParam(':id_venta', $id_venta);
        $stmt_venta->execute();
        $resultado['venta'] = $stmt_venta->fetch(PDO::FETCH_ASSOC);

        // 2. Sale items -- CAMBIO: Agregado vd.id_producto a la selección
        $query_items = "SELECT vd.id_producto, vd.cantidad, vd.precio_unitario, vd.subtotal, p.nombre as producto_nombre, p.sku
                        FROM venta_detalles vd
                        JOIN productos p ON vd.id_producto = p.id
                        WHERE vd.id_venta = :id_venta";
        $stmt_items = $this->conn->prepare($query_items);
        $stmt_items->bindParam(':id_venta', $id_venta);
        $stmt_items->execute();
        $resultado['items'] = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        return $resultado;
    }


    /**
     * Deletes a specific sale, but only if it is in 'Pendiente' status.
     * The database will handle cascading deletion of sale details.
     */
    public function deletePendingSale($id_venta, $id_sucursal)
    {
        $query = "DELETE FROM ventas WHERE id = :id_venta AND estado = 'Pendiente' AND id_sucursal = :id_sucursal";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_venta', $id_venta);
        $stmt->bindParam(':id_sucursal', $id_sucursal);

        if ($stmt->execute()) {
            // Returns true if at least one row was deleted
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    /**
     * Cancels a sale, returns stock to inventory, and adjusts client credit if applicable.
     * @param int $id_venta The ID of the sale to cancel.
     * @param int $id_usuario_cancela The ID of the user who cancels the sale.
     * @param int $id_sucursal The ID of the branch where the sale was made.
     * @throws Exception If the sale is not found, already cancelled, or an error occurs during the process.
     * @return bool True if the cancellation was successful.
     */
    public function cancelSale($id_venta, $id_usuario_cancela, $id_sucursal)
    {
        try {
            // Start transaction
            $this->conn->beginTransaction();

            // 1. Get sale details to check status and retrieve products/client info
            $saleDetails = $this->getDetailsForTicket($id_venta);

            if (!$saleDetails || !$saleDetails['venta']) {
                throw new Exception("Venta no encontrada.");
            }

            $venta = $saleDetails['venta'];
            $items = $saleDetails['items'];
            $id_cliente = $venta['id_cliente'];
            $total_venta = $venta['total']; // Not directly used in this method, but good to have.
            $metodo_pago_json = $venta['metodo_pago']; // This is a JSON string

            if ($venta['estado'] === 'Cancelada') {
                throw new Exception("La venta ya ha sido cancelada previamente.");
            }
            if ($venta['estado'] === 'Pendiente' || $venta['estado'] === 'Cotizacion') {
                throw new Exception("Las ventas pendientes o cotizaciones no pueden ser canceladas por este método. Deben ser eliminadas.");
            }

            // 2. Return stock for each product in the sale
            foreach ($items as $item) {
                $product = $this->productoModel->getById($item['id_producto'], $id_sucursal);
                $old_stock = $product['stock'] ?? 0;
                $new_stock = $old_stock + $item['cantidad'];

                // Corregir la llamada a updateStock para pasar todos los argumentos
                $this->productoModel->updateStock(
                    $item['id_producto'],
                    $id_sucursal,
                    $new_stock,
                    'devolucion', // tipo_movimiento
                    $item['cantidad'], // cantidad_movida
                    $old_stock, // stock_anterior
                    'Devolución por cancelación de Venta #' . $id_venta, // motivo
                    $id_venta // referencia_id
                );
            }

            // 3. Adjust client's credit if the sale was paid with 'Crédito'
            if ($metodo_pago_json) {
                $payments = json_decode($metodo_pago_json, true);
                $creditPaymentAmount = 0;
                foreach ($payments as $payment) {
                    if ($payment['method'] === 'Crédito') {
                        $creditPaymentAmount += (float)$payment['amount'];
                    }
                }

                if ($creditPaymentAmount > 0) {
                    $cliente = $this->clienteModel->getById($id_cliente);
                    if ($cliente && $cliente['tiene_credito'] == 1) {
                        // Subtract the credit amount from the client's current debt
                        $this->clienteModel->updateClientCredit($id_cliente, -$creditPaymentAmount);
                    }
                }
            }

            // 4. Update sale status to 'Cancelada' and record cancellation details
            $query_cancel = "UPDATE ventas SET estado = 'Cancelada', id_usuario_cancela = :id_usuario_cancela, fecha_cancelacion = NOW() WHERE id = :id_venta AND id_sucursal = :id_sucursal";
            $stmt_cancel = $this->conn->prepare($query_cancel);
            $stmt_cancel->bindParam(':id_usuario_cancela', $id_usuario_cancela);
            $stmt_cancel->bindParam(':id_venta', $id_venta);
            $stmt_cancel->bindParam(':id_sucursal', $id_sucursal);
            $stmt_cancel->execute();

            $this->conn->commit(); // Commit transaction
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack(); // Rollback on error
            throw $e;
        }
    }
}
