<?php

$fechas = ["2017-10-30"];
$horas = ['08:00', '12:00', '16:00'];


foreach ($fechas as  $fecha) {
    foreach ($horas as $hora) {
        $argv[1] = "$fecha $hora";
        include('liberarCitasVisado.php');
    }
}