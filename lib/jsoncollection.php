<?php

include_once('bookitDay.php');

/**
 * class for handling the variables into json, so we can keep a local db
 */
class JsonCollection 
{
    private $name;
    public $col;
    
    function __construct($name = "localdb.json")
    {       $this->name = $name;
            $st = file_get_contents($name);
            if($st != false){
                $this->col = json_decode($st, true);
            }
            if ($this->col === null) {
                $this->col = array();
            }
    }

    public function __destruct()
    {
        file_put_contents($this->name, json_encode($this->col, JSON_PRETTY_PRINT));
    }

    public function addDay(BookitDay $day)
    {
        $this->col[$day->Date()] = serialize($day);
    }

    public function getDay(string $date)
    {   
        $_ret = $this->col[$date];
        if($_ret){
            return unserialize($_ret);
        }

        return $_ret;
    }
}


// tests
// $db = new JsonCollection();
// $i = new StdClass();
// $i->fecha = '2017-05-06';
// $i->abiertos = 3333;
// $i->ocupados = 2413;

// $db->col['DS'] = $i;

// $db = new JsonCollection();
// // $i = new BookitDay('2017-10-15');
// // $db->addDay($i);
// $i = $db->getDay('2017-10-14');
// // $i->fecha = '2017-05-06';
// // $i->abiertos = 3333;
// // $i->ocupados = 2413;

// // $db->col['DS'] = $i;
// print $i;

