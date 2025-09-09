<?php
/**
 * Operaciones con Cadenas de Texto
 */

function contar_palabras_repetidas($texto) {
    
    $texto = strtolower($texto);
    $palabras = explode(' ', $texto);
    $contador = array();
    
   
    foreach ($palabras as $palabra) {
        
        $palabra = trim($palabra, ".,!?;:\"'()[]{}");
        
        if (!empty($palabra)) {
            if (isset($contador[$palabra])) {
                $contador[$palabra]++;
            } else {
                $contador[$palabra] = 1;
            }
        }
    }
    
    return $contador;
}


function capitalizar_palabras($texto) {
    
    $texto = strtolower($texto);
    $palabras = explode(' ', $texto);
    $palabras_capitalizadas = array();
    
    foreach ($palabras as $palabra) {
        if (!empty($palabra)) {
            
            $primera_letra = strtoupper(substr($palabra, 0, 1));
            $resto_palabra = substr($palabra, 1);
            $palabra_capitalizada = $primera_letra . $resto_palabra;
            $palabras_capitalizadas[] = $palabra_capitalizada;
        }
    }
    
    return implode(' ', $palabras_capitalizadas);
}
?>

      
