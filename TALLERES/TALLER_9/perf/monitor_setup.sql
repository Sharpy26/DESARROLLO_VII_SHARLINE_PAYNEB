-- monitor_setup.sql
-- Crea la tabla usada para registrar tiempos de ejecución de consultas
CREATE TABLE IF NOT EXISTS query_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sql_text MEDIUMTEXT NOT NULL,
  params TEXT DEFAULT NULL,
  duration_ms DECIMAL(10,3) NOT NULL,
  rows_returned INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (created_at)
);

-- Índice sugerido para consultas por duración o tiempo: no crear índices innecesarios
