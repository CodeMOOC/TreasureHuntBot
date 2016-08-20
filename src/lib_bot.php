<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Bot control logic.
 */

/**
 * Picks a random track.
 * @return int Random track ID.
 */
function bot_pick_random_track_id($context) {
    $track_id = db_scalar_query("SELECT DISTINCT(id) FROM `tracks` WHERE `game_id` = {$context->get_game_id()} ORDER BY RAND() LIMIT 1");

    //TODO: Safety check on track length
    $track_length = db_scalar_query("SELECT count(*) FROM `tracks` WHERE `game_id` = {$context->get_game_id()} AND `id` = {$track_id}");

    echo "Picked random track ID {$track_id} of length {$track_length}." . PHP_EOL;

    return $track_id;
}

function bot_register_new_group($context) {
    $group_id = db_perform_action("INSERT INTO `groups` VALUES(DEFAULT, NULL, {$context->get_user_id()}, '{$context->get_message()->get_full_sender_name()}', NOW(), NULL)");

    if($group_id === false) {
        error_log("Failed to register new group for user {$context->get_user_id()}");
        return false;
    }

    $track_id = bot_pick_random_track_id($context);

    if(db_perform_action("INSERT INTO `status` VALUES({$context->get_game_id()}, {$group_id}, 0, 'new', NULL, {$track_id}, 0)") === false) {
        error_log("Failed to register group status for group {$group_id}");
        return false;
    }

    return true;
}

?>
