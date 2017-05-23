<?php

require_once("randomization.php");
require_once("jsoncollection.php");

function print_initial_configs($ini_array){
    // error_log("Capacidad del departamento de visado es: $ini_array[dept_capacity] personas diarias");
    // $ob = $ini_array['overbooking_percentage'] * 100;
    // error_log("Se trabajar'a con un overbooking de: $ob %");
    // $cd = $ini_array['closest_days_percentage'] * 100;
    // error_log("Para los pr'oximos 15 d'ias se abrir'an un $cp % del total de las citas");
    // error_log("Los porcientos ")
    error_log('Initial settings');
    error_log(print_r($ini_array, true));

}

/**
* makes the calculations and decides what days to open, etc
*
* hacer los calculos iniciales:
* que momento es este del dia
* cantidad d turnos a abrir hoy esparcidos entre los X meses
* $GLOBALS['free.ini']['times_opened_today'] = $bDay->exec_count;
* hora en que se van a abrir turnos
* hasta que fecha se va a reservar nuevos turnos
* cuantos turnos por dia se han calculado a partir del dia de hoy hasta los proximos 15 dias
* cuantos turnos se van a repartir mas alla de los 15 primeros dias
* cuales de los dias m'as all'a de 15 son los escogidos para repartir esos turnos
*
* @param array $dbcol The collection of days in the db to explore them and
* make the decisions
* @param timestamp $now = time() The timestamp of the hour that is used as 
* reference
**/
function initial_calculations(array $dbcol, $now = null)
{
    $now = (!$now) ? time() : $now ;
    
    extract($GLOBALS['free.ini']);
    // BEGIN: que momento es este del dia
    if($times_opened_today >= $times_to_open)
    {        
        error_log("Ilegall tries to open slots. Today: $times_opened_today, Max: $times_to_open");
        throw new Exception("Illegal tries to open slots");
    }
    $lap = $times_opened_today + 1;
    $last_lap = $lap == $times_to_open;

    // BEGIN: cantidad d turnos a abrir hoy esparcidos entre los X meses
    $total_slots = floor($dept_capacity / (1- $overbooking_percentage));

    // BEGIN: cuantos turnos por dia se han calculado a partir del dia de hoy hasta los proximos 15 dias
    $slots2open4nextDays = round($total_slots * $closest_days_percentage);
    

    // BEGIN: hora en que se van a abrir turnos
    $execute_minute = Randomize\SelectMinuteFromNow($time_window * 60);

    // BEGIN: hasta que fecha se va a reservar nuevos turnos
    $reserve_until_date = strtotime("$reservation_period days", $now);

    //determinar primero la cantidad de turnos q se abriran para los proximos 15 dias
    $nextDays = array();

    foreach ($closer_days as $key => $value) {
        $thisDaySlots = round($value * $slots2open4nextDays);
        if(!$last_lap){
            $thisLapSlots = intdiv($thisDaySlots, $times_to_open);
        }
        else{
            $thisLapSlots = intdiv($thisDaySlots, $times_to_open) + $thisDaySlots % $times_to_open;
        }

        $nextDays[$key+1] = ['slots' => $thisDaySlots, 'thisLap' => $thisLapSlots];
    }

    // BEGIN: cuantos turnos se van a repartir mas alla de los 15 primeros dias
    // Se calcula en base a lo q ya se ha repartido entre los cercanos 15 d'ias'
    // para no desperdiciar turnos
    $slots2open4nextDays = 0;
    foreach ($nextDays as $key => $value) {
        $slots2open4nextDays += $value['slots'];
    }
    $slots2open4farDays = $total_slots - $slots2open4nextDays;

    // BEGIN: turnos en dias distantes que se van a repartir en este lap
    if(!$last_lap){
        $thisLapFarSlots = intdiv($slots2open4farDays, $times_to_open);
    }
    else{
        $thisLapFarSlots = intdiv($slots2open4farDays, $times_to_open) + $slots2open4farDays % $times_to_open;
    }

    // BEGIN: cuales de los dias m'as all'a de 15 son los escogidos para repartir esos turnos de este lap
    $farDays = array_slice($dbcol, count($closer_days)+1, -1);
    $InRangeAndNotOpenedYetAndWithReservations = function ($unserializedBookitDay)
        {
            $bd = unserialize($unserializedBookitDay);
            if(!$bd->isOpen() && $bd->hasReservations() == 0)
                return false;
            return true;
        };
    $thisLapSelectedFarDays = Randomize\someFromArray($farDays, $thisLapFarSlots, $InRangeAndNotOpenedYetAndWithReservations);
    $thisLapSelectedFarDays = array_keys($thisLapSelectedFarDays);

    return compact('execute_minute','times_opened_today','lap',
        'last_lap','total_slots','slots2open4nextDays','nextDays',
        'reserve_until_date','slots2open4farDays','thisLapSelectedFarDays');
}

/**
 * prints into the log the array of decisions
 *
 * @param array $decisions The array of decisions made somewhere else
 * @param timestamp $now The timestamp of the hour that is used as 
 * reference
 **/
function print_initial_decisions($decisions, $now = null){
    error_log('Decisions for this lap');
    $decisions['reserve_until_date'] = date('Y-m-d', $decisions['reserve_until_date']);
    error_log(print_r($decisions, true));
    $now = (!$now) ? time() : $now ;
    $will_exec = strtotime($decisions['execute_minute']." minutes", $now);
    error_log(
        "Will execute decisions around: ".
    date("Y-m-d H:i:s", $will_exec)
    );
    if($decisions['last_lap']){
        error_log('This is the last lap');
    }
    else{
        error_log("This is lap number $decisions[lap]");
    }
}

/**
 * Reserves every slots in days from one date to another
 *
 * looks dates in the array, if they are not there it creates it as a new bookitDay object, preserves
 * that day completly and stores it inside the collection, serializing it first.
 *
 * @param array $serializedBookitDays Collection of the serialized bookitdays
 * @param unixtimestamp $toDateTimeStamp Final date of reservations
 * @param unixtimestamp $fromDateTimeStamp Date from which the reservations will start
 **/

function reserve_until_date(array &$serializedBookitDays, $toDateTimeStamp, $fromDateTimeStamp = null)
{
    $fromDateTimeStamp = ($fromDateTimeStamp == null) ? time() + 24*3600 : $fromDateTimeStamp;
    error_log("Reserving events from ".date('Y-m-d',$fromDateTimeStamp)." to ".date('Y-m-d', $toDateTimeStamp).".");
    $fromDateTimeStamp = $fromDateTimeStamp - 24*3600; # esto asegura q cuando comience el ciclo caiga en la fecha correcta que es el dia actual $fromDate

    //generate dates from fromDate + 1 day to dateTimeStamp
    do {
        $fromDateTimeStamp = $fromDateTimeStamp + 24*3600;
        // if the date is already inside the collection is not necessary to preserve it again
        $strdate = date('Y-m-d', $fromDateTimeStamp);
        if(!array_key_exists($strdate, $serializedBookitDays)){
            $bd = new BookitDay($strdate);
            $bd->prereserve();
            $serializedBookitDays[$strdate] = serialize($bd);
        }
        
    } while ($fromDateTimeStamp < $toDateTimeStamp);
}

/**
 * Scans days looking for already reserved events
 *
 * useful if somthing went wrong and you need to update the db
 * 
 * @param array $serializedBookitDays Collection of the serialized bookitdays
 * @param unixtimestamp $toDateTimeStamp Final date of reservations
 * @param unixtimestamp $fromDateTimeStamp Date from which the reservations will start
 **/

function update_until_date(array &$serializedBookitDays, $toDateTimeStamp, $fromDateTimeStamp = null)
{
    $fromDateTimeStamp = ($fromDateTimeStamp == null) ? time() + 24*3600 : $fromDateTimeStamp;
    error_log("Searching for reserved events from ".date('Y-m-d',$fromDateTimeStamp)." to ".date('Y-m-d', $toDateTimeStamp).".");
    $fromDateTimeStamp = $fromDateTimeStamp - 24*3600; # esto asegura q cuando comience el ciclo caiga en la fecha correcta

    //scan dates from fromDate + 1 day to dateTimeStamp
    do {
        $fromDateTimeStamp = $fromDateTimeStamp + 24*3600;
        // if the date is already inside the collection is not necessary to preserve it again
        $strdate = date('Y-m-d', $fromDateTimeStamp);
        $bd = new BookitDay($strdate);
        $bd->updateDay();
        $serializedBookitDays[$strdate] = serialize($bd);
        
    } while ($fromDateTimeStamp < $toDateTimeStamp);
}

/**
 * Executes everything based in the decision array using the BookitDays collection
 *
 * @param array $decisions The array with every decision
 * @param JsonCollection $db The db of BookitDays
 * @param timestamp $now The timestamp of the hour that is used as 
 * reference
 **/
function execute_decisions(array $decisions, JsonCollection &$db, $now = null)
{
    $now = (!$now) ? time() : $now ;

    // liberar primero los turnos de dias cercanos
    // liberando la cantidad q especificada en $nextDays[key][thisLap]
    $first_time = true;
    foreach ($decisions['nextDays'] as $day => $slotsToOpen) {
        // calcular dia basdo en now y en el $day
        $date = date("Y-m-d", strtotime("$day days", $now));
        $bd = $db->getDay($date);
        if($first_time && $decisions['last_lap']){
            $first_time = false;
            $bd->releaseDay();
        }
        else{
            $bd->releaseEvents($slotsToOpen['thisLap']);
        }
        // put BDay back into the db
        $db->addDay($bd);
    }

    // liberar los turnos de dias distantes
    foreach ($decisions['thisLapSelectedFarDays'] as $sDay ) {
        $bd = $db->getDay($sDay);
        $bd->releaseEvents(1);
        // put BDay back into the db
        $db->addDay($bd);
    }
    
}

/**
*   TESTS
*/

// $reservation_period = 4;
// print date("Ymd", strtotime("$reservation_period days"));

// // leer la configuraciones iniciales
// $ini_array = parse_ini_file('../visado.ini');
// //poner a loggear todos los errores y todo todo al archivo local de log
// // ini_set('error_log', 'logs/'.$ini_array['log_name'].date('Ymd-Hi').".log");

// //poner algunas variables de las iniciales en el scope Global para poder usarlas
// $GLOBALS['free.ini'] = $ini_array;


// // $to = strtotime("2017-11-05");
// // $from = strtotime("2017-10-30");

// $db = new JsonCollection('../localdb.json');

// reserve_until_date($db->col, $to, $from);
// print_r($db);

// $bd = $db->getDay("2017-11-03");
// $bd->updateDay();
// print_r($bd);
// $db->addDay($bd);
// print_r ($bd);
// print($bd->Date());
// $bd->releaseEvents(2);
// print_r($bd);
// $db->addDay($bd);

// foreach ($db->col as $key => $value) {
//     $bd = unserialize($value);
//     print_r($bd);
//     $bd->releaseEvents(11);
//     print_r($bd);
//     $db->addDay($bd);
// }

// $t = strtotime("2017-05-13");

// echo date(
// "Y-m-d",
// strtotime("2 days", $t)
// );
