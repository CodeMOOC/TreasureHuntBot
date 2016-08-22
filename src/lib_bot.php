<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Bot control logic.
 */

require_once('model/context.php');

/**
 * Picks a random track.
 * @return int | null Random track ID or null on failure.
 */
function bot_pick_random_track_id($context) {
    $track_id = db_scalar_query("SELECT DISTINCT(id) FROM `tracks` WHERE `game_id` = {$context->get_game_id()} ORDER BY RAND() LIMIT 1");
    if($track_id === null) {
        Logger::fatal('Unable to pick random track (no tracks in DB?)', __FILE__, $context);
    }

    //TODO: Safety check on track length
    $track_length = db_scalar_query("SELECT count(*) FROM `tracks` WHERE `game_id` = {$context->get_game_id()} AND `id` = {$track_id}");

    Logger::debug("Picked random track ID {$track_id} of length {$track_length}", __FILE__, $context);

    return $track_id;
}

function bot_register_new_group($context) {
    Logger::debug("Attempting to register new group for user {$context->get_user_id()}", __FILE__, $context);

    $group_id = db_scalar_query("SELECT `id` FROM `identities` WHERE `telegram_id` = {$context->get_user_id()}");
    if($group_id === null) {
        Logger::debug('Registering new identity', __FILE__, $context);

        $group_id = db_perform_action("INSERT INTO `identities` (`id`, `telegram_id`, `full_name`, `last_registration`) VALUES(DEFAULT, {$context->get_user_id()}, '{$context->get_message()->get_full_sender_name()}', NOW())");
    }
    if($group_id === false) {
        Logger::error("Failed to register new group for user {$context->get_user_id()}", __FILE__, $context);
        return false;
    }

    if(db_perform_action("INSERT INTO `status` VALUES({$context->get_game_id()}, {$group_id}, NULL, 0, NULL, " . STATE_NEW . ", NULL, NULL, 0, NOW(), NOW())") === false) {
        Logger::error("Failed to register group status for group {$group_id}", __FILE__, $context);
        return false;
    }

    $context->refresh();

    Logger::info("New group registered for user {$context->get_user_id()}", __FILE__, $context);

    return true;
}

function bot_update_group_name($context, $new_name) {
    $updates = db_perform_action("UPDATE `status` SET `name` = '{$new_name}' WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$context->get_group_id()}");

    if($updates === 1) {
        $context->refresh();
        return true;
    }
    else {
        return false;
    }
}

function bot_update_group_state($context, $new_state, $new_name = null) {
    $updates = db_perform_action("UPDATE `status` SET `state` = {$new_state}, `last_state_change` = NOW() WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$context->get_group_id()}");

    if($updates === 1) {
        $context->refresh();
        return true;
    }
    else {
        return false;
    }
}

/**
 * Gets the count of registered groups (verified and with name).
 */
function bot_get_registered_groups($context) {
    return db_scalar_query("SELECT count(*) FROM `status` WHERE `game_id` = {$context->get_game_id()} AND `state` >= " . STATE_REG_NAME);
}

?>
