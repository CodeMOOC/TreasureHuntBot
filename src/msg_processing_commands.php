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
        $context->reply('Help message.');

        return true;
    }
    else if(starts_with($text, '/reset')) {
        $context->reply('Reset command received. Not implemented.');

        return true;
    }
    else if(starts_with($text, '/start')) {
        $payload = extract_command_payload($text, '/start');
        if($payload === '') {
            $context->reply("Ciao, {$context->get_message()->get_full_sender_name()}! Benvenuto alla caccia al tesoro *Urbino Code Hunting Game*. Per partecipare è necessario registrarsi, secondo le [modalità descritte sul sito](http://codemooc.org/urbino-code-hunting/), inviando il comando /register in questa chat.");
        }
        else if(sizeof($payload) == 16) {
            //Special treasure-hunt code sent
            echo "Treasure hunt code: {$payload}." . PHP_EOL;
        }
        else {
            echo "Unknown payload ({$payload})." . PHP_EOL;
            error_log("Unsupported /start payload ({$payload})");
        }

        return true;
    }

    return false;
}

 ?>
