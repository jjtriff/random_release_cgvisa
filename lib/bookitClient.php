<?php
namespace CGHAB\BookititClient;

include_once('bookit/CRestClient.php');

/**
*   Deletes an event based in its id and returns true o success.
*   Sends an exception if anything goes wrogn.
*/


function deleteEvent($eventId){
    $oRestClient = new \CRestClient();
    $result = $oRestClient->deleteEvent('json',false,$eventId);

    $oresult = json_decode($result);
    if($oresult->event->status == "true"){
        return true;
    }
    else {
        error_log("Error from bookitit: " . $oresult->event->id . " ". $oresult->event->message);
        throw new \Exception("Error deleting date $eventId");
    }

}

/**
* @param $date in the format "YYYY-MM-DD"
*/

function getDateEvents($date)
{
    $oRestClient = new \CRestClient();
    $ret = $GLOBALS['free.ini']['retries'];
    while ($ret >= 0) {
        $result = $oRestClient->getEvents($date,
        $date,
        'json',
        false);
        
        $oresult = json_decode($result);
        if($oresult->events->status == "true"){
            unset($oresult->events->status);
            return $oresult->events;
        }
        else
        {
            error_log("Error from bookitit: " . $oresult->events->id . " ". $oresult->events->message);
            $ret--;
        }
    }
    throw new \Exception("Error getting events for date $date");
}

/**
 * undocumented function summary
 *
 * Undocumented function long description
 *
 * @param timestamp $date The timestamp of the day you want to know
 * @return bool true if date is a Holiday or Weekend, false otherwise
 **/
function isDateHoliday(int $date)
{
    $knownHD = [
"2017-01-06",
"2017-04-13",
"2017-04-14",
"2017-05-01",
"2017-07-03",
"2017-07-04",
"2017-07-25",
"2017-07-26",
"2017-08-15",
"2017-10-10",
"2017-10-12",
"2017-11-01",
"2017-12-06",
"2017-12-08",
"2017-12-25",
"2017-12-31",
"2018-01-06"
    ];

    $wd = date('w', $date);
    $fulldate = date('Y-m-d', $date);
    if( $wd == 6 || $wd == 0)
        return true;
    return in_array($fulldate, $knownHD);
}