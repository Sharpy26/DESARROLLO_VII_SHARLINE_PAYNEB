<?php
function calcular_promocion($meses) {
    if ($meses < 3) return 0;
    if ($meses <= 12) return 8;
    if ($meses <= 24) return 12;
    return 20;
}
?>