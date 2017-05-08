<?php

require_once("randomization.php");

function print_initial_configs($ini_array){
    // error_log("Capacidad del departamento de visado es: $ini_array[dept_capacity] personas diarias");
    // $ob = $ini_array['overbooking_percentage'] * 100;
    // error_log("Se trabajar'a con un overbooking de: $ob %");
    // $cd = $ini_array['closest_days_percentage'] * 100;
    // error_log("Para los pr'oximos 15 d'ias se abrir'an un $cp % del total de las citas");
    // error_log("Los porcientos ")
    error_log('Initial settings');
    error_log(print_r($ini_array));

}

/**
* hacer los calculos iniciales:
* que momento es este del dia
* cantidad d turnos a abrir hoy esparcidos entre los X meses
* $GLOBALS['free.ini']['times_opened_today'] = $bDay->exec_count;
* hora en que se van a abrir turnos
* hasta que fecha se va a reservar nuevos turnos
* cuantos turnos por dia se han calculado a partir del dia de hoy hasta los proximos 15 dias
* cuantos turnos se van a repartir mas alla de los 15 primeros dias
* cuales de los dias m'as all'a de 15 son los escogidos para repartir esos turnos
*/


function initial_calculations(array $dbcol)
{
    
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
    $total_slots = intdiv($dept_capacity, (1- $overbooking_percentage));

    // BEGIN: cuantos turnos por dia se han calculado a partir del dia de hoy hasta los proximos 15 dias
    $slots2open4nextDays = $total_slots * $closest_days_percentage;
    
    // BEGIN: cuantos turnos se van a repartir mas alla de los 15 primeros dias
    $slots2open4farDays = $total_slots - $slots2open4nextDays;

    // BEGIN: hora en que se van a abrir turnos
    $time_to_execute_lap = Randomize\SelectMinuteFromNow($time_window * 60);

    // BEGIN: hasta que fecha se va a reservar nuevos turnos
    $reserve_until_date = date("Y-m-d", strtotime("$reservation_period days"));

    //determinar primero la cantidad de turnos q se abriran para los proximos 15 dias
    $nextDays = array();

    foreach ($closer_days as $key => $value) {
        $thisDaySlots = $value * $slots2open4nextDays;
        if(!$last_lap){
            $thisLapSlots = intdiv($thisDaySlots, $times_to_open);
        }
        else{
            $thisLapSlots = intdiv($thisDaySlots, $times_to_open) + $thisDaySlots % $times_to_open;
        }

        $nextDays[$key+1] = ['slots' => $thisDaySlots, 'thisLap' => $thisLapSlots];
    }


    // BEGIN: cuales de los dias m'as all'a de 15 son los escogidos para repartir esos turnos
    $farDays = array_slice($dbcol, count($closer_days)+1);
    $selectedFarDays = array();
    for ($i=0; $i < $slots2open4farDays; $i++) { 
        
    }

}