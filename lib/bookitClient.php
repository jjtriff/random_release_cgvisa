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