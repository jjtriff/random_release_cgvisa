<?php

require_once 'lib/bookitClient.php';
require_once 'lib/reservedBookitDay.php';


// function eventFactory($agenda, $start_date, $start_time){
//     $e = new StdClass();
//     $e->agenda_id = $agenda;
//     $e->start_time = $start_time;
//     $e->start_date = $start_date;
//     $e->id = 'created by FACTORY';
//     return $e;
// }


// buscar en un rango de fechas las reservaciones hechas sobre la agenda viajes despues
$desde = strtotime('2017-06-01');
$hasta = strtotime('2017-07-15'); 
// $hasta = strtotime('2017-06-21'); # testing
// $desde = strtotime('2017-06-21'); # testing
$agenda = 'bkt84315'; # visado 
$log_name = 'rebalancing-events';
$capacities = [
    '09:00' => 16,
    '10:00' => 17,
    '11:00' => 17,
    '12:00' => 17,
    '13:00' => 17,
];


ini_set('error_log', 'logs/'.$log_name.".log");

$GLOBALS['free.ini'] = [];
$GLOBALS['free.ini']['retries'] = 0;


$day = $desde;
while ($day <= $hasta) {
    // skip tuesdays
    if(date('w', $day) != 2 ){
        $count = 0;
        try{
            // recuperar los eventos del dia
            $events = \CGHAB\BookititClient\getDateEvents(date('Y-m-d', $day));

// // for testing

// $events->new1 = eventFactory($agenda, date('Y-m-d', $day), '09:00');
// $events->new21 = eventFactory($agenda, date('Y-m-d', $day), '09:00');
// $events->new31 = eventFactory($agenda, date('Y-m-d', $day), '09:00');
// $events->new41 = eventFactory($agenda, date('Y-m-d', $day), '09:00');


            error_log("Day ".date('Y-m-d', $day)." events:");
            // loop over them looking for the ones in $agenda
            $rbd = new ReservedBookitDay(date('Y-m-d',$day), $capacities);
            foreach ($events as $key => $event) {
                //coger los eventos de un dia
                if($event->agenda_id == $agenda){
                    //empezar a acomodarlos de nuevo basados en las capacidades 
                    // de cada horario usando la var $rbd
                    $finalHour = $rbd->placeEvent($event);
                    error_log("Placed event $event->id from $event->start_time to $finalHour");
                    if($event->start_time != $finalHour){
                        $count++;
                    }
                }
            }
            
        } catch(Exception $e){
            error_log($e->getMessage());
        }
        error_log('Day '.date('Y-m-d', $day).': '.$count.' moved events.');
    }
    $day += 24 * 60 * 60; # cantidad de segundos que tiene un dia
}
