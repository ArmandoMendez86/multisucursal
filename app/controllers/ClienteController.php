<?php
// Archivo: /app/controllers/ClienteController.php

require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Producto.php';

class ClienteController
{

    private $clienteModel;
    private $productoModel;

    public function __construct()
    {
        $this->clienteModel = new Cliente();
        $this->productoModel = new Producto();
    }

    public function getAll()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        try {
            $clientes = $this->clienteModel->getAll();
            echo json_encode(['success' => true, 'data' => $clientes]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener los clientes.']);
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
            echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado.']);
            return;
        }

        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        $cliente = $this->clienteModel->getById($id);

        if ($cliente) {
            echo json_encode(['success' => true, 'data' => $cliente]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Cliente no encontrado.']);
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

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El nombre del cliente es requerido.']);
            return;
        }

        $clientData = [
            'nombre' => $data['nombre'],
            'rfc' => $data['rfc'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'email' => $data['email'] ?? null,
            'tiene_credito' => $data['tiene_credito'] ?? 0,
            'limite_credito' => $data['limite_credito'] ?? 0.00,
            'direcciones' => $data['direcciones'] ?? [],
            'precios' => $data['precios'] ?? []
        ];

        try {
            $newClientId = $this->clienteModel->create($clientData);
            if ($newClientId) {
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Cliente creado exitosamente.', 'id' => $newClientId]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo crear el cliente.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
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

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado.']);
            return;
        }

        $id = $data['id'];
        $clientData = [
            'nombre' => $data['nombre'],
            'rfc' => $data['rfc'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'email' => $data['email'] ?? null,
            'tiene_credito' => $data['tiene_credito'] ?? 0,
            'limite_credito' => $data['limite_credito'] ?? 0.00,
            'direcciones' => $data['direcciones'] ?? [],
            'precios' => $data['precios'] ?? []
        ];

        try {
            if ($this->clienteModel->update($id, $clientData)) {
                echo json_encode(['success' => true, 'message' => 'Cliente actualizado exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el cliente.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
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

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado.']);
            return;
        }

        $id = $data['id'];
        if ($this->clienteModel->delete($id)) {
            echo json_encode(['success' => true, 'message' => 'Cliente eliminado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el cliente.']);
        }
    }
    
    public function registrarAbono()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado. Inicie sesión.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $idCliente = $data['id_cliente'] ?? null;
        $monto = $data['monto'] ?? null;
        $metodoPago = $data['metodo_pago'] ?? null;
        $idUsuario = $_SESSION['user_id'];

        if (empty($idCliente) || !isset($monto) || !is_numeric($monto) || $monto <= 0 || empty($metodoPago)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos para registrar el abono.']);
            return;
        }

        try {
            if ($this->clienteModel->registrarAbono($idCliente, $monto, $metodoPago, $idUsuario)) {
                echo json_encode(['success' => true, 'message' => 'Abono registrado exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo registrar el abono.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function search()
    {
        header('Content-Type: application/json');
        // Select2 envía el término de búsqueda en el parámetro 'term'
        $term = $_GET['term'] ?? '';

        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            // Select2 espera 'results' en la respuesta, incluso si hay un error.
            echo json_encode(['results' => [], 'message' => 'Acceso no autorizado.']);
            return;
        }
        
        // Si el término de búsqueda está vacío, devuelve el cliente por defecto
        if (empty($term)) {
            // Select2 espera 'results' con un array de objetos {id, text}
            echo json_encode(['results' => [['id' => 1, 'text' => 'Público en General']]]);
            return;
        }

        $term = htmlspecialchars(strip_tags($term));
        $clientes = $this->clienteModel->search($term);

        // Formatear los resultados para Select2
        $results = array_map(function($cliente) {
            return ['id' => $cliente['id'], 'text' => $cliente['nombre']];
        }, $clientes);

        // Envía la respuesta con la clave 'results'
        echo json_encode(['results' => $results]);
    }

    public function getProductosParaPreciosEspeciales()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        try {
            $productos = $this->productoModel->getAllSimple();
            echo json_encode(['success' => true, 'data' => $productos]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener los productos.']);
        }
    }

    public function saveSpecialClientPrice()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $idCliente = $data['id_cliente'] ?? null;
        $idProducto = $data['id_producto'] ?? null;
        $precioEspecial = $data['precio_especial'] ?? null;

        if (empty($idCliente) || empty($idProducto) || !isset($precioEspecial)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos para guardar precio especial.']);
            return;
        }

        try {
            $this->clienteModel->setSpecialPrice($idCliente, $idProducto, $precioEspecial); // Utiliza el método existente
            echo json_encode(['success' => true, 'message' => 'Precio especial guardado exitosamente.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al guardar precio especial: ' . $e->getMessage()]);
        }
    }
}
