<?php
// ... (código anterior de procesar.php)

// Modificar la sección de mostrar resultados
if (empty($errores)) {
    echo "<h2>Datos Recibidos:</h2>";
    echo "<table border='1'>";
    foreach ($datos as $campo => $valor) {
        echo "<tr>";
        echo "<th>" . ucfirst($campo) . "</th>";
        if ($campo === 'intereses') {
            echo "<td>" . implode(", ", $valor) . "</td>";
        } elseif ($campo === 'foto_perfil') {
            echo "<td><img src='$valor' width='100'></td>";
        } else {
            echo "<td>$valor</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<h2>Errores:</h2>";
    echo "<ul>";
    foreach ($errores as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}

echo "<br><a href='formulario.html'>Volver al formulario</a>";
?>