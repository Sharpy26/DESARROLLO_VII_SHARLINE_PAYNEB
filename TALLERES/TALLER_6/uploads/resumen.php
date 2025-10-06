<?php
// Página para mostrar resumen de todos los registros
$registros = [];

if (file_exists('datos.json')) {
    $registros = json_decode(file_get_contents('datos.json'), true) ?: [];
}

// Estadísticas
$total_registros = count($registros);
$paises = [];
$generos = [];
$edades = [];

foreach ($registros as $registro) {
    $datos = $registro['datos'];
    
    // Conteo por país
    $pais = $datos['pais'] ?? 'Desconocido';
    $paises[$pais] = ($paises[$pais] ?? 0) + 1;
    
    // Conteo por género
    $genero = $datos['genero'] ?? 'Desconocido';
    $generos[$genero] = ($generos[$genero] ?? 0) + 1;
    
    // Edades para promedio
    if (isset($datos['edad'])) {
        $edades[] = $datos['edad'];
    }
}

$edad_promedio = $edades ? array_sum($edades) / count($edades) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de Registros - TALLER_6</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .stats { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .stats h3 { margin-top: 0; }
        img { border-radius: 5px; max-width: 80px; }
        .actions a { 
            display: inline-block; 
            padding: 10px 15px; 
            background: #007bff; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            margin-right: 10px;
        }
        .actions a:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>Resumen de Todos los Registros</h1>
    
    <div class="actions">
        <a href="formulario.html">Nuevo Registro</a>
        <a href="procesar.php">Procesar Formulario</a>
    </div>
    
    <?php if ($total_registros > 0): ?>
        
        <div class="stats">
            <h3>Estadísticas Generales</h3>
            <p><strong>Total de registros:</strong> <?php echo $total_registros; ?></p>
            <p><strong>Edad promedio:</strong> <?php echo number_format($edad_promedio, 1); ?> años</p>
            
            <h4>Distribución por País:</h4>
            <ul>
                <?php foreach ($paises as $pais => $cantidad): ?>
                    <li><?php echo ucfirst($pais) . ": " . $cantidad; ?> registros</li>
                <?php endforeach; ?>
            </ul>
            
            <h4>Distribución por Género:</h4>
            <ul>
                <?php foreach ($generos as $genero => $cantidad): ?>
                    <li><?php echo ucfirst($genero) . ": " . $cantidad; ?> registros</li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <h2>Detalle de Registros</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha/Hora</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Edad</th>
                    <th>Género</th>
                    <th>País</th>
                    <th>Intereses</th>
                    <th>Foto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($registros) as $registro): ?>
                <tr>
                    <td><?php echo $registro['timestamp']; ?></td>
                    <td><?php echo htmlspecialchars($registro['datos']['nombre'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($registro['datos']['email'] ?? ''); ?></td>
                    <td><?php echo $registro['datos']['edad'] ?? ''; ?> años</td>
                    <td><?php echo ucfirst($registro['datos']['genero'] ?? ''); ?></td>
                    <td><?php echo ucfirst($registro['datos']['pais'] ?? ''); ?></td>
                    <td>
                        <?php 
                        if (isset($registro['datos']['intereses'])) {
                            echo implode(", ", array_map('ucfirst', $registro['datos']['intereses']));
                        }
                        ?>
                    </td>
                    <td>
                        <?php if (isset($registro['datos']['foto_perfil']) && $registro['datos']['foto_perfil'] !== 'No subida'): ?>
                            <img src="<?php echo $registro['datos']['foto_perfil']; ?>" alt="Foto">
                        <?php else: ?>
                            No disponible
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
    <?php else: ?>
        <p>No hay registros almacenados aún.</p>
        <p><a href="formulario.html">Crear el primer registro</a></p>
    <?php endif; ?>
</body>
</html>