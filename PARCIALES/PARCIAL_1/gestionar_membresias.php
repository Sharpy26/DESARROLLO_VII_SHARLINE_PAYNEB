<?php
include 'funciones_gimnasio.php';

$miembros = [
    ["Sharline Payne", "Básica", 2],
    ["Ireyel Gutierrez", "Básica", 6],
    ["Enoc Britton", "Premium", 10],
    ["Angel Cordoba", "Premium", 12],
    ["Nicolle Ripamonte", "VIP", 15]
];

$precios = ["Básica" => 25000, "Premium" => 50000, "VIP" => 75000];
$seguro = 5000;
?>

<table border="1">
<tr>
    <th>Nombre</th>
    <th>Tipo</th>
    <th>Meses</th>
    <th>Precio</th>
    <th>Descuento</th>
    <th>Total</th>
</tr>

<?php foreach ($miembros as $m): ?>
<?php
$cuota = $precios[$m[1]];
$descuento = calcular_promocion($m[2]);
$ahorro = $cuota * ($descuento / 100);
$total = $cuota - $ahorro + $seguro;
?>

<tr>
    <td><?= $m[0] ?></td>
    <td><?= $m[1] ?></td>
    <td><?= $m[2] ?></td>
    <td>$<?= $cuota ?></td>
    <td><?= $descuento ?>%</td>
    <td>$<?= $total ?></td>
</tr>
<?php endforeach; ?>
</table>