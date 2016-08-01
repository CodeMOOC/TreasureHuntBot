<?php
/**/

class Group
{
    var $user_id;
    var $user_name;
    var $participants_num;
    var $group_name;
    var $group_selfie_path;
    var $registration_time;

    function __construct($message)
    {
        $this->user_id = $message->from['id'];
        $this->user_name = $message->from['first_name'] . " " . $message->from['last_name'];
    }
}