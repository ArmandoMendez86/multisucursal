<?php
// Archivo: /app/controllers/VentaController.php

require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Cliente.php'; // Include Cliente model
require_once __DIR__ . '/../models/Producto.php'; // Include Producto model for inventory movements
require_once __DIR__ . '/../../config/Database.php'; // Include Database class

class VentaController
{
    private $ventaModel;
    private $clienteModel;
    private $productoModel;
    private $conn; // Declare connection property

    public function __construct()
    {
        $this->ventaModel = new Venta();
        $this->clienteModel = new Cliente();
        $this->productoModel = new Producto();
        $database = Database::getInstance(); // Get database instance
        $this->conn = $database->getConnection(); // Get the PDO connection
    }

    /**
     * Processes a sale that is being paid at the moment ('Completada' status).
     * Can also finalize a pending sale.
     */
    public function processSale()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['cart']) || !isset($data['total']) || empty($data['payments'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos de la venta incompletos o métodos de pago no proporcionados.']);
            return;
        }

        $data['id_usuario'] = $_SESSION['user_id'];
        $data['id_sucursal'] = $_SESSION['branch_id'];
        $data['id_cliente'] = $data['id_cliente'] ?? 1;
        $data['estado'] = 'Completada';
        $data['iva_aplicado'] = $data['iva_aplicado'] ?? 0;

        $creditPaymentAmount = 0;
        foreach ($data['payments'] as $payment) {
            if ($payment['method'] === 'Crédito') {
                $creditPaymentAmount += (float)$payment['amount'];
            }
        }

        // Validate credit if 'Crédito' payment method is used
        if ($creditPaymentAmount > 0) {
            $cliente = $this->clienteModel->getById($data['id_cliente']);
            if (!$cliente || $cliente['tiene_credito'] == 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El cliente seleccionado no tiene crédito habilitado.']);
                return;
            }
            $availableCredit = (float)$cliente['limite_credito'] - (float)$cliente['deuda_actual'];
            if ($creditPaymentAmount > $availableCredit) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Monto de crédito excede el límite disponible del cliente. Crédito disponible: $' . number_format($availableCredit, 2)]);
                return;
            }
        }

        try {
            $this->conn->beginTransaction(); // Start transaction for sale and inventory movements

            // First, check stock for all items before processing anything
            foreach ($data['cart'] as $item) {
                $product = $this->productoModel->getById($item['id'], $data['id_sucursal']);
                $current_stock = $product['stock'] ?? 0;
                if ($item['quantity'] > $current_stock) {
                    throw new Exception("Stock insuficiente para el producto: " . $product['nombre'] . ". Solicitado: " . $item['quantity'] . ", Disponible: " . $current_stock);
                }
            }

            if (isset($data['id_venta']) && !empty($data['id_venta'])) {
                $this->ventaModel->update($data);
                $saleId = $data['id_venta'];
                $message = 'Venta pendiente completada exitosamente.';
            } else {
                $saleId = $this->ventaModel->create($data);
                $message = 'Venta registrada exitosamente.';
            }

            // Update client's credit if 'Crédito' was used
            if ($creditPaymentAmount > 0) {
                $this->clienteModel->updateClientCredit($data['id_cliente'], $creditPaymentAmount);
            }

            // Record inventory movements and UPDATE STOCK for each item in the cart
            foreach ($data['cart'] as $item) {
                $product = $this->productoModel->getById($item['id'], $data['id_sucursal']);
                $old_stock = $product['stock'] ?? 0;
                $new_stock = $old_stock - $item['quantity'];

                // *** INICIO DE LA CORRECCIÓN ***
                // Step 1: Actually UPDATE the stock in the inventory table
                $stmt_update_stock = $this->conn->prepare(
                    "UPDATE inventario_sucursal SET stock = :new_stock WHERE id_producto = :id_producto AND id_sucursal = :id_sucursal"
                );
                $stmt_update_stock->bindParam(':new_stock', $new_stock, PDO::PARAM_INT);
                $stmt_update_stock->bindParam(':id_producto', $item['id'], PDO::PARAM_INT);
                $stmt_update_stock->bindParam(':id_sucursal', $data['id_sucursal'], PDO::PARAM_INT);
                $stmt_update_stock->execute();
                // *** FIN DE LA CORRECCIÓN ***

                // Step 2: Log the movement (this part was already correct)
                $this->productoModel->addInventoryMovement(
                    $item['id'],
                    $data['id_sucursal'],
                    $data['id_usuario'],
                    'venta',
                    $item['quantity'],
                    $old_stock,
                    $new_stock,
                    'Venta # ' . $saleId,
                    $saleId // Reference to sale ID
                );
            }

            $this->conn->commit(); // Commit transaction
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => $message, 'id_venta' => $saleId]);
        } catch (Exception $e) {
            $this->conn->rollBack(); // Rollback on error
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Saves a sale as 'Pendiente' without processing payment or affecting stock.
     * Or updates an existing sale as 'Pendiente'.
     */
    public function saveSale()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['cart']) || !isset($data['total']) || empty($data['id_cliente']) || $data['id_cliente'] == 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Para guardar una venta, debe seleccionar un cliente específico y tener productos en el carrito.']);
            return;
        }

        $data['id_usuario'] = $_SESSION['user_id'];
        $data['id_sucursal'] = $_SESSION['branch_id'];
        $data['estado'] = 'Pendiente';
        $data['iva_aplicado'] = $data['iva_aplicado'] ?? 0;

        try {
            if (isset($data['id_venta']) && !empty($data['id_venta'])) {
                $this->ventaModel->update($data);
                $saleId = $data['id_venta'];
                $message = 'Venta pendiente actualizada exitosamente.';
            } else {
                $saleId = $this->ventaModel->create($data);
                $message = 'Venta guardada como pendiente exitosamente.';
            }

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => $message, 'id_venta' => $saleId]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * Returns a list of sales with 'Pendiente' status for the current branch.
     */
    public function listPendingSales()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        try {
            $id_sucursal = $_SESSION['branch_id'];
            $ventas = $this->ventaModel->getPendingSales($id_sucursal);
            echo json_encode(['success' => true, 'data' => $ventas]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al listar ventas pendientes.']);
        }
    }

    /**
     * Returns the complete data of a specific sale to load it into the POS.
     */
    public function loadSale()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado.']);
            return;
        }

        $id_venta = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        try {
            $venta = $this->ventaModel->getSaleForPOS($id_venta);
            if ($venta) {
                echo json_encode(['success' => true, 'data' => $venta]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Venta pendiente no encontrada.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al cargar la venta.']);
        }
    }

    /**
     * Gets the details of a sale for ticket printing.
     */
    public function getTicketDetails()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado.']);
            return;
        }

        $id_venta = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

        try {
            $details = $this->ventaModel->getDetailsForTicket($id_venta);
            if ($details['venta']) {
                echo json_encode(['success' => true, 'data' => $details]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Venta no encontrada.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener los detalles de la venta.']);
        }
    }

    /**
     * Handles the request to delete a pending sale.
     */
    public function deletePendingSale()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id_venta'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado.']);
            return;
        }

        try {
            $id_venta = $data['id_venta'];
            $id_sucursal = $_SESSION['branch_id'];

            if ($this->ventaModel->deletePendingSale($id_venta, $id_sucursal)) {
                echo json_encode(['success' => true, 'message' => 'Venta pendiente eliminada exitosamente.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'No se encontró la venta pendiente o no pertenece a esta sucursal.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al eliminar la venta.']);
        }
    }

    /**
     * Generates an HTML view of the quote ready for PDF printing.
     */
    public function generateQuote()
    {
        // We don't send JSON, but HTML, so we don't set header.
        if (!isset($_SESSION['user_id'])) {
            die('Acceso no autorizado.');
        }
        if (empty($_GET['id'])) {
            die('ID de venta no proporcionado.');
        }

        $id_venta = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

        try {
            $data = $this->ventaModel->getDetailsForTicket($id_venta);
            if ($data && $data['venta']) {
                // Include the template and pass the data
                include __DIR__ . '/../views/cotizacion_template.php';
            } else {
                die('Cotización no encontrada.');
            }
        } catch (Exception $e) {
            die('Error al generar la cotización: ' . $e->getMessage());
        }
    }

    /**
     * Handles the request to cancel a completed sale.
     * Reverts stock and adjusts client credit.
     */
    public function cancelSale()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id_venta'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado para cancelar.']);
            return;
        }

        try {
            $id_venta = $data['id_venta'];
            $id_usuario_cancela = $_SESSION['user_id'];
            $id_sucursal = $_SESSION['branch_id'];

            if ($this->ventaModel->cancelSale($id_venta, $id_usuario_cancela, $id_sucursal)) {
                echo json_encode(['success' => true, 'message' => 'Venta cancelada exitosamente. Stock devuelto y crédito ajustado.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'No se pudo cancelar la venta. Verifique el ID y el estado.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al cancelar la venta: ' . $e->getMessage()]);
        }
    }
}
