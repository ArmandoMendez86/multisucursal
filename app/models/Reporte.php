<?php
// Archivo: /app/models/Reporte.php

require_once __DIR__ . '/../../config/Database.php';

class Reporte
{
    private $conn;

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    // --- CAMBIO: Firma del método y consulta SQL actualizadas ---
    public function getVentasPorFecha($id_sucursal, $fecha_inicio, $fecha_fin, $id_vendedor = null)
    {
        $fecha_fin_completa = $fecha_fin . ' 23:59:59';
        $query = "SELECT v.id, v.fecha, v.total, v.estado, c.nombre as cliente_nombre, u.nombre as usuario_nombre
                  FROM ventas v
                  JOIN clientes c ON v.id_cliente = c.id
                  JOIN usuarios u ON v.id_usuario = u.id
                  WHERE v.id_sucursal = :id_sucursal 
                    AND v.fecha BETWEEN :fecha_inicio AND :fecha_fin_completa";

        if ($id_vendedor !== null) {
            $query .= " AND v.id_usuario = :id_vendedor";
        }

        $query .= " ORDER BY v.fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin_completa', $fecha_fin_completa);
        if ($id_vendedor !== null) {
            $stmt->bindParam(':id_vendedor', $id_vendedor);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- CAMBIO: Firma del método y consultas SQL actualizadas ---
    public function getCorteDeCaja($id_sucursal, $fecha, $id_usuario = null)
    {
        $fecha_inicio_completa = $fecha . ' 00:00:00';
        $fecha_fin_completa = $fecha . ' 23:59:59';
        $resultado = [
            'total_ventas' => 0,
            'ventas_efectivo' => 0,
            'ventas_tarjeta' => 0,
            'ventas_transferencia' => 0,
            'ventas_credito' => 0,
            'total_gastos' => 0,
            'abonos_clientes' => 0
        ];

        // 1. Ventas
        $query_ventas = "SELECT metodo_pago, total FROM ventas WHERE id_sucursal = :id_sucursal AND fecha BETWEEN :fecha_inicio AND :fecha_fin AND estado = 'Completada'";
        if ($id_usuario !== null)
            $query_ventas .= " AND id_usuario = :id_usuario";
        $stmt_ventas = $this->conn->prepare($query_ventas);
        $stmt_ventas->bindParam(':id_sucursal', $id_sucursal);
        $stmt_ventas->bindParam(':fecha_inicio', $fecha_inicio_completa);
        $stmt_ventas->bindParam(':fecha_fin', $fecha_fin_completa);
        if ($id_usuario !== null)
            $stmt_ventas->bindParam(':id_usuario', $id_usuario);
        $stmt_ventas->execute();

        // --- INICIO DE LA LÓGICA CORREGIDA ---
        while ($row = $stmt_ventas->fetch(PDO::FETCH_ASSOC)) {
            $resultado['total_ventas'] += $row['total'];
            $metodos_pago = json_decode($row['metodo_pago'], true);
            if (is_array($metodos_pago)) {
                foreach ($metodos_pago as $pago) {
                    if (isset($pago['method']) && isset($pago['amount'])) {
                        switch ($pago['method']) {
                            case 'Efectivo':
                                $resultado['ventas_efectivo'] += $pago['amount'];
                                break;
                            case 'Tarjeta':
                                $resultado['ventas_tarjeta'] += $pago['amount'];
                                break;
                            case 'Transferencia':
                                $resultado['ventas_transferencia'] += $pago['amount'];
                                break;
                            case 'Crédito':
                                $resultado['ventas_credito'] += $pago['amount'];
                                break;
                        }
                    }
                }
            }
            // Si no es un array, podría ser el formato antiguo (una sola cadena de texto)
            elseif (is_string($row['metodo_pago'])) {
                $metodo = $row['metodo_pago'];
                $monto = $row['total']; // En el formato antiguo, el total de la venta es el monto del único método.
                switch ($metodo) {
                    case 'Efectivo':
                        $resultado['ventas_efectivo'] += $monto;
                        break;
                    case 'Tarjeta':
                        $resultado['ventas_tarjeta'] += $monto;
                        break;
                    case 'Transferencia':
                        $resultado['ventas_transferencia'] += $monto;
                        break;
                    case 'Crédito':
                        $resultado['ventas_credito'] += $monto;
                        break;
                }
            }
        }
        // --- FIN DE LA LÓGICA CORREGIDA ---

        // 2. Gastos
        $query_gastos = "SELECT SUM(monto) as total_gastos FROM gastos WHERE id_sucursal = :id_sucursal AND fecha BETWEEN :fecha_inicio AND :fecha_fin";
        if ($id_usuario !== null)
            $query_gastos .= " AND id_usuario = :id_usuario";
        $stmt_gastos = $this->conn->prepare($query_gastos);
        $stmt_gastos->bindParam(':id_sucursal', $id_sucursal);
        $stmt_gastos->bindParam(':fecha_inicio', $fecha_inicio_completa);
        $stmt_gastos->bindParam(':fecha_fin', $fecha_fin_completa);
        if ($id_usuario !== null)
            $stmt_gastos->bindParam(':id_usuario', $id_usuario);
        $stmt_gastos->execute();
        $gastos_result = $stmt_gastos->fetch(PDO::FETCH_ASSOC);
        if ($gastos_result && $gastos_result['total_gastos']) {
            $resultado['total_gastos'] = $gastos_result['total_gastos'];
        }

        // 3. Abonos
        $query_abonos = "SELECT SUM(pc.monto) as total_abonos FROM pagos_clientes pc JOIN usuarios u ON pc.id_usuario = u.id WHERE u.id_sucursal = :id_sucursal AND pc.fecha BETWEEN :fecha_inicio AND :fecha_fin AND pc.metodo_pago IN ('Efectivo', 'Transferencia')";
        if ($id_usuario !== null)
            $query_abonos .= " AND pc.id_usuario = :id_usuario";
        $stmt_abonos = $this->conn->prepare($query_abonos);
        $stmt_abonos->bindParam(':id_sucursal', $id_sucursal);
        $stmt_abonos->bindParam(':fecha_inicio', $fecha_inicio_completa);
        $stmt_abonos->bindParam(':fecha_fin', $fecha_fin_completa);
        if ($id_usuario !== null)
            $stmt_abonos->bindParam(':id_usuario', $id_usuario);
        $stmt_abonos->execute();
        $abonos_result = $stmt_abonos->fetch(PDO::FETCH_ASSOC);
        if ($abonos_result && $abonos_result['total_abonos']) {
            $resultado['abonos_clientes'] = $abonos_result['total_abonos'];
        }

        return $resultado;
    }

    // --- CAMBIO: Firma del método y consulta SQL actualizadas ---
    public function getGastosDetallados($id_sucursal, $fecha, $id_usuario = null)
    {
        $fecha_inicio_completa = $fecha . ' 00:00:00';
        $fecha_fin_completa = $fecha . ' 23:59:59';
        $query = "SELECT id, fecha, categoria_gasto, descripcion, monto 
                  FROM gastos WHERE id_sucursal = :id_sucursal AND fecha BETWEEN :fecha_inicio AND :fecha_fin";
        if ($id_usuario !== null)
            $query .= " AND id_usuario = :id_usuario";
        $query .= " ORDER BY fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio_completa);
        $stmt->bindParam(':fecha_fin', $fecha_fin_completa);
        if ($id_usuario !== null)
            $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- CAMBIO: Firma del método y consulta SQL actualizadas ---
    public function getAbonosDetallados($id_sucursal, $fecha, $id_usuario = null)
    {
        $fecha_inicio_completa = $fecha . ' 00:00:00';
        $fecha_fin_completa = $fecha . ' 23:59:59';
        $query = "SELECT pc.id, pc.fecha, pc.monto, pc.metodo_pago, c.nombre as cliente_nombre, u.nombre as usuario_nombre
                  FROM pagos_clientes pc JOIN clientes c ON pc.id_cliente = c.id JOIN usuarios u ON pc.id_usuario = u.id
                  WHERE u.id_sucursal = :id_sucursal AND pc.fecha BETWEEN :fecha_inicio AND :fecha_fin
                  AND pc.metodo_pago IN ('Efectivo', 'Transferencia')";
        if ($id_usuario !== null)
            $query .= " AND pc.id_usuario = :id_usuario";
        $query .= " ORDER BY pc.fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio_completa);
        $stmt->bindParam(':fecha_fin', $fecha_fin_completa);
        if ($id_usuario !== null)
            $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVentasGlobales()
    {
        $query = "SELECT 
                    v.id, v.fecha, v.total, v.estado, 
                    c.nombre as cliente_nombre, 
                    u.nombre as usuario_nombre,
                    s.nombre as sucursal_nombre
                  FROM ventas v
                  JOIN clientes c ON v.id_cliente = c.id
                  JOIN usuarios u ON v.id_usuario = u.id
                  JOIN sucursales s ON v.id_sucursal = s.id
                  ORDER BY v.fecha DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}