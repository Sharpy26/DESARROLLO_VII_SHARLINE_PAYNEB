
<?php
// Ejemplo de uso de json_decode() con un JSON simple
$jsonFrutas = '["Fresa, Kiwi, Pitaya, Mango, Banana"]';
$frutas = json_decode($jsonFrutas);
echo "JSON de frutas decodificado:</br>";
print_r($frutas);

// Ejemplo con un JSON de objeto
$jsonPersona = '{"nombre":"Mayleth","edad":23,"ciudad":"Panamá"}';
$persona = json_decode($jsonPersona, true); // true para obtener un array asociativo
echo "</br>JSON de persona decodificado como array:</br>";
print_r($persona);

// Ejercicio: Decodifica el JSON de tu película favorita del ejercicio anterior
$jsonPelicula = '{"titulo":"Tu película","director":"Director","año":2000,"actores":["Actor 1","Actor 2","Actor 3"]}';
$peliculaFavorita = json_decode($jsonPelicula, true);
echo "</br>Información de tu película favorita decodificada:</br>";
print_r($peliculaFavorita);

// Bonus: Trabajar con JSON anidado
$jsonComplejo = '{
    "nombre": "Sharline",
    "edad": 22,
    "hobbies": ["leer", "cocinar", "viajar"],
    "direccion": {
        "calle": "Calle Principal",
        "numero": 123,
        "ciudad": "Barcelona"
    }
}';
$datosComplejos = json_decode($jsonComplejo, true);
echo "</br>JSON complejo decodificado:</br>";
print_r($datosComplejos);

// Extra: Manejo de errores en json_decode()
$jsonInvalido = '{"nombre": "Enoc", "edad": 21,}'; // JSON inválido (coma extra)
$resultado = json_decode($jsonInvalido);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "</br>Error al decodificar JSON: " . json_last_error_msg();
}
?>
      
