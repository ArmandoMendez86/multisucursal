<?php
// Archivo: /app/controllers/ReporteController.php

require_once __DIR__ . '/../models/Reporte.php';

class ReporteController
{

    private $reporteModel;

    public function __construct()
    {
        $this->reporteModel = new Reporte();
    }

   public function getVentas() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); return; }
        if (empty($_GET['start']) || empty($_GET['end'])) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Rango de fechas no proporcionado.']); return; }

        $id_sucursal = $_SESSION['branch_id'];
        $fecha_inicio = htmlspecialchars(strip_tags($_GET['start']));
        $fecha_fin = htmlspecialchars(strip_tags($_GET['end']));

        // --- CAMBIO: Lógica de roles para ventas ---
        $id_vendedor = null;
        if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Vendedor') {
            $id_vendedor = $_SESSION['user_id'];
        }

        try {
            // Se pasa el id_vendedor (que puede ser null) al modelo
            $ventas = $this->reporteModel->getVentasPorFecha($id_sucursal, $fecha_inicio, $fecha_fin, $id_vendedor);
            echo json_encode(['success' => true, 'data' => $ventas]);
        } catch (Exception $e) { http_response_code(500); echo json_encode(['success' => false, 'message' => 'Error al generar el reporte.', 'error' => $e->getMessage()]); }
    }

    private function handleCorteRequest($methodName) {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); return; }
        if (empty($_GET['date'])) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Fecha no proporcionada.']); return; }

        $fecha = htmlspecialchars(strip_tags($_GET['date']));
        $id_sucursal = $_SESSION['branch_id'];

        // --- CAMBIO: Lógica de roles para Corte de Caja ---
        $id_usuario_a_filtrar = null;
        if (isset($_SESSION['rol'])) {
            if ($_SESSION['rol'] === 'Vendedor') {
                $id_usuario_a_filtrar = $_SESSION['user_id'];
            } elseif ($_SESSION['rol'] === 'Administrador' && !empty($_GET['user_id'])) {
                if ($_GET['user_id'] !== 'all') {
                    $id_usuario_a_filtrar = filter_var($_GET['user_id'], FILTER_VALIDATE_INT);
                }
            }
        }

        try {
            $data = $this->reporteModel->{$methodName}($id_sucursal, $fecha, $id_usuario_a_filtrar);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => "Error en la operación: " . $e->getMessage()]);
        }
    }


   public function getCorteCaja() {
        $this->handleCorteRequest('getCorteDeCaja');
    }

    public function getDetailedExpenses() {
        $this->handleCorteRequest('getGastosDetallados');
    }

    public function getDetailedClientPayments() {
        $this->handleCorteRequest('getAbonosDetallados');
    }

    public function getVentasGlobales() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Super') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        try {
            $ventas = $this->reporteModel->getVentasGlobales();
            // DataTables espera un objeto con una clave "data"
            echo json_encode(['data' => $ventas]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['data' => [], 'error' => 'Error al generar el reporte global: ' . $e->getMessage()]);
        }
    }
}
