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

        $group_id = db_perform_action("INSERT INTO `identities` (`id`, `telegram_id`, `full_name`, `last_registration`) VALUES(DEFAULT, {$context->get_user_id()}, '" . db_escape($context->get_message()->get_full_sender_name()) . "', NOW())");
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
    $updates = db_perform_action("UPDATE `status` SET `name` = '" . db_escape($new_name) . "' WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$context->get_group_id()}");

    if($updates === 1) {
        $context->refresh();
        return true;
    }
    else {
        return false;
    }
}

function bot_update_group_number($context, $new_number) {
    $updates = db_perform_action("UPDATE `status` SET `participants_count` = '" . $new_number . "' WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$context->get_group_id()}");

    if($updates === 1) {
        $context->refresh();
        return true;
    }
    else {
        return false;
    }
}

function bot_update_group_photo($context, $new_photo_path) {
    $updates = db_perform_action("UPDATE `status` SET `photo_path` = '" . $new_photo_path . "' WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$context->get_group_id()}");

    if($updates === 1) {
        $context->refresh();
        return true;
    }
    else {
        return false;
    }
}

function bot_update_group_state($context, $new_state) {
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
 * Promotes reserved groups (verified, with name) to confirmed
 * groups that can complete the registration process.
 */
function bot_promote_reserved_to_confirmed($context) {
    return db_perform_action("UPDATE `status` SET `state` = " . STATE_REG_CONFIRMED . ", `last_state_change` = NOW() WHERE `game_id` = {$context->get_game_id()} AND `state` = " . STATE_REG_NAME);
}

/**
 * Gets the count of registered groups (verified and with name).
 */
function bot_get_registered_groups($context) {
    return db_scalar_query("SELECT count(*) FROM `status` WHERE `game_id` = {$context->get_game_id()} AND `state` >= " . STATE_REG_NAME);
}

/**
 * Gets the count of ready groups (verified, with name, participants, and avatars).
 */
function bot_get_ready_groups($context) {
    return db_scalar_query("SELECT count(*) FROM `status` WHERE `game_id` = {$context->get_game_id()} AND `state` >= " . STATE_REG_READY);
}

/**
 * Gets a list of Telegram IDs and names of all registered groups.
 * @param $min_state_level Minimum level the groups must have.
 * @return array List of (Telegram ID, Leader name, Group name).
 */
function bot_get_telegram_ids_of_groups($context, $min_state_level = STATE_NEW, $max_state_level = STATE_GAME_WON, $is_admin = false) {
    $sql = "SELECT i.`telegram_id`, i.`full_name`, s.`name` FROM `status` AS s LEFT JOIN `identities` AS i ON s.`group_id` = i.`id` WHERE s.`game_id` = {$context->get_game_id()} AND s.`state` >= {$min_state_level} AND s.`state` <= {$max_state_level}";
    if($is_admin) {
        $sql .= " AND i.`is_admin` = 1";
    }

    return db_table_query($sql);
}

/**
 * Gets a map of group counts, grouped by group state.
 */
function bot_get_group_count_by_state($context) {
    $data = db_table_query("SELECT `state`, count(*) FROM `status` WHERE `game_id` = {$context->get_game_id()} GROUP BY `state` ORDER BY `state` ASC");

    $map = array();
    foreach(STATE_ALL as $c) {
        $map[$c] = 0;
    }
    foreach($data as $d) {
        $map[$d[0]] = $d[1];
    }

    return $map;
}

?>
