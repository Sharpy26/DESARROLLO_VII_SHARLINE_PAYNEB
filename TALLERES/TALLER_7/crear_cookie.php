<?php
// Configurar una cookie segura
setcookie("usuario", "Sharline Payne", [
    'expires' => time() + 3600,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

echo "Cookie segura 'usuario' creada.";
?>