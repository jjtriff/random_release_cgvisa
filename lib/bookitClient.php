<?php
namespace CGHAB\BookititClient;

/**
*   Deletes an event based in its id and returns true o success.
*   Sends an exception if anything goes wrogn.
*/


function deleteEvent($eventId){
    $oRestClient = new CRestClient();
    $result = $oRestClient->deleteEvent('json',false,$eventId);

    $oresult = json_decode($result);
    if($oresult->event->status == "true"){
        return true;
    }
    else {
        error_log("Error from bookitit: " . $oresult->event->id . " ". $oresult->event->message);
        throw new Exception("Error deleting date $id from day $this->date, we will retry $retries more time(s)");
    }

}