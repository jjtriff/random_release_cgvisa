<?php

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
    }

    public function __destruct()
    {
        file_put_contents($this->name, json_encode($this->col, JSON_PRETTY_PRINT));
    }
}


// tests
// $db = new JsonCollection();
// $i = new StdClass();
// $i->fecha = '2017-05-06';
// $i->abiertos = 3333;
// $i->ocupados = 2413;

// $db->col['DS'] = $i;


