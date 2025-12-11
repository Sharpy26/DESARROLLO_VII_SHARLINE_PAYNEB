-- Restaurante schema
CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurante_db;

-- Menu 
CREATE TABLE IF NOT EXISTS menu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  categoria VARCHAR(80) DEFAULT NULL,
  precio DECIMAL(10,2) NOT NULL
);

-- Reservación
CREATE TABLE IF NOT EXISTS reservas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre_cliente VARCHAR(150) NOT NULL,
  telefono VARCHAR(50) DEFAULT NULL,
  fecha DATE NOT NULL,
  hora TIME NOT NULL,
  personas INT NOT NULL,
  creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Ingredientes / inventario
CREATE TABLE IF NOT EXISTS inventario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ingrediente VARCHAR(150) NOT NULL,
  cantidad INT NOT NULL DEFAULT 0,
  unidad VARCHAR(20) DEFAULT 'u'
);

-- Ordern
CREATE TABLE IF NOT EXISTS pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mesa VARCHAR(50) DEFAULT NULL,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  estado ENUM('pendiente','en_preparacion','listo') DEFAULT 'pendiente',
  creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orden items
CREATE TABLE IF NOT EXISTS pedido_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT NOT NULL,
  menu_id INT NOT NULL,
  cantidad INT NOT NULL DEFAULT 1,
  precio DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  FOREIGN KEY (menu_id) REFERENCES menu(id)
);


