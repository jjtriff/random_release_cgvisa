<?php

require_once 'bookitDay.php';

/**
 * To manage OutOfBounds time
 */
class ETimeOutOfBounds extends Exception
{
    function __construct($message)
    {
        parent::__construct($message, 1);
    }
}


/**
 * Class to represent days with some capacities. Mainly to use with 
 * rebalanceDays
 */
class ReservedBookitDay extends BookitDay
{
    /**
     * contains how many events can be placed within a range of time
     *
     * @var capacities
     **/
    var $capacities = null;

    function __construct($date, $capacities)
    {
        parent::__construct($date);
        $this->capacities = $capacities;
    }

    /**
     * Place events inside this day. Tries first inside the original
     * time slot, if not it goes in the upper one.
     *
     * @param StdClass event The event as outputted by getEvents
     * @param String newTime in the form of "HH:mm", if null, the same time
     * of the event is used
     * @return the final start_time that was asigned
     **/
    public function placeEvent($event, $newTime = null)
    {
        // check si este evento es de este dia
        if($event->start_date != $this->Date())
            throw new Exception("This event $event->id, is from a different day ($event->start_date) than this {$this->Date()}, we won't touch it.", 1);
            
        // check la hora del evento
        $time = ($newTime) ? $newTime : $event->start_time;

        // check si esta hora existe en nuestro dia
        if(!array_key_exists($time, $capacities)){       
            throw new ETimeOutOfBounds("The start_time requested for this event $event->start_time, is not within our timetable.");
        }            

        $_ret = '';
        // si hay disponibilidad en su turno
        if($capacities[$time] > 0){
            // si el start_time que se solicita es igual al del evento
            if($time == $event->start_time){
                // dejarlo como esta y mandar a disminuir la capacidad en ese turno
                $_ret = $time;
            }
            // sino
            else{
                // ponerlo en su turno
                try{
                    $_ret = true; # for testing
                    // $_ret = \CGHAB\BookititClient\changeEventHour($event, $time);
                    // si todo fue bien
                    if($_ret === true){
                        // mandar a disminuir la capacidad en ese turno
                        $_ret = $time;
                    }
                }
                catch (Exception $e){
                    error_log("Error from Bookitit: {$e->getMessage()}");
                }
            }
        }
        // sino
        else{
            // llamar a esta funcion de nuevo con un nuevo start_time
            try{
                $_ret = $this->placeEvent($event, date('H:m', strtotime($time.' +1 hour')));
            } catch (ETimeOutOfBounds $e){
                $availTime = $this->getLastHourAvailable();
                if($availTime){
                    $_ret = $this->placeEvent($event, $availTime);
                }
                else{
                    throw new Exception("There is no place left in this day {$this->Date()} for event $event->id", 1);
                }
            }
        }

        // disminuir la capacidad del turno donde se haya puesto
        $capacities[$_ret] = $capacities[$_ret] - 1;

        // informar donde cayo finalmente
        return $_ret;
    }

    /**
     * returns the last hour with capacities for this day
     *
     * @return string with the Last available hour in the day in the form HH:mm
     * if there are no availability returns null
     **/
    public function getLastHourAvailable()
    {   
        $rev = array_reverse($this->capacities);
        foreach ($rev as $hour => $capacity) {
            if($capacity > 0)
                return $hour;
        }
        return null;
    }
}


// tests

// $rbd = new ReservedBookitDay(1498017600, []);
// print_r ($rbd->Date());
