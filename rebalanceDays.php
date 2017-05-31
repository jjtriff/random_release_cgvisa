<?php

include_once '/home/jtriff/workspace/visado/lib/bookitClient.php';


// buscar en un rango de fechas las reservaciones hechas sobre la agenda viajes despues
// $hasta = strtotime('2017-07-15'); 
// $desde = strtotime('2017-06-01');
$hasta = strtotime('2017-06-21'); # testing
$desde = strtotime('2017-06-21'); # testing
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
            // loop over them looking for the ones in $origin_agenda
            $this_day_events = [];
            foreach ($events as $key => $event) {
                //coger los eventos de un dia
                if($event->agenda_id == $origin_agenda){
                    if(!array_key_exists($event->start_time, $this_day_events)){
                        $this_day_events[$event->start_time] = [];
                    }
                    //separarlos por horarios
                    $this_day_events[$event->start_time][] = $event;
                }
            }
            
            //empezar a acomodarlos de nuevo basados en las capacidades de cada horario


        } catch(Exception $e){
            error_log($e->getMessage());
        }
        error_log('Day '.date('Y-m-d', $day).': '.$count.' moved events.');
        $day += 24 * 60 * 60; # cantidad de segundos que tiene un dia
    }
}
