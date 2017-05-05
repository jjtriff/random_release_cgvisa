<?php

namespace Randomize;
/** 
* Devuelve un array que tiene key => valor de los que se seleccionen
* random en el array
*/
function someFromArray($array, $howMany)
{
    $selected = array();
    $b = array_rand($array, $howMany);
    foreach ($b as $key) {
        $selected[$key] = $array[$key];
    }
    return $selected;
}


/**
* returns a number which is the selected minute from now within the Range param
*/
function selectMinuteFromNow(int $rangeInMinutes){
    $now = strtotime("now");
    $secsFromNow = strtotime("$rangeInMinutes minutes");
    $_ret = intdiv(mt_rand(15, $secsFromNow - $now), 60);
    // error_log("selected minute: $_ret");
    return $_ret;
}




//tests

// $a = ['a', 'b', 'c', 'd','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
// $b = someFromArray($a, 2);
// print_r ($b);

// print date("H:i")."\n";
// for ($i=0; $i < 15; $i++) { 
//     print date("H:i", strtotime(selectMinuteFromNow(10)." minutes"))."\n";
//     # code...
// }