<?php
session_start();
// Usamos validar_datos.php (contiene sanitizarUsuario/sanitizarContraseña)
require_once __DIR__ . '/validar_datos.php';
require_once __DIR__ . '/validacion_user.php';
require_once __DIR__ . '/BD.php'; 

// Intentar cargar conexion (si existe)
@include_once __DIR__ . '/conexion.php'; // deja $pdo o null

// Verificar si se han enviado los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = sanitizarUsuario($_POST['usuario'] ?? '');
    $password = sanitizarContraseña($_POST['password'] ?? '');
    $perfil = $_POST['perfil'] ?? '';

    // Validar el nombre de usuario, la contraseña y el perfil
    if (validarNombre($usuario) && validarContrasena($password) && validarPerfil($perfil)) {
        $autenticado = false;

        // Si hay conexión a BD, intentar autenticar contra la tabla usuarios
        if (isset($pdo) && $pdo instanceof PDO) {
            try {
                $stmt = $pdo->prepare('SELECT password_hash, perfil FROM usuarios WHERE username = :u LIMIT 1');
                $stmt->execute([':u' => $usuario]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && $row['perfil'] === $perfil && password_verify($password, $row['password_hash'])) {
                    $autenticado = true;
                }
            } catch (Exception $e) {
                // log y continuar con fallback
                error_log('procesar.php DB auth error: ' . $e->getMessage());
            }
        }

        // Fallback a arreglo en BD.php si no autenticó por BD
        if (!$autenticado) {
            if (isset($usuarios[$perfil]) && array_key_exists($usuario, $usuarios[$perfil]) && $usuarios[$perfil][$usuario] === $password) {
                $autenticado = true;
            }
        }

        if ($autenticado) {
            echo "¡Bienvenido, " . htmlspecialchars($usuario) . " como " . htmlspecialchars($perfil) . "!";
            $_SESSION['usuario'] = $usuario;
            $_SESSION['perfil'] = $perfil;
        } else {
            echo "Usuario o contraseña incorrectos.";
        }

    } else {
        echo "Nombre de usuario, contraseña o perfil no válidos.";
    }
}
?>