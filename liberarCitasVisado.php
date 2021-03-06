<?php

// libs to import
include_once('lib/main.php');
include_once('lib/jsoncollection.php');

//establecer contacto con la base de datos local
$db = new JsonCollection();

//leer la configuraciones iniciales
$ini_array = parse_ini_file('visado.ini');
//poner a loggear todos los errores y todo todo al archivo local de log
$log_name = (!$ini_array['debug']) ? $ini_array['log_name'].'-'.date('Ymd-Hi') : $ini_array['log_name'] ;
ini_set('error_log', 'logs/'.$log_name.".log");

//poner algunas variables de las iniciales en el scope Global para poder usarlas
$GLOBALS['free.ini'] = $ini_array;

//printear en el log las configs iniciales que se leyeron para que queden
print_initial_configs($ini_array);

//que momento es este del dia
$today = ($GLOBALS['free.ini']['simulate']) ? strtotime($argv[1]) : time();
error_log("Today is ".date("Y-m-d, H:i", $today)." and is a beautiful day.");

// reservar los nuevos turnos hasta la fecha que se haya decidido
$until_date = strtotime($GLOBALS['free.ini']['reservation_period']." days", $today);
reserve_until_date($db->col, $until_date, $today);
// update_until_date($db->col, $until_date, $today);
// explorar algunas fechas
// update_until_date($db->col, strtotime("2017-07-05"), strtotime("2017-07-01"));

// limpiarlas
// foreach ($db->col as $serBD) {
//     $bd = unserialize($serBD);
//     $bd->releaseDay();
// }

// exit;

//hacer los calculos iniciales:
$bDay = $db->getDay(date("Y-m-d", $today));
$GLOBALS['free.ini']['times_opened_today'] = $bDay->exec_count;
//hora en que se van a abrir turnos
//hasta que fecha se va a reservar nuevos turnos
//cuantos turnos por dia se han calculado a partir del dia de hoy
//cuantos turnos se van a repartir mas alla de los 15 primeros dias
//cuales de los dias m'as all'a de 15 son los escogidos para repartir esos turnos
$decisions = initial_calculations($db->col, $today);


//printearlos en el log para que quede
print_initial_decisions($decisions, $today);

try{
    // ejecuta las decisiones
    // buscar en cada d'ia de los proximos 15 dias para que queden liberados tantos como se decida
    // liberar turnos en los dias mas all'a de 15 
    if($GLOBALS['free.ini']['simulate']){
        error_log("Sleeping for 2 secs");
        sleep(2);
        error_log("Lets get this over with...");
    }
    else{
        error_log("Sleeping for ".$decisions['execute_minute']. " mins" );
        sleep($decisions['execute_minute']*60);
        error_log("I'm back baby");
    } 
    execute_decisions($decisions, $db, $today);
    // aumenta la cuenta de ejecuciones en este dia
    $bDay->increaseExec();
    // put day back to the db
    $db->addDay($bDay);
    if($GLOBALS['free.ini']['simulate'] && $decisions['last_lap']){
        error_log("Writing results to the file 'results.cvs'");
        write_results_to_csv('logs/results.csv', $db->col, $decisions['total_slots'], $today);
    }
    unset($db);
} catch (Exception $e){
    error_log($e->getMessage());
}



