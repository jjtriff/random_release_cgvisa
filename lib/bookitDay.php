<?php

include('bookit/CRestClient.php');
include_once('reservationUser.php');
include_once('jsoncollection.php');
include_once('randomization.php');
include_once('bookitClient.php');


/**
 * objeto para representar los d'ias de bookitit y las distintas decisiones sobre el mismo
 * estos son los objetos que estar'an guardados en la db
 */             
class BookitDay extends StdClass
{
    public $date;
    public $exec_count = 0;
    // esta sera una lista de los id de los turnos preservados 
    public $prereservations = array();
    
    public function __construct($date)
    {
       $this->date = $date;
    }

    public function mapper(StdClass $bookitDay){
      $this->date = $bookitDay->date;
      $this->exec_count = $bookitDay->exec_count;
      // esta sera una lista de los id de los turnos preservados 
      $this->prereservations = $bookitDay->prereservations;
    }

    public function prereserve()
    {
      
      $agenda = $GLOBALS['free.ini']['agenda_id'];
      $service = $GLOBALS['free.ini']['service_id'];
      $client =  new ReservationUser(
        $GLOBALS['free.ini']['reservation_name'], 
        $GLOBALS['free.ini']['reservation_mail'],
        $GLOBALS['free.ini']['reservation_phone'], 
        $GLOBALS['free.ini']['reservation_comment']
      );
      $retries = $GLOBALS['free.ini']['retries'];     
      $this->prereserve($agenda, $service, $client, $retries);
    }

    public function prereserve($agenda, $service, $client, $retries)
    {
      $oRestClient = new CRestClient();

      // $date = ($date == null) ? $client->proposed_date : $date ;
      // $time = ($time == null) ? $client->proposed_time : $time ;

      // repetir este ciclo hasta que se cumplan los retries
      while ($retries >= 0) {
        
        try
        {
        //buscar los huecos libres del dia de hoy
          $sReturn = $oRestClient->getFreeSlots($service,
          $agenda, $this->date, "json", false);

          $hours = json_decode($sReturn);
          if($hours->slots->status == "true"){
            $hours = $hours->slots->hours;
          }
          else{
            throw new Exception("An error occurred when communicating with Bookitit");
          }

          if(count($hours) == 0)
            throw new Exception("We ran out of free slots for day $this->date", 1);

          $client->dni = null;
          foreach ($hours as $time) {
              $sReturn = $oRestClient->addEvent( $agenda,
                $service,
                $this->date,
                $this->date,
                $oRestClient->renderMinutes($time),
                $oRestClient->renderMinutes(date('H:i', strtotime("$time +1 hour"))),
                "",
                "",
                $client->comment,
                "",
                "",
                "",
                "",
                $client->phone,
                "",
                $client->name,
                $client->phone,
                $client->mail,
                "json",
                false,
                ["document" => $client->dni]);

              $oObject = json_decode($sReturn);
              if($oObject->event->status == "true"){
                $this->prereservations[] = $oObject->event->id;
              }
              else{
                throw new Exception("An error when trying to prereserve on day $this->date, hour $time");
              }

          }
        } catch (Exception $e){
          $retries--;
        }
      }
      $totalp = count($this->preservations);
      error_log("Reservados un total de $totalp para el d'ia $this->date, para la agenda $agenda");
    }

    public function releaseDates($howMany, $retries)
    {
      //buscar random en el array tantos como $howMany
      $selected = Randomize\someFromArray($this->prereservations, $howMany);
      //con cada uno de esos mandarlo a eliminar en el sistema
      foreach ($selected as $key => $eventId) {
        $ret = $retries;
          while($ret <= 0){
            try{
                $forDeletion = CGHAB\BookititClient\deleteEvent($eventId);
                break;
            } catch (Exception $e){
              error_log($e->getMessage());
              $ret--;
            }
          }
        //si se elimina ok eliminarlo del array
        if($forDeletion){
          unset($this->preservations[$key]);
        }
      }
    }
}


// tests
// $d  = new BookitDay("2017-10-17");
// $u = new ReservationUser("jj","jj@mail", "78686868", "no tomar en cuenta, solo pruebas de prereservas");

// $d->prereserve("bkt103664", "bkt219175", $u, 1 );

$db = new JsonCollection();
$d = unserialize($db->col["2017-10-17"]);
// $nd = new BookitDay($d->date);
// $nd->mapper($d);

// $db->col[$nd->date] = serialize($nd);

// $d-


// $a = ['a', 'b', 'c', 'd','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
// $b = Randomize\someFromArray($a, 2);
// print_r ($b);

$d->releaseDate() $



print $d->date;


