<?php

namespace Randomize;
/** 
* Devuelve un array que tiene key => valor de los que se seleccionen
* random en el array
*/
function someFromArray($array, $howMany, $eligible = null)
{
    $selected = array();
    if($howMany > count($array))
        throw new \OutOfBoundsException("The amount of requested random elements is greater than the elements in the array");
        
    
    try{
        //tantas veces como howMany
        $i = 0;
        $key = true;
        while ( $i < $howMany && $key) { 
            // busca un key random en el array
            $key = array_rand($array);
            $elected = false;
            //si la funcion eligible existe
            if($eligible && $key){ // si existe la funcion y key no es null
                //usala para determinar si ese element se puede usar
                $elected = $eligible($array[$key]);
                // si se puede usar
                if($elected){
                    //ponlo con los seleccionados
                    $selected[$key] = $array[$key];
                }
            }
            // sino existe eligible
            elseif ($key) {
                // ponlo en el array seleccionado
                $selected[$key] = $array[$key];
            }
            //siempre hay q sacarlo del array original
            unset($array[$key]);

            //si fue seleccionado entonces aumentar el counter
            $i = $elected? $i + 1 : $i;
        }

    } catch (Exception $e){
        error_log($e->getMessage());
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

// $a = [1,2,3,4,5,6];
// $b = someFromArray($a, 3);
// $electionDay = function ($value)
// {
//     return $value <= 3;
// };

// $b = someFromArray($a, 1, $electionDay);

// print_r($b);