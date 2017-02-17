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
require_once('game.php');
require_once('lib.php');

require_once('msg_processing_admin.php');
require_once('msg_processing_commands.php');
require_once('msg_processing_state.php');

//Needs some error checking here
$in = new IncomingMessage($message);
$context = new Context($in);

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

        if(DEACTIVATED) {
            $context->reply(TEXT_DEACTIVATED);
            return;
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
        // Registration responses
        if(msg_processing_handle_group_response($context)) {
            return;
        }

        $context->reply(TEXT_UNREQUESTED_PHOTO);
    }
    else {
        $context->reply(TEXT_UNSUPPORTED_OTHER);
    }
}
