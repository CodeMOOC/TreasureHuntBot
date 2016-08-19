<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Basic message processing functionality,
 * used by both pull and push scripts.
 */

require_once 'model/context.php';
require_once 'msg_processing_commands.php';
//require_once 'bot-commands/msg_in.php';
//require_once 'bot-commands/get_image.php';
//require_once 'vendor/autoload.php';

// Set default timezone for date operations
date_default_timezone_set('UTC');

//Needs some error checking here
$in = new IncomingMessage($message);
$context = new Context($in);

if (isset($in->text)) {
    // Incoming text message
    echo "Text message: '{$in->text}'" . PHP_EOL;

    if(msg_processing_commands($context)) {
        return;
    }
} else if (isset($in->photo)) {
    // Incoming photo
    echo "Photo received" . PHP_EOL;

    //parsePhotoIn($configuration);
} else {
    telegram_send_message($in->chat_id, 'Non ho capito!');
}
