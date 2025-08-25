<?php
// Archivo: /app/models/Producto.php

require_once __DIR__ . '/../../config/Database.php';

class Producto
{
    private $conn;
    private $table_name = "productos";
    private $codes_table = "producto_codigos";
    private $inventory_table = "inventario_sucursal";
    private $special_prices_table = "cliente_precios_especiales";
    private $movements_table = "movimientos_inventario";

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function getAllServerSide($id_sucursal, $params)
    {
        $baseQuery = "FROM productos p
                      LEFT JOIN categorias c ON p.id_categoria = c.id
                      LEFT JOIN marcas m ON p.id_marca = m.id
                      LEFT JOIN inventario_sucursal inv ON p.id = inv.id_producto AND inv.id_sucursal = :id_sucursal
                      LEFT JOIN producto_codigos pc ON p.id = pc.id_producto";

        $whereClause = "WHERE p.activo = 1";
        $bindings = [':id_sucursal' => $id_sucursal];

        if (!empty($params['search']['value'])) {
            $searchValue = '%' . $params['search']['value'] . '%';
            $whereClause .= " AND (p.sku LIKE :search_value OR p.nombre LIKE :search_value OR c.nombre LIKE :search_value OR pc.codigo_barras LIKE :search_value)";
            $bindings[':search_value'] = $searchValue;
        }

        $stmtTotal = $this->conn->prepare("SELECT COUNT(DISTINCT p.id) " . $baseQuery . " WHERE p.activo = 1");
        $stmtTotal->execute([':id_sucursal' => $id_sucursal]);
        $recordsTotal = $stmtTotal->fetchColumn();

        $stmtFiltered = $this->conn->prepare("SELECT COUNT(DISTINCT p.id) " . $baseQuery . " " . $whereClause);
        $stmtFiltered->execute($bindings);
        $recordsFiltered = $stmtFiltered->fetchColumn();

        $columns = ['p.sku', 'p.nombre', 'pc.codigo_barras', 'c.nombre', null, 'p.precio_menudeo'];
        $orderClause = "ORDER BY " . $columns[$params['order'][0]['column']] . " " . $params['order'][0]['dir'];

        $limitClause = "LIMIT :limit OFFSET :offset";
        $bindings[':limit'] = intval($params['length']);
        $bindings[':offset'] = intval($params['start']);

        $query = "SELECT 
                    p.id, p.sku, p.nombre, p.costo, p.precio_menudeo,
                    c.nombre as categoria_nombre,
                    inv.stock,
                    GROUP_CONCAT(DISTINCT pc.codigo_barras SEPARATOR ', ') as codigos_barras
                  " . $baseQuery . "
                  " . $whereClause . "
                  GROUP BY p.id
                  " . $orderClause . "
                  " . $limitClause;

        $stmtData = $this->conn->prepare($query);
        foreach ($bindings as $key => &$val) {
            $stmtData->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmtData->execute();
        $data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
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
        $query = "SELECT 
                    p.id, p.sku, p.nombre, p.costo, p.precio_menudeo, p.precio_mayoreo, p.precio_1, p.precio_2, p.precio_3, p.precio_4, p.precio_5, p.costo, p.precio_1, p.precio_2, p.precio_3, p.precio_4, p.precio_5, p.activo,
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

    public function create($data, $id_sucursal_actual)
    {
        $this->conn->beginTransaction();
        try {
            $query_producto = "INSERT INTO " . $this->table_name . " (id_categoria, id_marca, nombre, descripcion, sku, costo, precio_menudeo, precio_mayoreo) VALUES (:id_categoria, :id_marca, :nombre, :descripcion, :sku, :costo, :precio_menudeo, :precio_mayoreo)";
            $stmt_producto = $this->conn->prepare($query_producto);
            $stmt_producto->bindParam(':id_categoria', $data['id_categoria']);
            $stmt_producto->bindParam(':id_marca', $data['id_marca']);
            $stmt_producto->bindParam(':nombre', $data['nombre']);
            $stmt_producto->bindParam(':descripcion', $data['descripcion']);
            $stmt_producto->bindParam(':sku', $data['sku']);
            $stmt_producto->bindParam(':costo', $data['costo']);
            $stmt_producto->bindParam(':precio_menudeo', $data['precio_menudeo']);
            $stmt_producto->bindParam(':precio_mayoreo', $data['precio_mayoreo']);

            $stmt_producto->execute();
            $id_producto_nuevo = $this->conn->lastInsertId();

            if (!empty($data['codigos_barras']) && is_array($data['codigos_barras'])) {
                $query_codigos = "INSERT INTO " . $this->codes_table . " (id_producto, codigo_barras) VALUES (:id_producto, :codigo_barras)";
                $stmt_codigos = $this->conn->prepare($query_codigos);
                foreach ($data['codigos_barras'] as $codigo) {
                    $codigo_limpio = trim($codigo);
                    if (!empty($codigo_limpio)) {
                        $stmt_codigos->bindParam(':id_producto', $id_producto_nuevo);
                        $stmt_codigos->bindValue(':codigo_barras', $codigo_limpio);
                        $stmt_codigos->execute();
                    }
                }
            }

            // MODIFICACIÓN: Crear registro de stock para TODAS las sucursales
            $sucursales = $this->getAllSucursalIds();
            foreach ($sucursales as $id_sucursal) {
                // El stock inicial del formulario se aplica solo a la sucursal actual. Las demás empiezan en 0.
                $initial_stock = ($id_sucursal == $id_sucursal_actual) ? ($data['stock'] ?? 0) : 0;
                $stock_minimo = ($id_sucursal == $id_sucursal_actual) ? ($data['stock_minimo'] ?? 0) : 0;
                
                $this->createInitialStockRecord($id_producto_nuevo, $id_sucursal, $initial_stock, $stock_minimo);
            }

            // Registrar el movimiento de inventario solo para la sucursal actual y si hay stock inicial
            $stock_inicial_actual = $data['stock'] ?? 0;
            if ($stock_inicial_actual > 0) {
                $this->addInventoryMovement(
                    $id_producto_nuevo,
                    $id_sucursal_actual,
                    $_SESSION['user_id'],
                    'entrada',
                    $stock_inicial_actual,
                    0, // stock anterior es 0
                    $stock_inicial_actual, // stock nuevo es el inicial
                    'Inventario inicial por creación de producto',
                    null
                );
            }

            $this->conn->commit();
            return $id_producto_nuevo;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getById($id, $id_sucursal)
    {
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

            $query_producto = "UPDATE " . $this->table_name . " SET id_categoria = :id_categoria, id_marca = :id_marca, nombre = :nombre, descripcion = :descripcion, sku = :sku, costo = :costo, precio_menudeo = :precio_menudeo, precio_mayoreo = :precio_mayoreo WHERE id = :id";
            $stmt_producto = $this->conn->prepare($query_producto);
            $stmt_producto->bindParam(':id', $id);
            $stmt_producto->bindParam(':id_categoria', $data['id_categoria']);
            $stmt_producto->bindParam(':id_marca', $data['id_marca']);
            $stmt_producto->bindParam(':nombre', $data['nombre']);
            $stmt_producto->bindParam(':descripcion', $data['descripcion']);
            $stmt_producto->bindParam(':sku', $data['sku']);
            $stmt_producto->bindParam(':costo', $data['costo']);
            $stmt_producto->bindParam(':precio_menudeo', $data['precio_menudeo']);
            $stmt_producto->bindParam(':precio_mayoreo', $data['precio_mayoreo']);

            if ($stmt_producto->execute()) {
                $query_delete_codigos = "DELETE FROM " . $this->codes_table . " WHERE id_producto = :id_producto";
                $stmt_delete = $this->conn->prepare($query_delete_codigos);
                $stmt_delete->bindParam(':id_producto', $id);
                $stmt_delete->execute();

                if (!empty($data['codigos_barras']) && is_array($data['codigos_barras'])) {
                    $query_insert_codigos = "INSERT INTO " . $this->codes_table . " (id_producto, codigo_barras) VALUES (:id_producto, :codigo_barras)";
                    $stmt_insert = $this->conn->prepare($query_insert_codigos);
                    foreach ($data['codigos_barras'] as $codigo) {
                        $codigo_limpio = trim($codigo);
                        if (!empty($codigo_limpio)) {
                            $stmt_insert->bindParam(':id_producto', $id);
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
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getForPOS($id_producto, $id_sucursal, $id_cliente)
    {
        $query = "SELECT 
                    p.id, p.sku, p.nombre, p.costo, p.precio_menudeo, p.precio_mayoreo, p.precio_1, p.precio_2, p.precio_3, p.precio_4, p.precio_5,
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
            $producto['precio_final'] = $producto['precio_1'] ?? $producto['precio_menudeo'];
            $producto['tipo_precio_aplicado'] = 'P1';
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
            $stmt_update->bindParam(':new_stock', $new_stock);
            $stmt_update->bindParam(':new_stock_update', $new_stock);
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
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->bindParam(':stock_anterior', $stock_anterior);
            $stmt->bindParam(':stock_nuevo', $stock_nuevo);
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
    public function search($id_sucursal, $q, $limit = 50, $offset = 0)
    {
        $like = '%' . $q . '%';
        $query = "SELECT 
                    p.id, p.sku, p.nombre, p.costo, p.precio_menudeo, p.precio_mayoreo,
                    p.precio_1, p.precio_2, p.precio_3, p.precio_4, p.precio_5, p.activo,
                    c.nombre as categoria_nombre,
                    m.nombre as marca_nombre,
                    inv.stock, inv.stock_minimo,
                    GROUP_CONCAT(pc.codigo_barras SEPARATOR ', ') as codigos_barras
                  FROM productos p
                  LEFT JOIN categorias c ON p.id_categoria = c.id
                  LEFT JOIN marcas m ON p.id_marca = m.id
                  LEFT JOIN " . $this->inventory_table . " inv
                         ON p.id = inv.id_producto AND inv.id_sucursal = :id_sucursal
                  LEFT JOIN " . $this->codes_table . " pc
                         ON p.id = pc.id_producto
                  WHERE (p.nombre LIKE :like OR p.sku LIKE :like OR pc.codigo_barras LIKE :like)
                  GROUP BY p.id
                  ORDER BY p.nombre ASC
                  LIMIT :offset, :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->bindParam(':like', $like);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchSimpleByNameOrSku($term)
    {
        $query = "SELECT id, nombre, sku, precio_menudeo 
              FROM productos 
              WHERE (nombre LIKE :term OR sku LIKE :term) AND activo = 1 
              LIMIT 10";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $term . "%";
        $stmt->bindParam(':term', $searchTerm);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAllSucursalIds() {
        $stmt = $this->conn->prepare("SELECT id FROM sucursales WHERE activo = 1");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function createInitialStockRecord($id_producto, $id_sucursal, $stock = 0, $stock_minimo = 0)
    {
        $query = "INSERT INTO " . $this->inventory_table . " (id_producto, id_sucursal, stock, stock_minimo) 
                  VALUES (:id_producto, :id_sucursal, :stock, :stock_minimo)
                  ON DUPLICATE KEY UPDATE stock = VALUES(stock), stock_minimo = VALUES(stock_minimo)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt->bindParam(':id_sucursal', $id_sucursal, PDO::PARAM_INT);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':stock_minimo', $stock_minimo);
        
        return $stmt->execute();
    }
}
