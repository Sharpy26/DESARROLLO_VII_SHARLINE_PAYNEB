<?php
// audit_logger.php
// Helper para registrar transacciones fallidas en la tabla audit_logs
require_once __DIR__ . '/../perf/config_pdo.php';

class AuditLogger {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function logFailure(string $context, array $details = [], string $errorMessage = '') {
        $stmt = $this->pdo->prepare("INSERT INTO audit_logs (context, details, error_message) VALUES (?, ?, ?)");
        $detailsJson = json_encode($details, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $stmt->execute([$context, $detailsJson, $errorMessage]);
    }
}

// Uso rápido cuando se invoca directamente
if (php_sapi_name() !== 'cli') {
    echo "Audit logger ready.\n";
}
