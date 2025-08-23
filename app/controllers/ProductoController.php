<?php
// Archivo: /app/controllers/ProductoController.php

require_once __DIR__ . '/../models/Producto.php';

class ProductoController
{
    private $productoModel;

    public function __construct()
    {
        $this->productoModel = new Producto();
    }

    // ... (getProductForPOS, getByBarcode, create, etc. se mantienen igual)

    public function getProductForPOS()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        if (!isset($_GET['id_producto']) || !isset($_GET['id_cliente'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Faltan parámetros (producto o cliente).']);
            return;
        }

        $id_producto = filter_var($_GET['id_producto'], FILTER_SANITIZE_NUMBER_INT);
        $id_cliente = filter_var($_GET['id_cliente'], FILTER_SANITIZE_NUMBER_INT);
        $id_sucursal = $_SESSION['branch_id'];

        try {
            $producto = $this->productoModel->getForPOS($id_producto, $id_sucursal, $id_cliente);

            if ($producto) {
                echo json_encode(['success' => true, 'data' => $producto]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Producto no encontrado o sin stock.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * @deprecated Este método ya no se usa para la tabla principal. Se reemplaza por getProductsServerSide.
     */
    public function getAll()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        try {
            $id_sucursal = $_SESSION['branch_id'];
            $productos = $this->productoModel->getAll($id_sucursal);
            echo json_encode(['success' => true, 'data' => $productos]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener los productos: ' . $e->getMessage()]);
        }
    }

    // --- INICIO: Nuevo método para Server-Side ---
    public function getProductsServerSide()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $params = $_REQUEST;
        $id_sucursal = $_SESSION['branch_id'];

        try {
            $result = $this->productoModel->getAllServerSide($id_sucursal, $params);

            $json_data = [
                "draw"            => intval($params['draw']),
                "recordsTotal"    => intval($result['recordsTotal']),
                "recordsFiltered" => intval($result['recordsFiltered']),
                "data"            => $result['data']
            ];

            echo json_encode($json_data);
        } catch (Exception $e) {
            http_response_code(500);
            // Para depuración, es útil ver el error. En producción, se puede cambiar por un mensaje genérico.
            echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }
    // --- FIN: Nuevo método para Server-Side ---

    public function getByBarcode()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        if (!isset($_GET['code'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Código no proporcionado.']);
            return;
        }

        $code = htmlspecialchars(strip_tags($_GET['code']));
        $id_sucursal = $_SESSION['branch_id'];
        $producto = $this->productoModel->findByBarcodeOrSku($code, $id_sucursal);

        if ($producto) {
            echo json_encode(['success' => true, 'data' => $producto]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
        }
    }

    public function create()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        $data = (array) json_decode(file_get_contents('php://input'), true);
        if (empty($data['nombre']) || empty($data['sku'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
            return;
        }
        try {
            $id_sucursal = $_SESSION['branch_id'];
            $newProductId = $this->productoModel->create($data, $id_sucursal);
            if ($newProductId) {
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Producto creado y asignado a sucursal.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo crear el producto.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al crear el producto: ' . $e->getMessage()]);
        }
    }

    public function getById()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de producto no proporcionado.']);
            return;
        }

        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        $id_sucursal = $_SESSION['branch_id'];
        $producto = $this->productoModel->getById($id, $id_sucursal);

        if ($producto) {
            echo json_encode(['success' => true, 'data' => $producto]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
        }
    }

    public function update()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = (array) json_decode(file_get_contents('php://input'), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de producto no proporcionado.']);
            return;
        }

        $id = $data['id'];
        $id_sucursal = $_SESSION['branch_id'];
        try {
            if ($this->productoModel->update($id, $data, $id_sucursal)) {
                echo json_encode(['success' => true, 'message' => 'Producto actualizado exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el producto.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el producto: ' . $e->getMessage()]);
        }
    }

    public function delete()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = (array) json_decode(file_get_contents('php://input'));
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de producto no proporcionado.']);
            return;
        }

        $id = $data['id'];
        if ($this->productoModel->delete($id)) {
            echo json_encode(['success' => true, 'message' => 'Producto eliminado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el producto.']);
        }
    }

    public function adjustStock()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $id_producto = $data['id_producto'] ?? null;
        $new_stock = $data['new_stock'] ?? null;
        $tipo_movimiento = $data['tipo_movimiento'] ?? 'ajuste';
        $cantidad_movida = $data['cantidad_movida'] ?? 0;
        $motivo = $data['motivo'] ?? 'Ajuste manual';
        $stock_anterior = $data['stock_anterior'] ?? 0;

        if (is_null($id_producto) || is_null($new_stock) || !is_numeric($new_stock) || $new_stock < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos de ajuste de stock incompletos o inválidos.']);
            return;
        }

        $id_sucursal = $_SESSION['branch_id'];
        if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Super' && !empty($data['id_sucursal'])) {
            $id_sucursal = filter_var($data['id_sucursal'], FILTER_SANITIZE_NUMBER_INT);
        }

        try {
            $success = $this->productoModel->updateStock(
                $id_producto,
                $id_sucursal,
                $new_stock,
                $tipo_movimiento,
                $cantidad_movida,
                $stock_anterior,
                $motivo
            );

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Stock ajustado exitosamente y movimiento registrado.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo ajustar el stock.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al ajustar stock: ' . $e->getMessage()]);
        }
    }
    public function getInventoryMovements()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $id_sucursal = $_SESSION['branch_id'];
        try {
            $movements = $this->productoModel->getInventoryMovements($id_sucursal);
            echo json_encode(['success' => true, 'data' => $movements]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener el historial de movimientos: ' . $e->getMessage()]);
        }
    }

    public function getStockAcrossBranches()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        if (!isset($_GET['term']) || empty(trim($_GET['term']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Término de búsqueda no proporcionado.']);
            return;
        }

        $searchTerm = trim(htmlspecialchars(strip_tags($_GET['term'])));

        try {
            $results = $this->productoModel->findStockInAllBranches($searchTerm);

            $groupedResults = [];
            foreach ($results as $row) {
                $sku = $row['sku'];
                if (!isset($groupedResults[$sku])) {
                    $groupedResults[$sku] = [
                        'sku' => $sku,
                        'producto_nombre' => $row['producto_nombre'],
                        'sucursales' => []
                    ];
                }
                $groupedResults[$sku]['sucursales'][] = [
                    'nombre' => $row['sucursal_nombre'],
                    'stock' => (int) $row['stock']
                ];
            }

            echo json_encode(['success' => true, 'data' => array_values($groupedResults)]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }
    public function searchProducts()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        if ($limit <= 0 || $limit > 200) $limit = 50;
        if ($offset < 0) $offset = 0;

        try {
            $id_sucursal = $_SESSION['branch_id'];
            $productos = $this->productoModel->search($id_sucursal, $q, $limit, $offset);
            echo json_encode(['success' => true, 'data' => $productos]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al buscar productos.']);
        }
    }
}
