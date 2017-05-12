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
    protected $open = false;
    // esta sera una lista de los id de los turnos preservados 
    public $prereservations = array();

    public function isOpen()
    {
      return $this->open;
    }

    /**
     * Returns if this day still has reserved events
     *
     * @return int The amount of actual prereservations 
     **/
    public function hasReservations()
    {
      return count($this->prereservations);
    }
    
    public function __construct($date)
    {
       $this->Date($date);
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
          $agenda, $this->Date(), "json", false);

          $hours = json_decode($sReturn);
          if($hours->slots->status == "true"){
            $hours = $hours->slots->hours;
          }
          else{
            throw new Exception("An error occurred when communicating with Bookitit");
          }

          if(count($hours) == 0)
            throw new Exception("We ran out of free slots for day {$this->Date()}", 1);

          $client->dni = null;
          foreach ($hours as $time) {
              $sReturn = $oRestClient->addEvent( $agenda,
                $service,
                $this->Date(),
                $this->Date(),
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
                throw new Exception("An error when trying to prereserve on day {$this->Date()}, hour $time");
              }

          }
        } catch (Exception $e){
          $retries--;
        }
      }
      $totalp = count($this->prereservations);
      error_log("Reservados un total de $totalp para el d'ia {$this->Date()}, para la agenda $agenda");
      $this->open = false;
    }

    public function releaseEvents($howMany)
    {
      $retries = (int)$GLOBALS['free.ini']['retries'];
      return $this->_releaseEvents($howMany, $retries);
    }

    public function _releaseEvents($howMany, $retries)
    {
      //buscar random en el array tantos como $howMany
      try{
        $selected = Randomize\someFromArray($this->prereservations, $howMany);
      } catch (OutOfBoundsException $e){
        $_count = count($this->prereservations);
        error_log("The amount of dates/events to release ($howMany) is more than the existing reservations ($_count) for day {$this->Date()}. Every event in the day will be released.");
        $selected = [];
        $this->releaseDay();
      }
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

      $this->open = true;
      error_log("We have release ".count($selected)." dates/events for day ".$this->Date().". This day is now open");
      return true;
    }

    /**
     * Increases the number of Execution counts for this day
     *
     * This is for using when the script uses this day to execute
     *
     * @return
     **/
    public function increaseExec()
    {
      $this->exec_count++;
    }

    /**
     * undocumented function summary
     *
     * Undocumented function long description
     *
     * @param type var Description
     **/

    public function updateDay($retries = null)
    {
      $ret = ($retries == null)?$GLOBALS['free.ini']['retries']:$retries;
      while($ret >= 0){
        try{
          //pedir en booktit todos los eventos de esta fecha
          $events = CGHAB\BookititClient\getDateEvents($this->Date());
          unset($this->prereservations);
          $this->prereservations = array();
          foreach ($events as $event) {
          //limpiar el array de preservas y llenarlo con las nuevas encontradas
          //seleccionar los que tengan el correo y el mail de reservacion
            if($event->user_name == $GLOBALS['free.ini']['reservation_name'])
              $this->prereservations[] = $event->id;
          }
          error_log("Found ".count($this->prereservations)." events for day ".$this->Date());
          break;
        } catch (Exception $e){
          error_log($e->getMessage());
          if($ret == 0)
            error_log("Found ".count($this->prereservations)." events for day ".$this->Date());
          $ret--;
        }
      }
    }

    public function releaseDay(){
      //con cada uno de esos mandarlo a eliminar en el sistema
      foreach ($this->prereservations as $key => $eventId) {
        $ret = $GLOBALS['free.ini']['retries'] ;
          while($ret >= 0){
            try{
                $forDeletion = CGHAB\BookititClient\deleteEvent($eventId);
                error_log("Eliminado evento $eventId del dia {$this->Date()}");
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

    /**
    * Devuelve la fecha en str para usar por bookit, y asigna la fecha al att date q es un timestamp
    * q es usable despues para calcular
    * @return if strDate == null String date for the day in the format YYYY-MM-DD
    * @return true if strDate es una fecha valida, false otherwise
    */
    public function Date($strDate = null)
    {
      if($strDate == null){
        return date("Y-m-d", $this->date);
      }

      $this->date = strtotime($strDate);
      return ($this->date)? true : false;
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

// /**
//  * 
//  */
// class test 
// {
  
//   function __construct()
//   {
//     # code...
//   }
//   function test()
//   {
//     return "2017-05";
//   }

// }


// function aplusb($a, $b)
// {
//   return $a + $b;
// }

// print "esto es {aplusb(3, 4)}";