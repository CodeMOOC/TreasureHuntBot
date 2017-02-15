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
 * Get the Telegram ID of a group.
 */
function bot_get_telegram_id($context, $group_id = null) {
    if($group_id === null) {
        $group_id = $context->get_group_id();
    }

    return db_scalar_query("SELECT `telegram_id` FROM `identities` WHERE `id` = {$group_id}");
}

/**
 * Gets image path, text, and hint for a riddle, by ID.
 */
function bot_get_riddle_info($context, $riddle_id) {
    return db_row_query("SELECT `image_path`, `text`, `solution` FROM `riddles` WHERE `game_id` = {$context->get_game_id()} AND `id` = {$riddle_id}");
}

/**
 * Get lat, long, and description of a location, by ID.
 */
function bot_get_location_info($context, $location_id) {
    return db_row_query("SELECT `lat`, `lng`, `description` FROM `locations` WHERE `game_id` = {$context->get_game_id()} AND `id` = {$location_id}");
}

/**
 * Get whether the game has been won by a group.
 * @return The winning game's name or false otherwise.
 */
function bot_get_winning_group($context) {
    $count = db_scalar_query("SELECT `name` FROM `status` WHERE `game_id` = {$context->get_game_id()} AND `state` = " . STATE_GAME_WON);

    if($count === null || $count === false)
        return false;

    return (string)$count;
}

/*** TRACKS, PUZZLES, AND ASSIGNMENTS ***/

/**
 * Assigns a random track to a group.
 * @return The track ID on success, false otherwise.
 */
function bot_assign_random_track_id($context, $group_id = null) {
    if($group_id === null) {
        $group_id = $context->get_group_id();
    }

    Logger::debug("Assigning random track ID to group {$group_id}", __FILE__, $context);

    if(db_perform_action("LOCK TABLES `tracks` READ, `status` WRITE") === false) {
        Logger::error('Unable to lock tables', __FILE__, $context);
        return false;
    }

    $track_id = db_scalar_query("SELECT DISTINCT(id) FROM `tracks` WHERE `game_id` = {$context->get_game_id()} AND `id` NOT IN (SELECT DISTINCT(`track_id`) FROM `status` WHERE `track_id` IS NOT NULL) ORDER BY RAND() LIMIT 1");
    if($track_id === null) {
        db_perform_action("UNLOCK TABLES");

        Logger::error('Unable to pick random track (no more free tracks in DB?)', __FILE__, $context);

        return false;
    }

    if(db_perform_action("UPDATE `status` SET `track_id` = {$track_id} WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$group_id}") !== 1) {
        db_perform_action("UNLOCK TABLES");

        Logger::error("Failure while assigning picked track to group", __FILE__, $context);

        return false;
    }

    if(db_perform_action("UNLOCK TABLES") === false) {
        Logger::fatal('Unable to unlock tables', __FILE__, $context);
        return false;
    }

    //TODO: Safety check on track length
    $track_length = db_scalar_query("SELECT count(*) FROM `tracks` WHERE `game_id` = {$context->get_game_id()} AND `id` = {$track_id}");

    Logger::info("Assigned track ID {$track_id} of length {$track_length} to group {$group_id}", __FILE__, $context);

    return $track_id;
}

/**
 * Assigns a random, not assigned previously, riddle to a group.
 * @return Riddle ID if assigned correctly,
 *         null if no riddle can be assigned,
 *         false otherwise.
 */
function bot_assign_random_riddle($context, $group_id = null) {
    if($group_id === null) {
        $group_id = $context->get_group_id();
    }

    Logger::debug("Assigning random riddle to group {$group_id}", __FILE__, $context);

    $riddle_id = db_scalar_query("SELECT `id` FROM `riddles` WHERE `game_id` = {$context->get_game_id()} AND `id` NOT IN (SELECT `riddle_id` FROM `assigned_riddles` WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$group_id}) ORDER BY RAND() LIMIT 1");
    if($riddle_id === false) {
        return false;
    }
    else if($riddle_id === null) {
        return null;
    }

    // Write new riddle
    if(db_perform_action("INSERT INTO `assigned_riddles` VALUES({$context->get_game_id()}, {$riddle_id}, {$group_id}, NOW(), NULL, NULL)") === false) {
        return false;
    }
    if(db_perform_action("UPDATE `status` SET `state` = " . STATE_GAME_PUZZLE . ", `last_state_change` = NOW() WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$group_id}") === false) {
        return false;
    }

    $context->refresh();

    Logger::info("Riddle {$riddle_id} assigned to group {$group_id}", __FILE__, $context);

    return $riddle_id;
}

/**
 * Assigns the next location inside a track to the group.
 * @return Newly assigned location ID on success,
 *         'eot' if track is completed (no more locations in track),
 *         False on failure.
 */
function bot_advance_track_location($context, $group_id = null, $track_id = null, $track_index = null) {
    if($group_id === null) {
        $group_id = $context->get_group_id();
    }
    if($track_id === null) {
        if($context->get_track_id() === null) {
            return false;
        }
        else {
            $track_id = $context->get_track_id();
        }
    }
    if($track_index === null) {
        if($context->get_track_index() === null) {
            return false;
        }
        else {
            $track_index = $context->get_track_index();
        }
    }

    $next_track_index = intval($track_index) + 1;

    Logger::debug("Progressing group {$group_id} to location at index {$next_track_index} on track", __FILE__, $context);

    //Get new location
    $next_location_id = db_scalar_query("SELECT `location_id` FROM `tracks` WHERE `game_id` = {$context->get_game_id()} AND `id` = {$track_id} AND `order_index` = {$next_track_index}");
    if($next_location_id === false) {
        return false;
    }
    else if($next_location_id === null) {
        Logger::debug("Reached end of track {$track_id}", __FILE__, $context);
        return 'eot';
    }

    //Write new location
    if(db_perform_action("INSERT INTO `assigned_locations` VALUES({$context->get_game_id()}, {$next_location_id}, {$group_id}, {$next_track_index}, NOW(), NULL)") === false) {
        return false;
    }

    if(db_perform_action("UPDATE `status` SET `state` = " . STATE_GAME_LOCATION . ", `track_index` = {$next_track_index}, `last_state_change` = NOW() WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$group_id}") === false) {
        return false;
    }

    Logger::info("Group {$group_id} assigned to location {$next_location_id} (track index {$next_track_index})", __FILE__, $context);

    $context->refresh();

    return $next_location_id;
}

/**
 * Gets the current location code for the current group.
 * @return Location code as string, null if no location assigned,
 *         False on failure.
 */
function bot_get_current_location_code($context) {
    $state = $context->get_group_state();

    if($state === STATE_GAME_LOCATION) {
        return db_scalar_query("SELECT l.`code` FROM `assigned_locations` AS ass LEFT JOIN `locations` AS l ON ass.`location_id` = l.`id` WHERE ass.`game_id` = {$context->get_game_id()} AND ass.`group_id` = {$context->get_group_id()} AND ass.`track_index` = {$context->get_track_index()} AND ass.`reached_on` IS NULL");
    }
    else if($state === STATE_GAME_LAST_LOC) {
        return db_scalar_query("SELECT `code` FROM `locations` WHERE `game_id` = {$context->get_game_id()} AND `id` = " . LAST_LOCATION_ID);
    }
    else {
        return null;
    }
}

/**
 * Group reaches location.
 */
function bot_reach_location($context, $code) {
    $expected_payload = bot_get_current_location_code($context);

    if($expected_payload === false) {
        return false;
    }
    else if($expected_payload === null) {
        return 'unexpected';
    }
    else if($code !== $expected_payload) {
        return 'wrong';
    }

    $state = $context->get_group_state();
    if($state === STATE_GAME_LOCATION) {
        Logger::info("Group {$context->get_group_id()} reached its " . ($context->get_track_index() + 1) . "th assigned location", __FILE__, $context, true);

        $reached_rows = db_perform_action("UPDATE `assigned_locations` SET `reached_on` = NOW() WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$context->get_group_id()} AND `track_index` = {$context->get_track_index()}");
        if($reached_rows !== 1) {
            Logger::error("Marking location as reached updated {$reached_rows} rows", __FILE__, $context);
            return false;
        }

        if(!bot_update_group_state($context, STATE_GAME_SELFIE)) {
            return false;
        }
    }
    else if($state === STATE_GAME_LAST_LOC) {
        Logger::info("Group {$context->get_group_id()} reached the final location", __FILE__, $context, true);

        if(!bot_update_group_state($context, STATE_GAME_LAST_PUZ)) {
            return false;
        }
    }

    return true;
}

/**
 * Attempts to give solution to current riddle.
 * @return True if solution given correctly,
 *         positive int of seconds to wait,
 *         'wrong' if solution is not correct,
 *         false otherwise.
 */
function bot_give_solution($context, $solution) {
    $second_interval = db_scalar_query("SELECT TIMESTAMPDIFF(SECOND, `last_answer_on`, NOW()) FROM `assigned_riddles` WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$context->get_group_id()} ORDER BY `assigned_on` DESC LIMIT 1");
    if($second_interval && intval($second_interval) <= 60) {
        return 61 - intval($second_interval);
    }

    $correct_answer = db_scalar_query("SELECT r.`solution` FROM `assigned_riddles` AS ass LEFT JOIN `riddles` AS r ON ass.`riddle_id` = r.`id` WHERE ass.`game_id` = {$context->get_game_id()} AND ass.`group_id` = {$context->get_group_id()} ORDER BY ass.`assigned_on` DESC LIMIT 1");
    if($correct_answer === null || $correct_answer === false) {
        Logger::error("Unable to load solution for {$context->get_group_id()}", __FILE__, $context);
        return false;
    }

    if($correct_answer !== $solution) {
        db_perform_action("UPDATE `assigned_riddles` SET `last_answer_on` = NOW() WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$context->get_group_id()}");

        return 'wrong';
    }

    if(db_perform_action("UPDATE `assigned_riddles` SET `last_answer_on` = NOW(), `solved_on` = NOW() WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$context->get_group_id()}") === false) {
        return false;
    }

    return true;
}

/*** STATE PROMOTION ***/

/**
 * Promotes reserved groups (verified, with name) to confirmed
 * groups that can complete the registration process.
 */
function bot_promote_reserved_to_confirmed($context) {
    return db_perform_action("UPDATE `status` SET `state` = " . STATE_REG_CONFIRMED . ", `last_state_change` = NOW() WHERE `game_id` = {$context->get_game_id()} AND `state` = " . STATE_REG_NAME);
}

/**
 * Promotes the current group (if confirmed and ready) to active status.
 * @return bool True on success, 'not_found' is group not found,
 *              'invalid_state' if group is not currently confirmed,
 *              'already_active' if group is already active,
 *              False otherwise.
 */
function bot_promote_to_active($context) {
    if($context->get_group_id() === null) {
        return 'not_found';
    }

    $group_id = $context->get_group_id();
    $group_state = $context->get_group_state();

    if($group_state >= STATE_GAME_LOCATION) {
        Logger::debug("Failed to promote group {$group_id} to active (is already in playing state)", __FILE__, $context);
        return 'already_active';
    }
    else if($group_state !== STATE_REG_READY) {
        Logger::debug("Failed to promote group {$group_id} to active (is in state {$group_state})", __FILE__, $context);
        return 'invalid_state';
    }

    Logger::debug("Promoting group {$group_id} to active", __FILE__, $context);

    $track_id = bot_assign_random_track_id($context);
    if($track_id === false) {
        return false;
    }

    $advance_result = bot_advance_track_location($context, $group_id, $track_id, -1);
    switch($advance_result) {
        case 'eot':
            Logger::error("Assigned {$track_id} which doesn't contain a next location", __FILE__, $context);
            return false;

        case false:
            return false;
    }

    Logger::info("Promoted group {$group_id} to active and assigned location {$advance_result} of track {$track_id}", __FILE__, $context, true);

    return true;
}

/*** COUNTING AND AUXILIARY METHODS ***/

/**
 * Gets the count of registered groups (verified and with name).
 */
function bot_get_registered_groups($context) {
    return db_scalar_query("SELECT count(*) FROM `groups` WHERE `game_id` = {$context->get_game_id()} AND `state` >= " . STATE_REG_NAME);
}

/**
 * Gets the count of ready groups (verified, with name, participants, and avatars).
 */
function bot_get_ready_groups($context) {
    return db_scalar_query("SELECT count(*) FROM `groups` WHERE `game_id` = {$context->get_game_id()} AND `state` >= " . STATE_REG_READY);
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
 * Excludes groups by administrators.
 */
function bot_get_group_count_by_state($context) {
    $data = db_table_query("SELECT s.`state`, count(*) FROM `status` AS s LEFT JOIN `identities` AS i ON s.`group_id` = i.`id` WHERE s.`game_id` = {$context->get_game_id()} AND i.`is_admin` = 0 GROUP BY s.`state` ORDER BY s.`state` ASC");

    $map = array();
    foreach(STATE_ALL as $c) {
        $map[$c] = 0;
    }
    foreach($data as $d) {
        $map[$d[0]] = $d[1];
    }

    return $map;
}

/**
 * Gets the total count of participants in groups that are ready.
 * Excludes groups by administrators.
 */
function bot_get_participants_count($context) {
    return db_scalar_query("SELECT sum(`participants_count`) FROM `status` AS s LEFT JOIN `identities` AS i ON s.`group_id` = i.`id` WHERE s.`game_id` = {$context->get_game_id()} AND i.`is_admin` = 0 AND s.`state` >= " . STATE_REG_READY);
}

?>
