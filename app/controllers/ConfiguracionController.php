<?php
// Archivo: /app/controllers/ConfiguracionController.php

require_once __DIR__ . '/../models/Usuario.php'; 
require_once __DIR__ . '/../models/Sucursal.php'; 

class ConfiguracionController {

    private $usuarioModel;
    private $sucursalModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
        $this->sucursalModel = new Sucursal();
    }

    /**
     * Obtiene la impresora del usuario logueado.
     */
    public function getPrinterConfig() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); return; }

        $id_usuario = $_SESSION['user_id'];
        $printer = $this->usuarioModel->getPrinter($id_usuario);

        echo json_encode(['success' => true, 'data' => ['impresora_tickets' => $printer]]);
    }

    /**
     * Actualiza la impresora del usuario.
     */
    public function updatePrinterConfig() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); return; }

        $data = (array)json_decode(file_get_contents('php://input'));
        $printerName = $data['impresora_tickets'] ?? null;
        $id_usuario = $_SESSION['user_id'];

        if ($this->usuarioModel->updatePrinter($id_usuario, $printerName)) {
            echo json_encode(['success' => true, 'message' => 'Impresora guardada exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar la impresora.']);
        }
    }

    /**
     * Obtiene la configuración de la sucursal del usuario logueado.
     */
    public function getBranchConfig() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); return; }

        $id_sucursal = $_SESSION['branch_id'];
        $config = $this->sucursalModel->getById($id_sucursal);

        if ($config) {
            echo json_encode(['success' => true, 'data' => $config]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No se encontró la configuración de la sucursal.']);
        }
    }

    /**
     * Actualiza la configuración de la sucursal.
     */
    public function updateBranchConfig() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); return; }

        $data = (array)json_decode(file_get_contents('php://input'));

        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El nombre de la sucursal es requerido.']);
            return;
        }

        $id_sucursal = $_SESSION['branch_id'];
        
        if ($this->sucursalModel->update($id_sucursal, $data)) {
            echo json_encode(['success' => true, 'message' => 'Configuración guardada exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar la configuración.']);
        }
    }
}
?>
