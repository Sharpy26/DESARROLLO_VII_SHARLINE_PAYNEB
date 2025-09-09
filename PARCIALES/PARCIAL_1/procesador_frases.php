<?php

include 'operaciones_cadenas.php';

$frases = array(
    "La bebe juega con la otra bebe, pero la otra no quiere jugar con el bebe",
    "Sharline le gusta libros de crecimiento personal",
    "Juan juega con Juan Pablo",
    "Termina lo que empezaste"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Procesador de Frases</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .frase { border: 1px solid #e7bbe5ff; padding: 15px; margin-bottom: 20px; }
        .original { color: #555; font-style: italic; }
        .resultado { background: #fae8f5ff; padding: 10px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Procesador de Frases</h1>
    
    <?php foreach ($frases as $i => $frase): ?>
        <div class="frase">
            <h3>Frase <?= $i+1 ?>:</h3>
            <p class="original">Original: "<?= $frase ?>"</p>
            
            <?php $contador = contar_palabras_repetidas($frase); ?>
            <div class="resultado">
                <h4>Conteo de palabras:</h4>
                <?php foreach ($contador as $palabra => $cantidad): ?>
                    <div><?= $palabra ?>: <?= $cantidad ?></div>
                <?php endforeach; ?>
            </div>
            
            <?php $capitalizada = capitalizar_palabras($frase); ?>
            <div class="resultado">
                <h4>Frase capitalizada:</h4>
                <p>"<?= $capitalizada ?>"</p>
            </div>
        </div>
    <?php endforeach; ?>
</body>
</html>