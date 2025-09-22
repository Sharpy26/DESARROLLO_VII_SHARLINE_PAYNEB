<?php
require_once 'Gerente.php';
require_once 'Desarrollador.php';

class Empresa {
    private $empleados = [];
    private $nombre;

    public function __construct($nombre) {
        $this->nombre = $nombre;
    }

    // Agregar empleados
    public function agregarEmpleado(Empleado $empleado) {
        $this->empleados[] = $empleado;
        return $this;
    }

    // Listar todos los empleados
    public function listarEmpleados() {
        echo "<h3>Empleados de {$this->nombre}</h3>";
        foreach ($this->empleados as $empleado) {
            echo $empleado . "<br>";
        }
    }

    // Calcular nómina total
    public function calcularNominaTotal() {
        $total = 0;
        foreach ($this->empleados as $empleado) {
            $total += $empleado->calcularSalario();
        }
        return $total;
    }

    // Realizar evaluaciones de desempeño
    public function realizarEvaluaciones() {
        echo "<h3>Evaluaciones de Desempeño</h3>";
        foreach ($this->empleados as $empleado) {
            if ($empleado instanceof Evaluable) {
                $evaluacion = $empleado->evaluarDesempenio();
                echo "{$empleado->getNombre()}: $evaluacion<br>";
            }
        }
    }

    // Aumento de salario basado en evaluación (Desafío adicional)
    public function aplicarAumentos() {
        echo "<h3>Aplicando Aumentos de Salario</h3>";
        foreach ($this->empleados as $empleado) {
            if ($empleado instanceof Evaluable) {
                $evaluacion = $empleado->evaluarDesempenio();
                
                if (strpos($evaluacion, 'Excelente') !== false || 
                    strpos($evaluacion, 'Excepcional') !== false) {
                    $nuevoSalario = $empleado->getSalarioBase() * 1.15;
                    $empleado->setSalarioBase($nuevoSalario);
                    echo "{$empleado->getNombre()}: +15% de aumento<br>";
                } elseif (strpos($evaluacion, 'Muy bueno') !== false) {
                    $nuevoSalario = $empleado->getSalarioBase() * 1.10;
                    $empleado->setSalarioBase($nuevoSalario);
                    echo "{$empleado->getNombre()}: +10% de aumento<br>";
                }
            }
        }
    }

    // Generar reportes (Desafío adicional)
    public function generarReportes() {
        echo "<h3>Reportes de la Empresa</h3>";
        
        // Por departamento
        $porDepartamento = [];
        foreach ($this->empleados as $empleado) {
            if ($empleado instanceof Gerente) {
                $depto = $empleado->getDepartamento();
                $porDepartamento[$depto][] = $empleado;
            }
        }
        
        echo "<h4>Empleados por Departamento:</h4>";
        foreach ($porDepartamento as $depto => $empleados) {
            echo "<strong>$depto:</strong> " . count($empleados) . " empleados<br>";
        }

        // Salario promedio por tipo
        $totalGerentes = 0;
        $countGerentes = 0;
        $totalDesarrolladores = 0;
        $countDesarrolladores = 0;

        foreach ($this->empleados as $empleado) {
            if ($empleado instanceof Gerente) {
                $totalGerentes += $empleado->calcularSalario();
                $countGerentes++;
            } elseif ($empleado instanceof Desarrollador) {
                $totalDesarrolladores += $empleado->calcularSalario();
                $countDesarrolladores++;
            }
        }

        echo "<h4>Salarios Promedio:</h4>";
        if ($countGerentes > 0) {
            echo "Gerentes: $" . number_format($totalGerentes / $countGerentes, 2) . "<br>";
        }
        if ($countDesarrolladores > 0) {
            echo "Desarrolladores: $" . number_format($totalDesarrolladores / $countDesarrolladores, 2) . "<br>";
        }
    }

    // Guardar y cargar datos (Desafío adicional)
    public function guardarDatos($archivo) {
        $datos = [];
        foreach ($this->empleados as $empleado) {
            $datos[] = [
                'tipo' => get_class($empleado),
                'nombre' => $empleado->getNombre(),
                'id' => $empleado->getId(),
                'salarioBase' => $empleado->getSalarioBase(),
                'departamento' => $empleado instanceof Gerente ? $empleado->getDepartamento() : null,
                'lenguaje' => $empleado instanceof Desarrollador ? $empleado->getLenguaje() : null,
                'nivelExperiencia' => $empleado instanceof Desarrollador ? $empleado->getNivelExperiencia() : null
            ];
        }
        
        file_put_contents($archivo, json_encode($datos, JSON_PRETTY_PRINT));
        echo "Datos guardados en $archivo<br>";
    }

    public function cargarDatos($archivo) {
        if (file_exists($archivo)) {
            $datos = json_decode(file_get_contents($archivo), true);
            
            foreach ($datos as $dato) {
                if ($dato['tipo'] === 'Gerente') {
                    $empleado = new Gerente(
                        $dato['nombre'],
                        $dato['id'],
                        $dato['salarioBase'],
                        $dato['departamento']
                    );
                } elseif ($dato['tipo'] === 'Desarrollador') {
                    $empleado = new Desarrollador(
                        $dato['nombre'],
                        $dato['id'],
                        $dato['salarioBase'],
                        $dato['lenguaje'],
                        $dato['nivelExperiencia']
                    );
                }
                
                $this->agregarEmpleado($empleado);
            }
            
            echo "Datos cargados desde $archivo<br>";
        }
    }
}
?>