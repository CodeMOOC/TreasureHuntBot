<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Class wrapping an update's sender.
 */

class Sender {

    function __construct($from_data) {
        $this->id = $from_data['id'];
        if(isset($from_data['first_name'])) {
            $this->first_name = $from_data['first_name'];
        }
        if(isset($from_data['last_name'])) {
            $this->last_name = $from_data['last_name'];
        }
        if(isset($from_data['username'])) {
            $this->username = $from_data['username'];
        }
        if(isset($from_data['language_code'])) {
            $this->language_code = $from_data['language_code'];
        }
    }

    public $id;
    public $first_name = 'Unknown';
    public $last_name = '';
    public $username = '';
    public $language_code = 'en-US';

    /**
     * Gets the user's full name.
     */
    public function get_full_name() {
        $parts = array(
            $this->first_name,
            $this->last_name
        );

        return implode(' ', array_filter($parts));
    }

}
