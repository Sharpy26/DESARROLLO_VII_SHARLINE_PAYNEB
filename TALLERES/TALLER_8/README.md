# Sistema de Gestión de Biblioteca - TALLER_8

Este proyecto contiene dos implementaciones de un sistema simple de gestión de biblioteca:

- `mysqli/` - versión usando MySQLi procedural con prepared statements.
- `pdo/` - versión usando PDO con prepared statements y transacciones.

## Estructura de la base de datos (sugerida)

Tablas mínimas:

CREATE TABLE `libros` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titulo` VARCHAR(255) NOT NULL,
  `autor` VARCHAR(255) NOT NULL,
  `isbn` VARCHAR(50),
  `anio_publicacion` INT,
  `cantidad` INT DEFAULT 0
);

CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL
);

CREATE TABLE `prestamos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `libro_id` INT NOT NULL,
  `fecha_prestamo` DATETIME NOT NULL,
  `fecha_devolucion` DATETIME DEFAULT NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  FOREIGN KEY (libro_id) REFERENCES libros(id)
);

Índices recomendados:

- `CREATE INDEX idx_libros_isbn ON libros(isbn);`
- `CREATE INDEX idx_libros_autor ON libros(autor);`
- `CREATE INDEX idx_prestamos_usuario ON prestamos(usuario_id);`
- `CREATE INDEX idx_prestamos_libro ON prestamos(libro_id);`

## Cómo usar

1. Crear la base de datos `biblioteca` y ejecutar las sentencias SQL anteriores.
2. Ajustar credenciales en `mysqli/config.php` y `pdo/config.php` si es necesario.
3. Abrir en el navegador (ejemplos):
   - `TALLER_8/mysqli/index.php`
   - `TALLER_8/pdo/index.php`

Cada carpeta contiene módulos con funciones para CRUD de `libros`, `usuarios` y `prestamos`.

## Notas técnicas

- Todas las consultas que reciben entrada del usuario usan prepared statements para prevenir inyección SQL.
- Las operaciones que modifican stock y préstamos usan transacciones para asegurar consistencia.
- Se realiza validación y sanitización mínima; en producción añade validaciones más estrictas y manejo de sesiones/autenticación.
- Las funciones de listado soportan paginación.

## Comparativa breve: MySQLi vs PDO

- PDO: interfaz basada en objetos, soporte de múltiples DBMS, binding flexible, manejo de excepciones integrado.
- MySQLi: ligeramente más simple para MySQL y con soporte procedural; puede ser marginalmente más rápido en algunos casos.

En este ejercicio ambas implementaciones proporcionan la misma funcionalidad; elegir entre ellas depende de preferencias del proyecto y soporte futuro.

---

Si quieres, puedo:
- Añadir formularios HTML para las operaciones (crear/editar) y rutas REST básicas.
- Escribir scripts SQL de ejemplo con datos de prueba.
- Ejecutar un commit con estos archivos.
