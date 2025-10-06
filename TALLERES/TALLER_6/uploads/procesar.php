<?php
// Configuración
$max_file_size = 2 * 1024 * 1024; // 2MB
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$uploads_dir = 'uploads/';

// Arrays para almacenamiento
$datos = [];
$errores = [];

// Función para calcular edad desde fecha de nacimiento
function calcularEdad($fecha_nacimiento) {
    $nacimiento = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($nacimiento);
    return $edad->y;
}

// Función para generar nombre único de archivo
function generarNombreUnico($nombre_original) {
    $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
    $nombre_base = pathinfo($nombre_original, PATHINFO_FILENAME);
    $timestamp = time();
    return $nombre_base . '_' . $timestamp . '.' . $extension;
}

// Validar y sanitizar datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Guardar datos temporalmente para persistencia
    file_put_contents('datos_temp.json', json_encode($_POST));
    
    // Nombre
    if (!empty($_POST['nombre'])) {
        $datos['nombre'] = htmlspecialchars(trim($_POST['nombre']));
        if (strlen($datos['nombre']) < 2) {
            $errores[] = "El nombre debe tener al menos 2 caracteres";
        }
    } else {
        $errores[] = "El nombre es obligatorio";
    }

    // Email
    if (!empty($_POST['email'])) {
        $datos['email'] = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El email no es válido";
        }
    } else {
        $errores[] = "El email es obligatorio";
    }

    // NUEVO: Fecha de Nacimiento
    if (!empty($_POST['fecha_nacimiento'])) {
        $datos['fecha_nacimiento'] = $_POST['fecha_nacimiento'];
        
        // Validar fecha
        $fecha_valida = DateTime::createFromFormat('Y-m-d', $datos['fecha_nacimiento']);
        $hoy = new DateTime();
        
        if (!$fecha_valida || $fecha_valida > $hoy) {
            $errores[] = "La fecha de nacimiento no es válida";
        } else {
            // Calcular edad automáticamente
            $datos['edad'] = calcularEdad($datos['fecha_nacimiento']);
            
            // Validar edad mínima
            if ($datos['edad'] < 13) {
                $errores[] = "Debes tener al menos 13 años para registrarte";
            }
        }
    } else {
        $errores[] = "La fecha de nacimiento es obligatoria";
    }

    // Género
    if (!empty($_POST['genero'])) {
        $generos_permitidos = ['masculino', 'femenino', 'otro'];
        $datos['genero'] = $_POST['genero'];
        if (!in_array($datos['genero'], $generos_permitidos)) {
            $errores[] = "Género no válido";
        }
    } else {
        $errores[] = "El género es obligatorio";
    }

    // Intereses
    if (!empty($_POST['intereses'])) {
        $intereses_permitidos = ['programacion', 'diseno', 'musica', 'deportes', 'lectura'];
        $datos['intereses'] = [];
        foreach ($_POST['intereses'] as $interes) {
            if (in_array($interes, $intereses_permitidos)) {
                $datos['intereses'][] = htmlspecialchars($interes);
            }
        }
        if (count($datos['intereses']) === 0) {
            $errores[] = "Selecciona al menos un interés válido";
        }
    } else {
        $errores[] = "Selecciona al menos un interés";
    }

    // País
    if (!empty($_POST['pais'])) {
        $paises_permitidos = ['españa', 'mexico', 'argentina', 'colombia', 'chile'];
        $datos['pais'] = $_POST['pais'];
        if (!in_array($datos['pais'], $paises_permitidos)) {
            $errores[] = "País no válido";
        }
    } else {
        $errores[] = "El país es obligatorio";
    }

    // Biografía
    if (!empty($_POST['biografia'])) {
        $datos['biografia'] = htmlspecialchars(trim($_POST['biografia']));
        if (strlen($datos['biografia']) > 500) {
            $errores[] = "La biografía no puede exceder los 500 caracteres";
        }
    } else {
        $datos['biografia'] = "";
    }

    // Términos
    if (empty($_POST['terminos'])) {
        $errores[] = "Debes aceptar los términos y condiciones";
    } else {
        $datos['terminos'] = "Aceptado";
    }

    // Foto de perfil
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE) {
        $foto = $_FILES['foto_perfil'];
        
        // Validar error de subida
        if ($foto['error'] !== UPLOAD_ERR_OK) {
            $errores[] = "Error al subir la foto: " . $foto['error'];
        } else {
            // Validar tipo de archivo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $foto['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_types)) {
                $errores[] = "Tipo de archivo no permitido. Use JPG, PNG o GIF";
            }
            
            // Validar tamaño
            if ($foto['size'] > $max_file_size) {
                $errores[] = "La imagen es demasiado grande (Máx. 2MB)";
            }
            
            // Generar nombre único para evitar sobrescritura
            $nombre_unico = generarNombreUnico($foto['name']);
            $ruta_destino = $uploads_dir . $nombre_unico;
            
            // Verificar si el archivo ya existe (aunque con nombre único es improbable)
            if (file_exists($ruta_destino)) {
                $nombre_unico = generarNombreUnico($foto['name']); // Generar otro nombre
                $ruta_destino = $uploads_dir . $nombre_unico;
            }
            
            // Mover archivo
            if (empty($errores) {
                if (move_uploaded_file($foto['tmp_name'], $ruta_destino)) {
                    $datos['foto_perfil'] = $ruta_destino;
                } else {
                    $errores[] = "Error al guardar la imagen";
                }
            }
        }
    } else {
        $datos['foto_perfil'] = "No subida";
    }

    // Limpiar datos temporales si no hay errores
    if (empty($errores) {
        if (file_exists('datos_temp.json')) {
            unlink('datos_temp.json');
        }
    }
}

// Mostrar resultados
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesamiento Completado - TALLER_6</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        img { border-radius: 5px; }
        .actions { margin: 20px 0; }
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
    <h1>Procesamiento del Formulario</h1>
    
    <?php if (empty($errores)): ?>
        <div class="success">¡Formulario procesado exitosamente!</div>
        
        <h2>Datos Recibidos:</h2>
        <table>
            <tr>
                <th>Campo</th>
                <th>Valor</th>
            </tr>
            <?php foreach ($datos as $campo => $valor): ?>
            <tr>
                <td><strong><?php echo ucfirst(str_replace('_', ' ', $campo)); ?></strong></td>
                <td>
                    <?php if ($campo === 'intereses'): ?>
                        <?php echo implode(", ", array_map('ucfirst', $valor)); ?>
                    <?php elseif ($campo === 'foto_perfil' && $valor !== 'No subida'): ?>
                        <img src="<?php echo $valor; ?>" width="100" alt="Foto de perfil">
                    <?php elseif ($campo === 'foto_perfil'): ?>
                        <?php echo $valor; ?>
                    <?php else: ?>
                        <?php echo $valor; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <?php
        // Guardar en archivo JSON para persistencia
        $registro = [
            'timestamp' => date('Y-m-d H:i:s'),
            'datos' => $datos
        ];
        
        $registros_existentes = [];
        if (file_exists('datos.json')) {
            $registros_existentes = json_decode(file_get_contents('datos.json'), true) ?: [];
        }
        
        $registros_existentes[] = $registro;
        file_put_contents('datos.json', json_encode($registros_existentes, JSON_PRETTY_PRINT));
        ?>
        
    <?php else: ?>
        <div class="error">Se encontraron errores en el formulario:</div>
        <ul>
            <?php foreach ($errores as $error): ?>
                <li class="error"><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    
    <div class="actions">
        <?php if (!empty($errores)): ?>
            <a href="formulario.html?datos_previos=1">Volver al formulario (con datos guardados)</a>
        <?php endif; ?>
        <a href="formulario.html">Nuevo formulario</a>
        <a href="resumen.php">Ver todos los registros</a>
    </div>
</body>
</html>