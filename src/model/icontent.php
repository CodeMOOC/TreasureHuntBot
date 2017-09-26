<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Class wrapping a text message content from the Telegram API.
 */

interface iContent {

    public function get_sender();

    public function get_communicator($context);

}
