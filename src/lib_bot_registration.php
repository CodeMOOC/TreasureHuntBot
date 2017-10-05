<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Code Hunting Game registration and group status logic.
 */

/**
 * Gets whether the user is registered for a given game.
 */
function bot_is_registered($context, $game_id) {
    if ($context->game->game_id == $game_id && $context->game->group_name != null) {
        // User is currently playing this exact game
        return true;
    }

    $registration_count = db_scalar_query("SELECT count(*) FROM `groups` WHERE `group_id` = {$context->get_internal_id()} AND `game_id` = {$game_id}");

    return ($registration_count >= 1);
}

/**
 * Registers the user for a game.
 */
function bot_register($context, $game_id) {
    $game_id = intval($game_id);

    if(bot_is_registered($context, $game_id)) {
        Logger::info("User already registered for game #{$game_id}", __FILE__, $context);

        $context->set_active_game($game_id, false);
        $context->reload();

        return 'already_registered';
    }

    if($context->game->game_id != null) {
        Logger::debug("User is already registered for another game (#{$context->game->game_id})", __FILE__, $context);
        // Ignore now
    }

    Logger::debug("Attempting to register new group in game #{$game_id}", __FILE__, $context);

    // Query game information
    $game_info = db_row_query(sprintf(
        'SELECT `games`.`timeout_absolute`, `games`.`timeout_interval`, `games`.`state`, `events`.`state` FROM `games` LEFT OUTER JOIN `events` ON `games`.`event_id` = `events`.`event_id` WHERE `games`.`game_id` = %d',
        $game_id
    ));
    if($game_info === false || $game_info == null) {
        Logger::error("Game #{$game_id} does not exist", __FILE__, $context);
        return false;
    }

    // Check game state
    $game_state = (int)$game_info[2];
    $event_state = (int)$game_info[3];
    if(!game_check_can_register($context, $event_state, $game_state)) {
        Logger::debug("Cannot register game in state " . GAME_STATE_MAP[$game_state] . " for event in state " . EVENT_STATE_MAP[$event_state], __FILE__, $context);
        return 'game_unallowed';
    }

    // Compute group timeout and register
    $game_timeout = 'NULL';
    if($game_info[0]) {
        $game_timeout = "'{$game_info[0]}'";
    }
    else if($game_info[1]) {
        $game_timeout = "DATE_ADD(NOW(), INTERVAL {$game_info[1]} MINUTE)";
    }

    if(db_perform_action(sprintf(
        "INSERT INTO `groups` (`game_id`, `group_id`, `state`, `registered_on`, `last_state_change`, `timeout_absolute`) VALUES(%d, %d, %d, NOW(), NOW(), %s)",
        $game_id,
        $context->get_internal_id(),
        STATE_NEW,
        $game_timeout
    )) === false) {
        Logger::error("Failed to register group status", __FILE__, $context);
        return false;
    }

    Logger::info("New group registered in game #{$game_id}", __FILE__, $context);

    $context->set_active_game($game_id, false);
    $context->reload();

    return true;
}

/**
 * Sets a new group name.
 */
function bot_set_group_name($context, $new_name) {
    if(!$context->game || !$context->game->game_id) {
        return false;
    }

    $updates = db_perform_action(sprintf(
        "UPDATE `groups` SET `name` = '%s' WHERE `game_id` = %d AND `group_id` = %d",
        db_escape($new_name),
        $context->game->game_id,
        $context->get_internal_id()
    ));

    if($updates === 1) {
        $context->game->group_name = $new_name;
        return true;
    }
    else {
        return false;
    }
}

/**
* Updates participants count for current group.
*/
function bot_set_group_participants($context, $new_number) {
    $new_number = intval($new_number);

    $updates = db_perform_action(sprintf(
        "UPDATE `groups` SET `participants_count` = %d WHERE `game_id` = %d AND `group_id` = %d",
        $new_number,
        $context->game->game_id,
        $context->get_internal_id()
    ));

    return ($updates === 1);
}

/**
* Updates photo path for current group.
*/
function bot_set_group_photo($context, $new_photo_path) {
    $updates = db_perform_action(sprintf(
        "UPDATE `groups` SET `photo_path` = '%s' WHERE `game_id` = %d AND `group_id` = %d",
        db_escape($new_photo_path),
        $context->game->game_id,
        $context->get_internal_id()
    ));

    return ($updates === 1);
}

/**
* Updates state for current group and refreshes context.
*/
function bot_set_group_state($context, $new_state) {
    $prev_state = $context->game->group_state;

    $updates = db_perform_action(sprintf(
        "UPDATE `groups` SET `state` = %d, `last_state_change` = NOW() WHERE `game_id` = %d AND `group_id` = %d",
        $new_state,
        $context->game->game_id,
        $context->get_internal_id()
    ));

    Logger::debug(sprintf(
        "User status: %s => %s (rows: %d)",
        STATE_MAP[$prev_state],
        STATE_MAP[$new_state],
        $updates
    ), __FILE__, $context);

    if($updates === 1) {
        $context->game->group_state = $new_state;
        return true;
    }
    else {
        return false;
    }
}
