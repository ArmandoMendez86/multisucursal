<?php
// Archivo: /app/models/Producto.php

require_once __DIR__ . '/../../config/Database.php';

class Producto
{
    private $conn;
    private $table_name = "productos";
    private $codes_table = "producto_codigos"; // Nueva tabla de códigos
    private $inventory_table = "inventario_sucursal";
    private $special_prices_table = "cliente_precios_especiales";
    private $movements_table = "movimientos_inventario";

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function getAllSimple()
    {
        $query = "SELECT id, sku, nombre, precio_menudeo FROM " . $this->table_name . " WHERE activo = 1 ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll($id_sucursal)
    {
        // Se elimina p.codigo_barras y se agrega el GROUP_CONCAT para los nuevos códigos
        $query = "SELECT 
                    p.id, p.sku, p.nombre, p.precio_menudeo, p.activo,
                    c.nombre as categoria_nombre,
                    m.nombre as marca_nombre,
                    inv.stock, inv.stock_minimo,
                    GROUP_CONCAT(pc.codigo_barras SEPARATOR ', ') as codigos_barras
                  FROM 
                    productos p
                  LEFT JOIN categorias c ON p.id_categoria = c.id
                  LEFT JOIN marcas m ON p.id_marca = m.id
                  LEFT JOIN inventario_sucursal inv ON p.id = inv.id_producto AND inv.id_sucursal = :id_sucursal
                  LEFT JOIN " . $this->codes_table . " pc ON p.id = pc.id_producto
                  GROUP BY p.id
                  ORDER BY p.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByBarcodeOrSku($code, $id_sucursal)
    {
        // La consulta ahora busca en la tabla de productos (por SKU) y en la nueva tabla de códigos
        $query = "SELECT 
                    p.*, 
                    inv.stock, 
                    inv.stock_minimo
                  FROM 
                    " . $this->table_name . " p
                  LEFT JOIN 
                    " . $this->inventory_table . " inv ON p.id = inv.id_producto AND inv.id_sucursal = :id_sucursal
                  LEFT JOIN
                    " . $this->codes_table . " pc ON p.id = pc.id_producto
                  WHERE 
                    (p.sku = :code OR pc.codigo_barras = :code)
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data, $id_sucursal)
    {
        $this->conn->beginTransaction();
        try {
            // Se elimina codigo_barras de la inserción principal
            $query_producto = "INSERT INTO " . $this->table_name . " (id_categoria, id_marca, nombre, descripcion, sku, precio_menudeo, precio_mayoreo) VALUES (:id_categoria, :id_marca, :nombre, :descripcion, :sku, :precio_menudeo, :precio_mayoreo)";
            $stmt_producto = $this->conn->prepare($query_producto);
            $stmt_producto->bindParam(':id_categoria', $data['id_categoria']);
            $stmt_producto->bindParam(':id_marca', $data['id_marca']);
            $stmt_producto->bindParam(':nombre', $data['nombre']);
            $stmt_producto->bindParam(':descripcion', $data['descripcion']);
            $stmt_producto->bindParam(':sku', $data['sku']);
            $stmt_producto->bindParam(':precio_menudeo', $data['precio_menudeo']);
            $stmt_producto->bindParam(':precio_mayoreo', $data['precio_mayoreo']);

            if ($stmt_producto->execute()) {
                $id_producto_nuevo = $this->conn->lastInsertId();

                // Insertar los múltiples códigos de barras
                if (!empty($data['codigos_barras']) && is_array($data['codigos_barras'])) {
                    $query_codigos = "INSERT INTO " . $this->codes_table . " (id_producto, codigo_barras) VALUES (:id_producto, :codigo_barras)";
                    $stmt_codigos = $this->conn->prepare($query_codigos);
                    foreach ($data['codigos_barras'] as $codigo) {
                        $codigo_limpio = trim($codigo);
                        if (!empty($codigo_limpio)) {
                            $stmt_codigos->bindParam(':id_producto', $id_producto_nuevo);
                            // CORRECCIÓN: Usar bindValue en lugar de bindParam
                            $stmt_codigos->bindValue(':codigo_barras', $codigo_limpio);
                            $stmt_codigos->execute();
                        }
                    }
                }

                $query_inventario = "INSERT INTO " . $this->inventory_table . " (id_producto, id_sucursal, stock, stock_minimo) VALUES (:id_producto, :id_sucursal, :stock, :stock_minimo)";
                $stmt_inventario = $this->conn->prepare($query_inventario);
                $stmt_inventario->bindParam(':id_producto', $id_producto_nuevo);
                $stmt_inventario->bindParam(':id_sucursal', $id_sucursal);
                $stmt_inventario->bindParam(':stock', $data['stock']);
                $stmt_inventario->bindParam(':stock_minimo', $data['stock_minimo']);

                if ($stmt_inventario->execute()) {
                    $this->addInventoryMovement(
                        $id_producto_nuevo,
                        $id_sucursal,
                        $_SESSION['user_id'],
                        'entrada',
                        $data['stock'],
                        0,
                        $data['stock'],
                        'Creación de producto e inventario inicial',
                        null
                    );
                    $this->conn->commit();
                    return $id_producto_nuevo;
                }
            }
            $this->conn->rollBack();
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getById($id, $id_sucursal)
    {
        // Se añade la obtención de los múltiples códigos de barras
        $query = "SELECT 
                    p.*, 
                    inv.stock, 
                    inv.stock_minimo,
                    GROUP_CONCAT(pc.codigo_barras) as codigos_barras
                  FROM 
                    " . $this->table_name . " p
                  LEFT JOIN 
                    " . $this->inventory_table . " inv ON p.id = inv.id_producto AND inv.id_sucursal = :id_sucursal
                  LEFT JOIN
                    " . $this->codes_table . " pc ON p.id = pc.id_producto
                  WHERE 
                    p.id = :id
                  GROUP BY p.id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->execute();
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        // Convertir la cadena de códigos en un array
        if ($producto && !empty($producto['codigos_barras'])) {
            $producto['codigos_barras'] = explode(',', $producto['codigos_barras']);
        } else if ($producto) {
            $producto['codigos_barras'] = [];
        }

        return $producto;
    }

    public function update($id, $data, $id_sucursal)
    {
        $this->conn->beginTransaction();
        try {
            $currentProduct = $this->getById($id, $id_sucursal);
            $oldStock = $currentProduct['stock'] ?? 0;

            // Se elimina codigo_barras de la actualización principal
            $query_producto = "UPDATE " . $this->table_name . " SET id_categoria = :id_categoria, id_marca = :id_marca, nombre = :nombre, descripcion = :descripcion, sku = :sku, precio_menudeo = :precio_menudeo, precio_mayoreo = :precio_mayoreo WHERE id = :id";
            $stmt_producto = $this->conn->prepare($query_producto);
            $stmt_producto->bindParam(':id', $id);
            $stmt_producto->bindParam(':id_categoria', $data['id_categoria']);
            $stmt_producto->bindParam(':id_marca', $data['id_marca']);
            $stmt_producto->bindParam(':nombre', $data['nombre']);
            $stmt_producto->bindParam(':descripcion', $data['descripcion']);
            $stmt_producto->bindParam(':sku', $data['sku']);
            $stmt_producto->bindParam(':precio_menudeo', $data['precio_menudeo']);
            $stmt_producto->bindParam(':precio_mayoreo', $data['precio_mayoreo']);

            if ($stmt_producto->execute()) {
                // Eliminar códigos de barras existentes
                $query_delete_codigos = "DELETE FROM " . $this->codes_table . " WHERE id_producto = :id_producto";
                $stmt_delete = $this->conn->prepare($query_delete_codigos);
                $stmt_delete->bindParam(':id_producto', $id);
                $stmt_delete->execute();

                // Insertar los nuevos códigos de barras
                if (!empty($data['codigos_barras']) && is_array($data['codigos_barras'])) {
                    $query_insert_codigos = "INSERT INTO " . $this->codes_table . " (id_producto, codigo_barras) VALUES (:id_producto, :codigo_barras)";
                    $stmt_insert = $this->conn->prepare($query_insert_codigos);
                    foreach ($data['codigos_barras'] as $codigo) {
                        $codigo_limpio = trim($codigo);
                        if (!empty($codigo_limpio)) {
                            $stmt_insert->bindParam(':id_producto', $id);
                            // CORRECCIÓN: Usar bindValue en lugar de bindParam
                            $stmt_insert->bindValue(':codigo_barras', $codigo_limpio);
                            $stmt_insert->execute();
                        }
                    }
                }

                $query_inventario = "INSERT INTO " . $this->inventory_table . " (id_producto, id_sucursal, stock, stock_minimo) 
                                     VALUES (:id_producto, :id_sucursal, :stock, :stock_minimo)
                                     ON DUPLICATE KEY UPDATE stock = :stock_update, stock_minimo = :stock_minimo_update";

                $stmt_inventario = $this->conn->prepare($query_inventario);
                $stmt_inventario->bindParam(':id_producto', $id);
                $stmt_inventario->bindParam(':id_sucursal', $id_sucursal);
                $stmt_inventario->bindParam(':stock', $data['stock']);
                $stmt_inventario->bindParam(':stock_minimo', $data['stock_minimo']);
                $stmt_inventario->bindParam(':stock_update', $data['stock']);
                $stmt_inventario->bindParam(':stock_minimo_update', $data['stock_minimo']);

                if ($stmt_inventario->execute()) {
                    if ($oldStock != $data['stock']) {
                        $this->addInventoryMovement(
                            $id,
                            $id_sucursal,
                            $_SESSION['user_id'],
                            'ajuste',
                            abs($data['stock'] - $oldStock),
                            $oldStock,
                            $data['stock'],
                            'Actualización de producto desde gestión',
                            null
                        );
                    }
                    $this->conn->commit();
                    return true;
                }
            }
            $this->conn->rollBack();
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function delete($id)
    {
        // ON DELETE CASCADE se encargará de los códigos en producto_codigos
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getForPOS($id_producto, $id_sucursal, $id_cliente)
    {
        $query = "SELECT 
                    p.id, p.sku, p.nombre, p.precio_menudeo, p.precio_mayoreo,
                    inv.stock
                  FROM 
                    " . $this->table_name . " p
                  LEFT JOIN 
                    " . $this->inventory_table . " inv ON p.id = inv.id_producto AND inv.id_sucursal = :id_sucursal
                  WHERE 
                    p.id = :id_producto
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_producto', $id_producto);
        $stmt->bindParam(':id_sucursal', $id_sucursal);
        $stmt->execute();
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            return null;
        }

        $precio_especial = null;
        if ($id_cliente != 1) {
            $query_precio = "SELECT precio_especial FROM " . $this->special_prices_table . " WHERE id_cliente = :id_cliente AND id_producto = :id_producto";
            $stmt_precio = $this->conn->prepare($query_precio);
            $stmt_precio->bindParam(':id_cliente', $id_cliente);
            $stmt_precio->bindParam(':id_producto', $id_producto);
            $stmt_precio->execute();
            $resultado_precio = $stmt_precio->fetch(PDO::FETCH_ASSOC);
            if ($resultado_precio) {
                $precio_especial = $resultado_precio['precio_especial'];
            }
        }

        if ($precio_especial !== null) {
            $producto['precio_final'] = $precio_especial;
            $producto['tipo_precio_aplicado'] = 'Especial';
        } else {
            $producto['precio_final'] = $producto['precio_menudeo'];
            $producto['tipo_precio_aplicado'] = 'Menudeo';
        }

        return $producto;
    }

    public function updateStock($id_producto, $id_sucursal, $new_stock, $tipo_movimiento, $cantidad_movida, $stock_anterior, $motivo, $referencia_id = null)
    {
        try {
            $query_update_stock = "INSERT INTO " . $this->inventory_table . " (id_producto, id_sucursal, stock) 
                                   VALUES (:id_producto, :id_sucursal, :new_stock)
                                   ON DUPLICATE KEY UPDATE stock = :new_stock_update";
            $stmt_update = $this->conn->prepare($query_update_stock);
            $stmt_update->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmt_update->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
            $stmt_update->bindParam(':new_stock', $new_stock, PDO::PARAM_INT);
            $stmt_update->bindParam(':new_stock_update', $new_stock, PDO::PARAM_INT);
            $stmt_update->execute();

            $this->addInventoryMovement(
                $id_producto,
                $id_sucursal,
                $_SESSION['user_id'],
                $tipo_movimiento,
                $cantidad_movida,
                $stock_anterior,
                $new_stock,
                $motivo,
                $referencia_id
            );

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function addInventoryMovement($id_producto, $id_sucursal, $id_usuario, $tipo_movimiento, $cantidad, $stock_anterior, $stock_nuevo, $motivo = null, $referencia_id = null)
    {
        try {
            $query = "INSERT INTO " . $this->movements_table . " 
                    (id_producto, id_sucursal, id_usuario, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, motivo, referencia_id) 
                    VALUES (:id_producto, :id_sucursal, :id_usuario, :tipo_movimiento, :cantidad, :stock_anterior, :stock_nuevo, :motivo, :referencia_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
            $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $stmt->bindParam(':tipo_movimiento', $tipo_movimiento);
            $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt->bindParam(':stock_anterior', $stock_anterior, PDO::PARAM_INT);
            $stmt->bindParam(':stock_nuevo', $stock_nuevo, PDO::PARAM_INT);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->bindParam(':referencia_id', $referencia_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function getInventoryMovements($id_sucursal)
    {
        $query = "SELECT 
                    mov.fecha, 
                    p.nombre as producto_nombre, 
                    mov.tipo_movimiento, 
                    mov.cantidad,
                    mov.stock_anterior,
                    mov.stock_nuevo,
                    mov.motivo,
                    u.nombre as usuario_nombre,
                    mov.referencia_id
                  FROM " . $this->movements_table . " mov
                  JOIN productos p ON mov.id_producto = p.id
                  JOIN usuarios u ON mov.id_usuario = u.id
                  WHERE mov.id_sucursal = :id_sucursal
                  ORDER BY mov.fecha DESC
                  LIMIT 100";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function findStockInAllBranches($searchTerm)
    {
        // Se añade la búsqueda por la nueva tabla de códigos
        $query = "SELECT 
                    p.id AS producto_id,
                    p.sku,
                    p.nombre AS producto_nombre,
                    s.nombre AS sucursal_nombre,
                    inv.stock
                  FROM 
                    {$this->table_name} p
                  JOIN 
                    {$this->inventory_table} inv ON p.id = inv.id_producto
                  JOIN 
                    sucursales s ON inv.id_sucursal = s.id
                  LEFT JOIN
                    {$this->codes_table} pc ON p.id = pc.id_producto
                  WHERE 
                    (p.sku LIKE :searchTerm OR p.nombre LIKE :searchTerm OR pc.codigo_barras LIKE :searchTerm)
                    AND p.activo = 1
                  GROUP BY p.id, s.id
                  ORDER BY 
                    p.nombre, s.nombre";

        $stmt = $this->conn->prepare($query);
        $likeTerm = "%{$searchTerm}%";
        $stmt->bindParam(':searchTerm', $likeTerm);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
