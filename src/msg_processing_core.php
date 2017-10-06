<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Basic message processing functionality,
 * used by both pull and push scripts.
 * Expected an update data structure as $update.
 */

require_once(dirname(__FILE__) . '/game.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/model/context.php');

require_once(dirname(__FILE__) . '/msg_helpers.php');
require_once(dirname(__FILE__) . '/msg_processing_admin.php');
require_once(dirname(__FILE__) . '/msg_processing_commands.php');
require_once(dirname(__FILE__) . '/msg_processing_creation.php');
require_once(dirname(__FILE__) . '/msg_processing_state.php');

function process_update($context) {
    if($context->game && $context->game->is_admin) {
        // TODO: admin commands
    }

    if(DEACTIVATED) {
        $context->comm->reply(__('deactivated'));
        return;
    }

    // Base commands (take precedence over anything else)
    if($context->is_message() && msg_processing_commands($context)) {
        return;
    }

    // Game creation process
    if($context->game && $context->game->is_admin && $context->game->game_state <= GAME_STATE_ACTIVE) {
        Logger::debug("Game setup process still running", __FILE__, $context);

        if(msg_processing_handle_game_creation($context)) {
            return;
        }
    }

    // Registration and play process
    if($context->game && $context->game->game_id !== null && !$context->game->is_admin) {
        if(game_check_can_play($context->game->event_state, $context->game->game_state)) {
            if(msg_processing_handle_group_response($context)) {
                return;
            }
        }
        else {
            $context->comm->reply(__('failure_game_dead'));
            return;
        }
    }

    $context->comm->reply(__('fallback_response'));
}

$context = new Context($update);
process_update($context);
$context->close();
