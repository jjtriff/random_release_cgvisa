<?php

/**
 * Reservation user, a container to manage user information
 */
class ReservationUser 
{
    
    function __construct($name, $mail, $phone, $comment)
    {
        $this->name = $name;
        $this->mail = $mail;
        $this->phone = $phone;
        $this->comment = $comment;
    }
}
