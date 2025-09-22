<?php
require_once 'Empresa.php';

// Crear empresa
$empresa = new Empresa("TechSolutions Inc.");

// Crear empleados
$gerente1 = new Gerente("Ana García", "G001", 5000, "TI");
$gerente2 = new Gerente("Carlos López", "G002", 5500, "Ventas");

$dev1 = new Desarrollador("María Rodríguez", "D001", 3000, "PHP", "Senior");
$dev2 = new Desarrollador("Juan Pérez", "D002", 2500, "JavaScript", "Junior");
$dev3 = new Desarrollador("Laura Martínez", "D003", 3500, "Python", "Senior");

// Configurar propiedades específicas
$gerente1->asignarBono(1000);
$dev1->setHorasExtras(10);
$dev2->setHorasExtras(5);

// Agregar empleados a la empresa
$empresa->agregarEmpleado($gerente1)
        ->agregarEmpleado($gerente2)
        ->agregarEmpleado($dev1)
        ->agregarEmpleado($dev2)
        ->agregarEmpleado($dev3);

// Demostrar funcionalidades
echo "<h2>Sistema de Gestión de Empleados</h2>";

// Listar empleados
$empresa->listarEmpleados();
echo "<hr>";

// Calcular nómina
echo "<h3>Nómina Total: $" . number_format($empresa->calcularNominaTotal(), 2) . "</h3>";
echo "<hr>";

// Evaluaciones
$empresa->realizarEvaluaciones();
echo "<hr>";

// Reportes
$empresa->generarReportes();
echo "<hr>";

// Aplicar aumentos
$empresa->aplicarAumentos();
echo "<hr>";

// Guardar datos
$empresa->guardarDatos('empleados.json');
echo "<hr>";

// Mostrar nómina después de aumentos
echo "<h3>Nómina después de aumentos: $" . number_format($empresa->calcularNominaTotal(), 2) . "</h3>";

// Crear nueva empresa y cargar datos
echo "<h2>Cargando datos guardados...</h2>";
$empresa2 = new Empresa("TechSolutions Backup");
$empresa2->cargarDatos('empleados.json');
$empresa2->listarEmpleados();
?>