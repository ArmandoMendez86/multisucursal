
-- migracion_agregar_precios_niveles.sql (versión segura)
ALTER TABLE productos
  ADD COLUMN IF NOT EXISTS costo DECIMAL(10,2) NULL AFTER sku,
  ADD COLUMN IF NOT EXISTS precio_1 DECIMAL(10,2) NULL AFTER costo,
  ADD COLUMN IF NOT EXISTS precio_2 DECIMAL(10,2) NULL AFTER precio_1,
  ADD COLUMN IF NOT EXISTS precio_3 DECIMAL(10,2) NULL AFTER precio_2,
  ADD COLUMN IF NOT EXISTS precio_4 DECIMAL(10,2) NULL AFTER precio_3,
  ADD COLUMN IF NOT EXISTS precio_5 DECIMAL(10,2) NULL AFTER precio_4;

UPDATE productos p
JOIN (SELECT id FROM productos WHERE precio_1 IS NULL OR precio_2 IS NULL) f ON f.id = p.id
SET p.precio_1 = COALESCE(p.precio_1, p.precio_menudeo),
    p.precio_2 = COALESCE(p.precio_2, p.precio_mayoreo);
