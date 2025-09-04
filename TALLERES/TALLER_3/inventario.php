<?php


define('INVENTARIO_FILE', 'inventario.json');


function leerInventario() {
    // Verificar si el archivo existe
    if (!file_exists(INVENTARIO_FILE)) {
        die("Error: El archivo " . INVENTARIO_FILE . " no existe.\n");
    }
    
    // Leer el contenido del archivo JSON
    $jsonContent = file_get_contents(INVENTARIO_FILE);
    
    if ($jsonContent === false) {
        die("Error: No se pudo leer el archivo " . INVENTARIO_FILE . ".\n");
    }
    
    // Convertir JSON a array asociativo
    $inventario = json_decode($jsonContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Error: El archivo JSON contiene errores: " . json_last_error_msg() . "\n");
    }
    
    return $inventario;
}


function ordenarInventarioPorNombre($inventario) {
    usort($inventario, function($a, $b) {
        return strcmp($a['nombre'], $b['nombre']);
    });
    
    return $inventario;
}


function mostrarResumenInventario($inventario) {
    echo "=== RESUMEN DEL INVENTARIO (Ordenado alfabéticamente) ===\n";
    echo "NOMBRE          PRECIO   CANTIDAD   VALOR     \n";
    echo "--------------------------------------------------\n";
    
    $inventarioOrdenado = ordenarInventarioPorNombre($inventario);
    
    foreach ($inventarioOrdenado as $producto) {
        $valor = $producto['precio'] * $producto['cantidad'];
        printf("%-15s %7s %10d %10s\n", 
               $producto['nombre'], 
               '$' . number_format($producto['precio'], 2),
               $producto['cantidad'],
               '$' . number_format($valor, 2));
    }
    echo "\n";
}


function calcularValorTotalInventario($inventario) {
    $valoresProductos = array_map(function($producto) {
        return $producto['precio'] * $producto['cantidad'];
    }, $inventario);
    
    return array_sum($valoresProductos);
}


function generarInformeStockBajo($inventario, $umbralStock = 5) {
    $productosStockBajo = array_filter($inventario, function($producto) use ($umbralStock) {
        return $producto['cantidad'] < $umbralStock;
    });
    
    return $productosStockBajo;
}


function mostrarInformeStockBajo($productosStockBajo) {
    if (empty($productosStockBajo)) {
        echo "No hay productos con stock bajo.\n";
        return;
    }
    
    echo "=== INFORME DE PRODUCTOS CON STOCK BAJO (< 5 unidades) ===\n";
    echo "NOMBRE          CANTIDAD   \n";
    echo "-------------------------\n";
    
    foreach ($productosStockBajo as $producto) {
        printf("%-15s %10d\n", $producto['nombre'], $producto['cantidad']);
    }
    echo "\n";
}

// Script principal
function main() {
    echo "SISTEMA DE GESTIÓN DE INVENTARIO\n";
    echo "================================\n\n";
    
    try {
        // 1. Leer el inventario d
        $inventario = leerInventario();
        
        // 2. Mostrar resumen 
        mostrarResumenInventario($inventario);
        
        // 3. Calcular y mostrar el valor 
        $valorTotal = calcularValorTotalInventario($inventario);
        echo "Valor total del inventario: $" . number_format($valorTotal, 2) . "\n\n";
        
        // 4. Generar y mostrar 
        $productosStockBajo = generarInformeStockBajo($inventario);
        mostrarInformeStockBajo($productosStockBajo);
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Ejecutar el script principal
main();
?>