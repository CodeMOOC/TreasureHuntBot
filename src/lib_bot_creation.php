<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Code Hunting Game creation logic.
 */

/**
 * Verifies that the user is currently creating a new game.
 */
function bot_creation_verify($context) {
    if($context->game && $context->game->is_admin && $context->game->game_state < GAME_STATE_ACTIVE) {
        return true;
    }
    else {
        Logger::error("Invalid access, user is not creating a new game", __FILE__, $context);
        return false;
    }
}

function bot_creation_update_state($context, $new_state) {
    $prev_state = $context->game->game_state;

    $updates = db_perform_action(sprintf(
        'UPDATE `games` SET `state` = %d WHERE `game_id` = %d',
        $new_state,
        $context->game->game_id
    ));

    Logger::debug(sprintf(
        "Game status: %s => %s (rows: %d)",
        GAME_STATE_MAP[$prev_state],
        GAME_STATE_MAP[$new_state],
        $updates
    ), __FILE__, $context);

    if($updates === 1) {
        $context->game->game_state = $new_state;
        return true;
    }
    else {
        return false;
    }
}

/**
 * Initializes a new game for a given event.
 */
function bot_creation_init($context, $event_id) {
    // Load event data
    $event_data = db_row_query(sprintf(
        'SELECT `min_num_locations`, `min_avg_distance` FROM `events` WHERE `event_id` = %d',
        $event_id
    ));
    if(!$event_data) {
        Logger::error("Unable to load event #{$event_id}", __FILE__, $context);
        return false;
    }

    $context->memory->gameCreationMinLocations = (int)$event_data[0];
    $context->memory->gameCreationMinDistance = ($event_data[1]) ? floatval($event_data[1]) : null;

    // Create new entries
    $game_id = db_perform_action(sprintf(
        'INSERT INTO `games` (`game_id`, `event_id`, `state`, `organizer_id`, `registered_on`) VALUES(DEFAULT, %d, %d, %d, NOW())',
        $event_id,
        GAME_STATE_NEW,
        $context->get_internal_id()
    ));
    if($game_id === false) {
        Logger::error("Failed inserting new game", __FILE__, $context);
        return false;
    }

    Logger::debug("Game #{$game_id} created", __FILE__, $context);

    if(db_perform_action(sprintf(
        'INSERT INTO `game_location_clusters` (`game_id`, `cluster_id`, `num_locations`) VALUES(%d, %d, %d)',
        $game_id,
        1,
        $context->memory->gameCreationMinLocations
    )) === false) {
        Logger::error("Failed inserting new game cluster", __FILE__, $context);
        return false;
    }

    $context->set_active_game($game_id, true);
    $context->reload();

    return true;
}

/**
 * Aborts the creation of a new game.
 */
function bot_creation_abort($context) {
    if(!bot_creation_verify($context)) {
        return false;
    }

    // TODO

    $context->set_active_game(null, false);

    return true;
}

function bot_creation_confirm($context) {
    if(!bot_creation_verify($context)) {
        return false;
    }

    return bot_creation_update_state($context, GAME_STATE_REG_NAME);
}
