<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Default command message processing.
 */

require_once('lib.php');
require_once('model/context.php');

/*
 * Processes commands in text messages.
 * @param $context Context.
 * @return bool True if processed.
 */
function msg_processing_commands($context) {
    $text = $context->get_message()->text;

    if(starts_with($text, '/help')) {
        telegram_send_message($context->get_chat_id(), 'Help message.');

        return true;
    }
    else if(starts_with($text, '/reset')) {
        telegram_send_message($context->get_chat_id(), 'Reset command received. Not implemented.');

        return true;
    }

    return false;
}

 ?>
