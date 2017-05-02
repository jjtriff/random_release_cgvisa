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
      $this->_prereserve($agenda, $service, $client, $retries);
    }

    public function _prereserve($agenda, $service, $client, $retries)
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
      $totalp = count($this->prereservations);
      error_log("Reservados un total de $totalp para el d'ia $this->date, para la agenda $agenda");
    }

    public function releaseEvents($howMany)
    {
      $retries = (int)$GLOBALS['free.ini']['retries'];
      return $this->_releaseEvents($howMany, $retries);
    }

    public function _releaseEvents($howMany, $retries)
    {
      //buscar random en el array tantos como $howMany
      $selected = Randomize\someFromArray($this->prereservations, $howMany);
      //con cada uno de esos mandarlo a eliminar en el sistema
      foreach ($selected as $key => $eventId) {
        $ret = $retries;
          while($ret >= 0){
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
          unset($this->prereservations[$key]);
        }
      }

      return true;
    }

    public function updateDay()
    {
      //pedir en booktit todos los eventos de esta fecha
      $events = CGHAB\BookititClient\getDateEvents($this->date);
      //seleccionar los que tengan el correo y el mail de reservacion
      unset($this->prereservations);
      $this->prereservations = array();
      foreach ($events as $event) {
      //limpiar el array de preservas y llenarlo con las nuevas encontradas
        if($event->user_name == $GLOBALS['free.ini']['reservation_name'])
          $this->prereservations[] = $event->id;
      }
    }

    public function releaseDay(){
      //con cada uno de esos mandarlo a eliminar en el sistema
      foreach ($this->prereservations as $key => $eventId) {
        $ret = $GLOBALS['free.ini']['retries'] ;
          while($ret >= 0){
            try{
                $forDeletion = CGHAB\BookititClient\deleteEvent($eventId);
                error_log("Eliminado evento $eventId del dia $this->date");
                break;
            } catch (Exception $e){
              error_log($e->getMessage());
              $ret--;
            }
          }
        //si se elimina ok eliminarlo del array
        if($forDeletion){
          unset($this->prereservations[$key]);
        }
      }

      return true;
    }
}


// tests
// $d  = new BookitDay("2017-10-17");
// $u = new ReservationUser("jj","jj@mail", "78686868", "no tomar en cuenta, solo pruebas de prereservas");

// $d->prereserve("bkt103664", "bkt219175", $u, 1 );

// $db = new JsonCollection();
// $d = unserialize($db->col["2017-10-17"]);
// $nd = new BookitDay($d->date);
// $nd->mapper($d);

// $db->col[$nd->date] = serialize($nd);

// $d-


// $a = ['a', 'b', 'c', 'd','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
// $b = Randomize\someFromArray($a, 2);
// print_r ($b);

// $ini_array = parse_ini_file('../visado.ini');
// $GLOBALS['free.ini'] = $ini_array;
// $GLOBALS['free.ini']['reservation_name'] = "jj";

// $d->releaseDates(3);


// $db = new JsonCollection();
// $d = ($db->col["2017-10-16"]);
// $bd = new BookitDay("2017-10-16");
// $bd->mapper((object)$d);
// $d = $bd;



// $d->updateDay();

// $db = new JsonCollection();
// foreach ($db->col as $key => $value) {
//   $d = unserialize($value);
//   // $d = new BookitDay($key);
//   // $d->updateDay();
//   // print_r((array)$d);
//   $d->releaseDay();
//   $db->col[$d->date] = serialize($d);
// }



// print $d->date;


