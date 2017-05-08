<?php

// libs to import
include('lib/main.php');
include('lib/jsoncollection.php');

//establecer contacto con la base de datos local
$db = new JsonCollection();

//leer la configuraciones iniciales
$ini_array = parse_ini_file('visado.ini');
//poner a loggear todos los errores y todo todo al archivo local de log
ini_set('error_log', 'logs/'.$ini_array['log_name'].date('Ymd-Hi').".log");

//printear en el log las configs iniciales que se leyeron para que queden
print_initial_configs($ini_array);

//poner algunas variables de las iniciales en el scope Global para poder usarlas
$GLOBALS['free.ini'] = $ini_array;

//hacer los calculos iniciales:
//que momento es este del dia
$sToday = date("Y-m-d");
$bDay = $db->getDay($sToday);
$GLOBALS['free.ini']['times_opened_today'] = $bDay->exec_count;
//hora en que se van a abrir turnos
//hasta que fecha se va a reservar nuevos turnos
//cuantos turnos por dia se han calculado a partir del dia de hoy
//cuantos turnos se van a repartir mas alla de los 15 primeros dias
//cuales de los dias m'as all'a de 15 son los escogidos para repartir esos turnos
$decisions = initial_calculations($db->col);

//printearlos en el log para que quede
print_initial_decisions($decisions);

try{
    // ejecuta las decisiones
    // reservar los nuevos turnos hasta la fecha que se haya decidido
    // buscar en cada d'ia de los proximos 15 dias para que queden liberados tantos como se decida
    // liberar turnos en los dias mas all'a de 15 
    execute_decisions($decisions);
} catch (Exception $e){
    error_log($e->getMessage());
}



