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

    $context->memory['creation_min_locations'] = (int)$event_data[0];
    $context->memory['creation_min_distance'] = ($event_data[1]) ? floatval($event_data[1]) : null;

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
        $context->memory['creation_min_locations']
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

/**
 * Confirms the creation of a new game.
 */
function bot_creation_confirm($context) {
    if(!bot_creation_verify($context)) {
        return false;
    }

    if($context->game->game_state == GAME_STATE_NEW) {
        return bot_creation_update_state($context, GAME_STATE_REG_NAME);
    }

    return true;
}

/**
 * Advances game creation setting a new name.
 */
function bot_creation_set_name($context, $name) {
    if(!bot_creation_verify($context)) {
        return false;
    }

    if(!$name) {
        Logger::debug("No valid name provided", __FILE__, $context);
        return 'not_set';
    }
    else if(strlen($name) <= 4) {
        return 'too_short';
    }

    Logger::debug("Setting game name to '{$name}'", __FILE__, $context);

    $updates = db_perform_action(sprintf(
        'UPDATE `games` SET `name` = \'%s\' WHERE `game_id` = %d',
        db_escape($name),
        $context->game->game_id
    ));
    if($updates !== 1) {
        return false;
    }

    if($context->game->game_state == GAME_STATE_REG_NAME) {
        bot_creation_update_state($context, GAME_STATE_REG_CHANNEL);
    }

    return true;
}

/**
 * Advances game creation setting a public channel.
 */
function bot_creation_set_channel($context, $channel_name) {
    if(!bot_creation_verify($context)) {
        return false;
    }

    if(!$channel_name || mb_substr($channel_name, 0, 1) !== '@') {
        return 'invalid';
    }

    Logger::debug("Attempting to send message to '{$channel_name}'", __FILE__, $context);

    if(!telegram_send_message($channel_name, "Test. Remove this message.")) {
        // TODO: provide better details about error (check for #403 code), etc.
        //       requires better error signaling in telegram_send_message.
        return 'fail_send';
    }

    $updates = db_perform_action(sprintf(
        'UPDATE `games` SET `telegram_channel` = \'%s\' WHERE `game_id` = %d',
        db_escape($channel_name),
        $context->game->game_id
    ));
    if($updates !== 1) {
        return false;
    }

    return true;
}

/**
 * Advances game creation setting censorship options on the channel.
 */
 function bot_creation_set_channel_censorship($context, $censor) {
    if(!bot_creation_verify($context)) {
        return false;
    }

    Logger::debug("Setting censorship status to " . b2s($censor), __FILE__, $context);

    $updates = db_perform_action(sprintf(
        'UPDATE `games` SET `telegram_channel_censor_photo` = %d WHERE `game_id` = %d',
        (int)$censor,
        $context->game->game_id
    ));
    if($updates !== 1) {
        return false;
    }

    if($context->game->game_state == GAME_STATE_REG_CHANNEL) {
        bot_creation_update_state($context, GAME_STATE_REG_EMAIL);
    }

    return true;
}
