<?php

/**
 * Logger para el simple json
 */
class JsonLogger 
{
    private $file;
    
    function __construct($name = 'RedButton')
    {
        //create file redButton.time.log
        $filename = $name.date('Ymd-Hi').".log";
        $this->file = fopen($filename,'w');
    }

    function logJson($string)
    {
        print $string.",\n";
        $res = fwrite($this->file, $string.",\n");
        return $res;
    }

    function __destruct()
    {
        $res = fclose($this->file);
        if(!$res)
            throw new Exception("Log file not closed", 1);
    }
}





