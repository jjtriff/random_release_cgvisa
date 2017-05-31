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

/**
 * Changes the agenda of some event
 *
 * @param stdClass $event the Bookitit event 
 * @param string $newAgenda the bookitit agenda id
 **/
function changeEventAgenda($event, $newAgenda)
{
    $oRestClient = new \CRestClient();
    // $ret = $GLOBALS['free.ini']['retries'];
    // while ($ret >= 0) {
        $result = $oRestClient->updateEvent(
            'json', # $p_sMode, 
            false, # $p_bSecure, 
            $event->id, # $p_sEventID, 
            $newAgenda, # $p_sAgendaID, 
            $event->service_id, # $p_sServiceID, 
            $event->start_date, # $p_dStart_Date, 
            $event->end_date, # $p_dEndDate, 
            $oRestClient->renderMinutes($event->start_time), # $p_iStartTime, 
            $oRestClient->renderMinutes($event->end_time), # $p_iEndTime, 
            "", # $p_sTitle = "", 
            "", # $p_sDescription = "", 
            "" , # $p_sEventSynchroID = "", 
            "", # $p_sAgendaSynchroID = "", 
            "", # $p_sServiceSynchroID = "", 
            false # $p_bSendNotification = false
        );
        
        $oresult = json_decode($result);
        if($oresult->event->status == "true"){
            return true;
        }
        else
        {
            error_log("Error from bookitit: " . $oresult->events->id . " ". $oresult->events->message);
            $ret--;
        }
    // }
    throw new \Exception("Error updating event $event->id for date $event->start_date.");
}

/**
 * updates an event to a different hour set
 *
 * Undocumented function long description
 *
 * @param StdClass $event The bookitit event as returned by getEvents
 * @param String $newStart Time of the day in the form of HH:mm
 * @param String $newEnd Time of the day in the form of HH:mm, if not supplied 1 hour later is calculated
 * @return true if the change was effective
 **/
function changeEventHour($event, $newStart, $newEnd = null)
{
    $newEnd = ($newEnd) ? $newEnd : date('H:m', strtotime($newStart.' +1 hour'));
    $oRestClient = new \CRestClient();
    // $ret = $GLOBALS['free.ini']['retries'];
    // while ($ret >= 0) {
        $result = $oRestClient->updateEvent(
            'json', # $p_sMode, 
            false, # $p_bSecure, 
            $event->id, # $p_sEventID, 
            $event->agenda_id, # $p_sAgendaID, 
            $event->service_id, # $p_sServiceID, 
            $event->start_date, # $p_dStart_Date, 
            $event->end_date, # $p_dEndDate, 
            $oRestClient->renderMinutes($newStart), # $p_iStartTime, 
            $oRestClient->renderMinutes($newEnd), # $p_iEndTime, 
            "", # $p_sTitle = "", 
            "", # $p_sDescription = "", 
            "" , # $p_sEventSynchroID = "", 
            "", # $p_sAgendaSynchroID = "", 
            "", # $p_sServiceSynchroID = "", 
            false # $p_bSendNotification = false
        );
        
        $oresult = json_decode($result);
        if($oresult->event->status == "true"){
            return true;
        }
        else
        {
            error_log("Error from bookitit: " . $oresult->event->id . " ". $oresult->event->message);
            $ret--;
        }
    // }
    throw new \Exception("Error updating event $event->id for date $event->start_date.");
}