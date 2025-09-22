<?php
require_once 'Empleado.php';
require_once 'Evaluable.php';

class Gerente extends Empleado implements Evaluable {
    private $departamento;
    private $bono;

    public function __construct($nombre, $id, $salarioBase, $departamento) {
        parent::__construct($nombre, $id, $salarioBase);
        $this->departamento = $departamento;
        $this->bono = 0;
    }

    // Getters y Setters específicos
    public function getDepartamento() {
        return $this->departamento;
    }

    public function setDepartamento($departamento) {
        $this->departamento = $departamento;
    }

    public function getBono() {
        return $this->bono;
    }

    // Método para asignar bono
    public function asignarBono($monto) {
        $this->bono = $monto;
        return $this;
    }

    // Implementación del método abstracto
    public function calcularSalario() {
        return $this->salarioBase + $this->bono;
    }

    // Implementación de la interfaz Evaluable
    public function evaluarDesempenio() {
        // Lógica de evaluación para gerentes
        $puntaje = rand(1, 10); // Simulamos una evaluación
        
        if ($puntaje >= 8) {
            return "Excelente - Puntaje: $puntaje/10";
        } elseif ($puntaje >= 5) {
            return "Bueno - Puntaje: $puntaje/10";
        } else {
            return "Necesita mejorar - Puntaje: $puntaje/10";
        }
    }

    public function __toString() {
        return "Gerente: {$this->nombre} (ID: {$this->id}) - Depto: {$this->departamento}";
    }
}
?>
