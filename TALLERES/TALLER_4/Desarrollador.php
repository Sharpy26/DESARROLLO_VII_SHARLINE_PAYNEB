<?php
require_once 'Empleado.php';
require_once 'Evaluable.php';

class Desarrollador extends Empleado implements Evaluable {
    private $lenguaje;
    private $nivelExperiencia;
    private $horasExtras;

    public function __construct($nombre, $id, $salarioBase, $lenguaje, $nivelExperiencia) {
        parent::__construct($nombre, $id, $salarioBase);
        $this->lenguaje = $lenguaje;
        $this->nivelExperiencia = $nivelExperiencia;
        $this->horasExtras = 0;
    }

    // Getters y Setters específicos
    public function getLenguaje() {
        return $this->lenguaje;
    }

    public function setLenguaje($lenguaje) {
        $this->lenguaje = $lenguaje;
    }

    public function getNivelExperiencia() {
        return $this->nivelExperiencia;
    }

    public function setNivelExperiencia($nivelExperiencia) {
        $this->nivelExperiencia = $nivelExperiencia;
    }

    public function getHorasExtras() {
        return $this->horasExtras;
    }

    public function setHorasExtras($horas) {
        $this->horasExtras = $horas;
    }

    // Implementación del método abstracto
    public function calcularSalario() {
        $pagoHorasExtras = $this->horasExtras * ($this->salarioBase / 160 * 1.5);
        return $this->salarioBase + $pagoHorasExtras;
    }

    // Implementación de la interfaz Evaluable
    public function evaluarDesempenio() {
        // Lógica de evaluación para desarrolladores
        $puntaje = rand(1, 10); // Simulamos una evaluación
        
        if ($puntaje >= 9) {
            return "Excepcional - Puntaje: $puntaje/10";
        } elseif ($puntaje >= 7) {
            return "Muy bueno - Puntaje: $puntaje/10";
        } elseif ($puntaje >= 5) {
            return "Adecuado - Puntaje: $puntaje/10";
        } else {
            return "Deficiente - Puntaje: $puntaje/10";
        }
    }

    public function __toString() {
        return "Desarrollador: {$this->nombre} (ID: {$this->id}) - {$this->lenguaje} ({$this->nivelExperiencia})";
    }
}
?>