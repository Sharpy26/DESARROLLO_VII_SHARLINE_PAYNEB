-- sample_data.sql
-- Script to create a minimal schema and populate synthetic data for performance tests.
-- Assumptions: MySQL 5.7+ / 8.x. If your server is older, adapt the generator technique.
-- WARNING: Run on a test database only. Adjust N_PRODUCTOS, N_VENTAS, etc. to taste.

SET @N_PRODUCTOS = 500;    -- number of products to generate
SET @N_CLIENTES  = 200;    -- number of clients
SET @N_VENTAS    = 2000;   -- number of sales (ventas)

-- 1) Minimal schema (does NOT attempt to perfectly mirror your production schema)
CREATE TABLE IF NOT EXISTS productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(200) NOT NULL,
  categoria_id INT DEFAULT 1,
  precio DECIMAL(10,2) NOT NULL,
  stock INT DEFAULT 0,
  estado ENUM('activo','inactivo') DEFAULT 'activo',
  INDEX (categoria_id),
  INDEX (estado)
);

CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(200) NOT NULL,
  email VARCHAR(200) DEFAULT NULL,
  estado ENUM('activo','suspendido') DEFAULT 'activo'
);

CREATE TABLE IF NOT EXISTS ventas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  fecha DATE NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  INDEX (cliente_id),
  INDEX (fecha)
);

CREATE TABLE IF NOT EXISTS detalles_venta (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venta_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  INDEX (venta_id),
  INDEX (producto_id)
);

-- 2) Helper digits table (0..9) used to build bigger sets via cross join
DROP TEMPORARY TABLE IF EXISTS digits;
CREATE TEMPORARY TABLE digits (d INT PRIMARY KEY) ENGINE=MEMORY;
INSERT INTO digits (d) VALUES (0),(1),(2),(3),(4),(5),(6),(7),(8),(9);

-- 3) Populate productos (bulk generation)
DELETE FROM productos;
INSERT INTO productos (nombre, categoria_id, precio, stock, estado)
SELECT
  CONCAT('Producto ', t.rnum) AS nombre,
  (t.rnum % 12) + 1 AS categoria_id,
  ROUND( (RAND() * 200) + 1, 2) AS precio,
  FLOOR(RAND() * 500) AS stock,
  IF(RAND() > 0.05, 'activo', 'inactivo') AS estado
FROM (
  SELECT (a.d + b.d*10 + c.d*100) AS rnum
  FROM digits a CROSS JOIN digits b CROSS JOIN digits c
) t
WHERE t.rnum BETWEEN 1 AND @N_PRODUCTOS;

-- 4) Populate clientes
DELETE FROM clientes;
INSERT INTO clientes (nombre, email, estado)
SELECT
  CONCAT('Cliente ', n) AS nombre,
  CONCAT('cliente', n, '@example.test') AS email,
  IF(RAND() > 0.02, 'activo', 'suspendido')
FROM (
  SELECT (a.d + b.d*10 + c.d*100) AS n FROM digits a CROSS JOIN digits b CROSS JOIN digits c
) t
WHERE t.n BETWEEN 1 AND @N_CLIENTES;

-- 5) Populate ventas
DELETE FROM ventas;
INSERT INTO ventas (cliente_id, fecha, total)
SELECT
  FLOOR(RAND() * (@N_CLIENTES)) + 1 AS cliente_id,
  DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 365) DAY) AS fecha,
  ROUND( (RAND() * 500) + 20, 2) AS total
FROM (
  SELECT (a.d + b.d*10 + c.d*100) AS n FROM digits a CROSS JOIN digits b CROSS JOIN digits c
) t
WHERE t.n BETWEEN 1 AND @N_VENTAS;

-- 6) Populate detalles_venta: 1..5 items per venta, random products
DELETE FROM detalles_venta;
INSERT INTO detalles_venta (venta_id, producto_id, cantidad, precio_unitario)
SELECT
  v.id AS venta_id,
  FLOOR(RAND() * @N_PRODUCTOS) + 1 AS producto_id,
  FLOOR(RAND() * 5) + 1 AS cantidad,
  ROUND( (RAND() * 200) + 1, 2) AS precio_unitario
FROM (
  SELECT id FROM ventas
) v
CROSS JOIN (
  SELECT 1 AS x UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
) qty
WHERE RAND() < 0.6; -- ~60% of the cross products create a detalle (so avg ~3 items per venta)

-- 7) Simple consistency: update ventas.total from detalles_venta
UPDATE ventas v
JOIN (
  SELECT venta_id, SUM(cantidad * precio_unitario) AS s FROM detalles_venta GROUP BY venta_id
) dv ON dv.venta_id = v.id
SET v.total = dv.s;

-- Done
SELECT 'sample_data_loaded' AS status, COUNT(*) AS productos_count FROM productos;
