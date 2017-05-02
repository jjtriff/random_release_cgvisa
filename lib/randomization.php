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

function selectMinuteFromNow(int $rangeInHours){
    $now = strtotime("now");
    $secsFromNow = strtotime("$rangeInHours hours");
    $_ret = mt_rand(15, $secsFromNow - $now) % 60;
    // error_log("selected minute: $_ret");
    return $_ret;
}




//tests

// $a = ['a', 'b', 'c', 'd','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
// $b = someFromArray($a, 2);
// print_r ($b);

// print date("H:i")."\n";
// print date("H:i", strtotime(selectMinuteFromNow(1)." minutes"));