<?php

include('bookit/CRestClient.php');

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

    public function prereserve($agenda, $service, $mail, $name)
    {
        
    }
}
