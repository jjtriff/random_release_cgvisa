<?php

include_once '/home/jtriff/workspace/visado/lib/bookitClient.php';

// buscar en un rango de fechas las reservaciones hechas sobre la agenda viajes despues
// $desde = strtotime('2017-03-03'); # primeras reservas de viaje desp de 30
$hasta = strtotime('2017-09-18'); # ultimas reservas de viaje desp de 30
$desde = strtotime('2017-09-17');
// $hasta = strtotime('2017-06-06');
$origin_agenda = 'bkt93896'; # viaje desp de 30
// $origin_agenda = 'bkt103664'; # pruebas
$destination_agenda = 'bkt84315'; # visado 
$log_name = 'moving-events-to-visado';
ini_set('error_log', 'logs/'.$log_name.".log");

$GLOBALS['free.ini'] = [];
$GLOBALS['free.ini']['retries'] = 0;


$day = $desde;
while ($day <= $hasta) {
    
    $count = 0;
    try{
        // recuperar los eventos del dia
        $events = \CGHAB\BookititClient\getDateEvents(date('Y-m-d', $day));
        // loop over them looking for the ones in $origin_agenda
        foreach ($events as $key => $event) {
            if($event->agenda_id == $origin_agenda){
            // if($event->user_email == 'jjtriff@gmail.com'){
                    // when found update them to $destination_agenda;
                    $result = \CGHAB\BookititClient\changeEventAgenda($event, $destination_agenda);
                    if($result){
                        error_log('Successfully transfered '
                            .$event->start_date.', '
                            .$event->start_time.', event: '
                            .$event->id." to agenda $destination_agenda");  
                        $count++;
                    }
            }
        }
    } catch(Exception $e){
        error_log($e->getMessage());
    }
    error_log('Day '.date('Y-m-d', $day).': '.$count.' moved events.');
    $day += 24 * 60 * 60; # cantidad de segundos que tiene un dia
}
