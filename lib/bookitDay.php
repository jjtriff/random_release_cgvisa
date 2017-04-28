<?php

include('bookit/CRestClient.php');
include_once('reservationUser.php');
include_once('jsoncollection.php');

//
//

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
    
    function __construct($date)
    {
       $this->date = $date;
    }

    function mapper(StdClass $bookitDay){
      $this->date = $bookitDay->date;
      $this->exec_count = $bookitDay->exec_count;
      // esta sera una lista de los id de los turnos preservados 
      $this->prereservations = $bookitDay->prereservations;
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
    }
}


// tests
// $d  = new BookitDay("2017-10-17");
// $u = new ReservationUser("jj","jj@mail", "78686868", "no tomar en cuenta, solo pruebas de prereservas");

// $d->prereserve("bkt103664", "bkt219175", $u, 1 );

$db = new JsonCollection();
$d = unserialize( $db->col["2017-10-17"]);
// $nd = new BookitDay($d->date);
// $nd->mapper($d);

// $db->col[$nd->date] = serialize($nd);

$d-



print $d->date;


