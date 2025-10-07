<?php
// Archivo: clases.php

class Producto {
    public $id;
    public $nombre;
    public $descripcion;
    public $estado;
    public $stock;
    public $fechaIngreso;
    public $categoria;
    
    public $garantiaMeses; // Electrónico
    public $fechaVencimiento; // Alimento
    public $talla; // Ropa

    public function __construct($datos) {
        foreach ($datos as $clave => $valor) {
            if (property_exists($this, $clave)) {
                $this->$clave = $valor;
            }
        }
    }
}

class GestorInventario {
    private $items = [];
    private $rutaArchivo = 'productos.json';

    public function obtenerTodos() {
        if (empty($this->items)) {
            $this->cargarDesdeArchivo();
        }
        return $this->items;
    }

    public function obtenerPorId($id) {
        $this->obtenerTodos();
        foreach ($this->items as $item) {
            if ($item->id == $id) return $item;
        }
        return null;
    }

    public function agregar($datos) {
        $this->obtenerTodos();
        $nuevoId = $this->obtenerMaximoId() + 1;
        $datos['id'] = $nuevoId;
        $datos['fechaIngreso'] = date('Y-m-d');
        $this->items[] = new Producto($datos);
        $this->persistirEnArchivo();
    }

    public function modificar($id, $datos) {
        $this->obtenerTodos();
        foreach ($this->items as $i => $item) {
            if ($item->id == $id) {
                foreach ($datos as $clave => $valor) {
                    if (property_exists($item, $clave)) {
                        $item->$clave = $valor;
                    }
                }
                $this->items[$i] = $item;
                $this->persistirEnArchivo();
                return true;
            }
        }
        return false;
    }

    public function eliminar($id) {
        $this->obtenerTodos();
        foreach ($this->items as $i => $item) {
            if ($item->id == $id) {
                array_splice($this->items, $i, 1);
                $this->persistirEnArchivo();
                return true;
            }
        }
        return false;
    }

    public function cambiarEstado($id, $nuevoEstado) {
        $this->obtenerTodos();
        foreach ($this->items as $i => $item) {
            if ($item->id == $id) {
                $item->estado = $nuevoEstado;
                $this->items[$i] = $item;
                $this->persistirEnArchivo();
                return true;
            }
        }
        return false;
    }

    public function filtrarPorEstado($estado) {
        $this->obtenerTodos();
        return array_filter($this->items, function($item) use ($estado) {
            return $item->estado == $estado;
        });
    }

    public function ordenarPor($campo, $tipo = 'asc') {
        $this->obtenerTodos();
        $items = $this->items;
        usort($items, function($a, $b) use ($campo, $tipo) {
            $v1 = $a->$campo ?? '';
            $v2 = $b->$campo ?? '';
            if ($v1 == $v2) return 0;
            if ($tipo == 'asc') return ($v1 < $v2) ? -1 : 1;
            else return ($v1 > $v2) ? -1 : 1;
        });
        return $items;
    }

    private function cargarDesdeArchivo() {
        if (!file_exists($this->rutaArchivo)) {
            return;
        }
        $jsonContenido = file_get_contents($this->rutaArchivo);
        $arrayDatos = json_decode($jsonContenido, true);
        if ($arrayDatos === null) {
            return;
        }
        foreach ($arrayDatos as $datos) {
            $this->items[] = new Producto($datos);
        }
    }

    private function persistirEnArchivo() {
        $arrayParaGuardar = array_map(function($item) {
            return get_object_vars($item);
        }, $this->items);
        file_put_contents(
            $this->rutaArchivo, 
            json_encode($arrayParaGuardar, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public function obtenerMaximoId() {
        if (empty($this->items)) {
            return 0;
        }
        $ids = array_map(function($item) {
            return $item->id;
        }, $this->items);
        return max($ids);
    }
}