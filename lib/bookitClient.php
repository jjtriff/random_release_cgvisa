<?php
namespace CGHAB\BookititClient;

function deleteEvent($eventId){
    $oRestClient = new CRestClient();
    $result = $oRestClient->deleteEvent('json',false,$eventId);

    $oresult = json_decode($result);
    if($oresult->event->status == "true"){
        unset($this->prereservations[$key]);
    }
    else {
        error_log("Error from bookitit: " . $oresult->event->id . " ". $oresult->event->message);
        throw new Exception("Error deleting date $id from day $this->date, we will retry $retries more time(s)");
    }
}