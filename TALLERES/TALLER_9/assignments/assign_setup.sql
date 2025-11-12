-- assign_setup.sql
-- Setup auxiliar para la asignación: tablas de auditoría y coordinación de intents.
-- Ejecutar en la base de datos de pruebas.

CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  context VARCHAR(100) NOT NULL,
  details MEDIUMTEXT,
  error_message TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (context),
  INDEX (created_at)
);

CREATE TABLE IF NOT EXISTS tx_intents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tx_key VARCHAR(100) NOT NULL,
  payload JSON NULL,
  state ENUM('pending','prepared','committed','aborted') NOT NULL DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (tx_key),
  INDEX (state)
);

-- Opcional: añadir columna version a productos para control optimista
-- Ejecuta solo si estás en ambiente de pruebas y comprendes el cambio
-- ALTER TABLE productos ADD COLUMN version INT NOT NULL DEFAULT 1;

SELECT 'assign_setup_ok' AS status;
