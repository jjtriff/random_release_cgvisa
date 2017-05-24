<?php

// $fechas = ["2017-10-30","2017-10-31","2017-11-01","2017-11-02", "2017-11-03", "2017-11-04", "2017-11-05", "2017-11-06"];
$fechas =[



"2017-12-17",
"2017-12-18",
"2017-12-19"

];

$horas = ['08:00', '12:00', '16:00'];


foreach ($fechas as  $fecha) {
    foreach ($horas as $hora) {
        $argv[1] = "$fecha $hora";
        include('liberarCitasVisado.php');
    }
}