<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Basic message processing functionality,
 * used by both pull and push scripts.
 */

require_once('text.php');
require_once('log.php');
require_once('model/context.php');
require_once('msg_processing_admin.php');
require_once('msg_processing_commands.php');
require_once('msg_processing_registration.php');
//require_once 'bot-commands/msg_in.php';
//require_once 'bot-commands/get_image.php';
//require_once 'vendor/autoload.php';

// Set default timezone for date operations
date_default_timezone_set('UTC');

//Needs some error checking here
$in = new IncomingMessage($message);
$context = new Context($in);

Logger::debug("Current group state: {$context->get_group_state()}", __FILE__, $context);

if($in->is_group()) {
    // Group (TODO)
}
else if($in->is_private()) {
    if($in->is_text()) {
        Logger::debug("Text: '{$in->text}'", __FILE__, $context);

        if($context->is_admin()) {
            if(msg_processing_admin($context)) {
                return;
            }
        }

        // Base commands
        if(msg_processing_commands($context)) {
            return;
        }

        // Registration responses
        if(msg_processing_handle_group_response($context)) {
            return;
        }

        // ?
        $context->reply(TEXT_FALLBACK_RESPONSE);
    }
    else if($in->is_photo()) {
        $context->reply(TEXT_UNSUPPORTED_PHOTO);
    }
    else {
        $context->reply(TEXT_UNSUPPORTED_OTHER);
    }
}
