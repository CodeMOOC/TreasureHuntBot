<?php
/*
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Basic message processing functionality,
 * used by both pull and push scripts.
 *
 * Put your custom bot intelligence here!
 */

// This file assumes to be included by pull.php or
// hook.php right after receiving a new message.
// It also assumes that the message data is stored
// inside a $message variable.

// Message object structure: {
//     "message_id": 123,
//     "from": {
//       "id": 123456789,
//       "first_name": "First",
//       "last_name": "Last",
//       "username": "FirstLast"
//     },
//     "chat": {
//       "id": 123456789,
//       "first_name": "First",
//       "last_name": "Last",
//       "username": "FirstLast",
//       "type": "private"
//     },
//     "date": 1460036220,
//     "text": "Text"
//   }

require_once 'bot-commands/msg_in.php';
require_once 'bot-commands/configuration.php';

$configuration = new Configuration($message);

if (isset($configuration->text)) {
    // We got an incoming text message
    // Parse message and return correct response
    parseMsgIn($configuration);
} else if (isset($configuration->photo)) {
    $photo = $configuration->photo;
    $caption = $configuration->caption;

} else {
    telegram_send_message($configuration->chat_id, 'Non ho capito!');
}
?>
