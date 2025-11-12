-- triggers.sql
-- Triggers para el sistema: membresía, estadísticas por categoría, alertas de stock, historial de estado de clientes

-- Tabla auxiliar: estadísticas por categoría
CREATE TABLE IF NOT EXISTS estadisticas_categoria (
    categoria_id INT PRIMARY KEY,
    total_ventas DECIMAL(12,2) DEFAULT 0,
    total_items INT DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de alertas de stock
CREATE TABLE IF NOT EXISTS alertas_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    stock_actual INT NOT NULL,
    nivel_critico INT NOT NULL,
    fecha DATETIME NOT NULL
);

-- Tabla historial estado cliente
CREATE TABLE IF NOT EXISTS historial_estado_cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    estado_anterior VARCHAR(50),
    estado_nuevo VARCHAR(50),
    fecha DATETIME NOT NULL
);

-- Trigger: después de insertar una venta -> actualizar estadisticas_categoria y potencialmente nivel membresía
DELIMITER $$
DROP TRIGGER IF EXISTS trg_after_insert_venta$$
CREATE TRIGGER trg_after_insert_venta
AFTER INSERT ON ventas
FOR EACH ROW
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE pid INT;
    DECLARE qty INT;
    DECLARE prec DECIMAL(12,2);
    DECLARE cat INT;
    DECLARE cur CURSOR FOR SELECT dv.producto_id, dv.cantidad, dv.precio_unitario FROM detalles_venta dv WHERE dv.venta_id = NEW.id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO pid, qty, prec;
        IF done THEN LEAVE read_loop; END IF;
        -- Obtener categoría del producto
        SELECT categoria_id INTO cat FROM productos WHERE id = pid LIMIT 1;
        -- Insertar o actualizar estadisticas_categoria
        INSERT INTO estadisticas_categoria (categoria_id, total_ventas, total_items)
        VALUES (cat, qty * prec, qty)
        ON DUPLICATE KEY UPDATE
            total_ventas = total_ventas + qty * prec,
            total_items = total_items + qty;
    END LOOP;
    CLOSE cur;

    -- Nota: el nivel de membresía se puede actualizar mediante procedimiento separado o trigger adicional
END$$

-- Trigger: después de actualizar producto -> generar alerta si stock <= nivel critico (por ejemplo 2)
DROP TRIGGER IF EXISTS trg_after_update_producto_stock$$
CREATE TRIGGER trg_after_update_producto_stock
AFTER UPDATE ON productos
FOR EACH ROW
BEGIN
    IF NEW.stock <= 2 THEN
        INSERT INTO alertas_stock (producto_id, stock_actual, nivel_critico, fecha)
        VALUES (NEW.id, NEW.stock, 2, NOW());
    END IF;
END$$

-- Trigger: después de actualizar cliente -> registrar historial de estado (asumiendo columna estado)
DROP TRIGGER IF EXISTS trg_after_update_cliente_estado$$
CREATE TRIGGER trg_after_update_cliente_estado
AFTER UPDATE ON clientes
FOR EACH ROW
BEGIN
    IF NEW.estado <> OLD.estado THEN
        INSERT INTO historial_estado_cliente (cliente_id, estado_anterior, estado_nuevo, fecha)
        VALUES (NEW.id, OLD.estado, NEW.estado, NOW());
    END IF;
END$$

DELIMITER ;

-- Notas:
-- - El trigger de membresía se puede implementar como procedimiento que se ejecute periódicamente o mediante otro trigger si se desea.
-- - Validar la existencia de índices: productos(id, categoria_id), detalles_venta(venta_id), estadisticas_categoria(categoria_id)
