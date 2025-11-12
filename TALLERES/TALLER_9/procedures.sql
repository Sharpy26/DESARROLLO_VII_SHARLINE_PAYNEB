-- procedures.sql
-- Procedimientos almacenados para el sistema de ventas/inventario
-- ADVERTENCIA: Ajusta nombres de tablas/columnas según tu esquema. Se asume la existencia de tablas:
-- productos(id, nombre, stock), ventas(id, fecha, total, cliente_id, vendedor_id), detalles_venta(venta_id, producto_id, cantidad, precio_unitario)

DROP PROCEDURE IF EXISTS sp_procesar_devolucion;
DELIMITER $$
CREATE PROCEDURE sp_procesar_devolucion(
    IN p_venta_id INT,
    IN p_producto_id INT,
    IN p_cantidad INT
)
BEGIN
    DECLARE v_det_cant INT;
    DECLARE v_new_subtotal DECIMAL(12,2);
    DECLARE v_total DECIMAL(12,2);

    START TRANSACTION;

    -- Obtener cantidad en detalle (bloqueo para consistencia)
    SELECT cantidad INTO v_det_cant
    FROM detalles_venta
    WHERE venta_id = p_venta_id AND producto_id = p_producto_id
    FOR UPDATE;

    IF v_det_cant IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Detalle de venta no encontrado';
    END IF;

    IF v_det_cant < p_cantidad THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cantidad a devolver mayor que la vendida';
    END IF;

    -- Reducir cantidad en detalles_venta (y recalcular subtotal si existe columna)
    UPDATE detalles_venta
    SET cantidad = cantidad - p_cantidad
    WHERE venta_id = p_venta_id AND producto_id = p_producto_id;

    -- Incrementar stock del producto
    UPDATE productos
    SET stock = stock + p_cantidad
    WHERE id = p_producto_id;

    -- Recalcular total de la venta
    SELECT IFNULL(SUM(dv.cantidad * dv.precio_unitario), 0)
    INTO v_total
    FROM detalles_venta dv
    WHERE dv.venta_id = p_venta_id;

    UPDATE ventas SET total = v_total WHERE id = p_venta_id;

    COMMIT;
END$$
DELIMITER ;


DROP PROCEDURE IF EXISTS sp_aplicar_descuento_cliente;
DELIMITER $$
CREATE PROCEDURE sp_aplicar_descuento_cliente(
    IN p_cliente_id INT,
    OUT p_descuento_aplicado DECIMAL(5,2)
)
BEGIN
    DECLARE v_total DECIMAL(12,2);
    SET p_descuento_aplicado = 0;

    SELECT IFNULL(SUM(dv.cantidad * dv.precio_unitario), 0)
    INTO v_total
    FROM ventas v
    JOIN detalles_venta dv ON dv.venta_id = v.id
    WHERE v.cliente_id = p_cliente_id;

    -- Reglas simples de descuento por monto acumulado
    IF v_total >= 1000 THEN
        SET p_descuento_aplicado = 0.10; -- 10%
    ELSEIF v_total >= 500 THEN
        SET p_descuento_aplicado = 0.05; -- 5%
    ELSE
        SET p_descuento_aplicado = 0.00;
    END IF;

    -- Intentamos registrar el descuento en una tabla de log si existe (no fallar si no existe)
    BEGIN
        DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
        BEGIN
            -- Si falla el INSERT (tabla no existe), continuamos sin error
        END;
        INSERT INTO descuentos_log (cliente_id, monto_total, porcentaje, fecha)
        VALUES (p_cliente_id, v_total, p_descuento_aplicado, NOW());
    END;
END$$
DELIMITER ;


DROP PROCEDURE IF EXISTS sp_reporte_bajo_stock;
DELIMITER $$
CREATE PROCEDURE sp_reporte_bajo_stock()
BEGIN
    -- Devuelve productos con stock < 5, ventas en últimos 3 meses y sugerencia de reposición
    SELECT p.id AS producto_id,
           p.nombre,
           p.stock,
           IFNULL((
               SELECT SUM(dv.cantidad)
               FROM detalles_venta dv
               JOIN ventas v ON v.id = dv.venta_id
               WHERE dv.producto_id = p.id
                 AND v.fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 3 MONTH) AND CURDATE()
           ), 0) AS ventas_ult_3m,
           GREATEST(5 - p.stock, CEIL(IFNULL((
               SELECT SUM(dv.cantidad)
               FROM detalles_venta dv
               JOIN ventas v ON v.id = dv.venta_id
               WHERE dv.producto_id = p.id
                 AND v.fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 3 MONTH) AND CURDATE()
           ), 0) / 2)) AS sugerido_reposicion
    FROM productos p
    WHERE p.stock < 5
    ORDER BY p.stock ASC;
END$$
DELIMITER ;


DROP PROCEDURE IF EXISTS sp_calcular_comisiones;
DELIMITER $$
CREATE PROCEDURE sp_calcular_comisiones(
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    -- Calcula comisiones por vendedor (si no existe columna vendedor_id, ajustar)
    SELECT v.vendedor_id,
           IFNULL(SUM(dv.cantidad * dv.precio_unitario), 0) AS ventas_total,
           IFNULL(SUM(dv.cantidad), 0) AS total_items,
           ROUND(IFNULL(SUM(dv.cantidad * dv.precio_unitario) * 0.05, 0) + IFNULL(SUM(dv.cantidad) * 0.1, 0), 2) AS comision_estimada
    FROM ventas v
    JOIN detalles_venta dv ON dv.venta_id = v.id
    WHERE v.fecha BETWEEN p_fecha_inicio AND p_fecha_fin
    GROUP BY v.vendedor_id;
END$$
DELIMITER ;

-- Tabla de logs opcional
CREATE TABLE IF NOT EXISTS descuentos_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    monto_total DECIMAL(12,2) NOT NULL,
    porcentaje DECIMAL(5,2) NOT NULL,
    fecha DATETIME NOT NULL
);

-- Índices recomendados:
-- CREATE INDEX idx_dv_producto ON detalles_venta(producto_id);
-- CREATE INDEX idx_dv_venta ON detalles_venta(venta_id);
-- CREATE INDEX idx_ventas_fecha ON ventas(fecha);
-- CREATE INDEX idx_productos_stock ON productos(stock);
