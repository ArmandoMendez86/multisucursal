<?php
// Archivo: /app/controllers/AperturaCajaController.php

require_once __DIR__ . '/../models/AperturaCaja.php';

class AperturaCajaController
{
    private $aperturaCajaModel;

    public function __construct()
    {
        $this->aperturaCajaModel = new AperturaCaja();
    }

    /**
     * Verifica si la caja ha sido abierta para la sucursal y fecha actual.
     * Retorna el monto inicial si ya está abierta, o false si no.
     */
    public function checkApertura()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'Administrador') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado. Solo administradores pueden verificar la apertura de caja.']);
            return;
        }

        $id_sucursal = $_SESSION['branch_id'];
        $fecha_actual = date('Y-m-d'); // Obtiene la fecha actual en formato YYYY-MM-DD

        try {
            $apertura = $this->aperturaCajaModel->obtenerAperturaPorFecha($id_sucursal, $fecha_actual);
            if ($apertura) {
                echo json_encode(['success' => true, 'opened' => true, 'monto_inicial' => $apertura['monto_inicial']]);
            } else {
                echo json_encode(['success' => true, 'opened' => false]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al verificar la apertura de caja.', 'error' => $e->getMessage()]);
        }
    }

    /**
     * NUEVO: Función de ayuda para validar el formato de la fecha.
     */
    private function validarFecha($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Registra el monto de apertura de caja para la sucursal y fecha actual.
     * Solo accesible para administradores.
     */
    public function registrarApertura()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado. Debe iniciar sesión.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $monto_inicial = $data['monto_inicial'] ?? null;
        // CAMBIO: Recibir la fecha de apertura desde el frontend
        $fecha_apertura = $data['fecha_apertura'] ?? null;

        if (!isset($monto_inicial) || !is_numeric($monto_inicial) || $monto_inicial < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Monto inicial no válido.']);
            return;
        }

        // CAMBIO: Validar la fecha recibida
        if (empty($fecha_apertura) || !$this->validarFecha($fecha_apertura)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Formato de fecha no válido. Use YYYY-MM-DD.']);
            return;
        }

        $id_usuario = $_SESSION['user_id'];
        $id_sucursal = $_SESSION['branch_id'];

        try {
            // CAMBIO: Usar la fecha proporcionada en lugar de la fecha actual
            $id_apertura = $this->aperturaCajaModel->registrarApertura($id_usuario, $id_sucursal, $fecha_apertura, $monto_inicial);
            echo json_encode(['success' => true, 'message' => 'Apertura de caja registrada exitosamente para la fecha ' . $fecha_apertura . '.', 'id_apertura' => $id_apertura]);
        } catch (Exception $e) {
            // El código 409 (Conflicto) es ideal para "ya existe"
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    /**
     * Obtiene el monto de apertura de caja para una fecha específica (usado en reportes).
     */
    public function getMontoApertura()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $id_sucursal = $_SESSION['branch_id'];
        $fecha = $_GET['date'] ?? date('Y-m-d'); // Fecha por defecto es hoy

        try {
            $apertura = $this->aperturaCajaModel->obtenerAperturaPorFecha($id_sucursal, $fecha);
            if ($apertura) {
                echo json_encode(['success' => true, 'monto_inicial' => $apertura['monto_inicial']]);
            } else {
                echo json_encode(['success' => true, 'monto_inicial' => 0]); // Retorna 0 si no hay apertura
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener el monto de apertura.', 'error' => $e->getMessage()]);
        }
    }
}
