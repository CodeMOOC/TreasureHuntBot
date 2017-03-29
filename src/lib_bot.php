<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Bot control logic.
 */

require_once(dirname(__FILE__) . '/model/context.php');

/**
 * Get the Telegram ID of a group.
 */
function bot_get_telegram_id($context, $group_id = null) {
    if($group_id === null) {
        return $context->get_telegram_user_id();
    }

    return db_scalar_query("SELECT `telegram_id` FROM `identities` WHERE `id` = {$group_id}");
}

/**
 * Gets image path and text for a riddle, by ID.
 */
function bot_get_riddle_info($context, $riddle_id) {
    return db_row_query("SELECT `image_path`, `text` FROM `riddles` WHERE `event_id` = {$context->get_event_id()} AND `riddle_id` = {$riddle_id}");
}

/**
 * Get lat, long, description, image path, and internal note of a location, by ID.
 */
function bot_get_location_info($context, $location_id) {
    return db_row_query("SELECT `lat`, `lng`, `description`, `image_path`, `internal_note` FROM `locations` WHERE `game_id` = {$context->get_game_id()} AND `location_id` = {$location_id}");
}

/**
 * Get ID of the first location of the game.
 */
function bot_get_first_location_id($context) {
    return db_scalar_query("SELECT `location_id` FROM `locations` WHERE `game_id` = {$context->get_game_id()} AND `is_start` = 1 LIMIT 1");
}

/**
 * Get ID of the last location of the game.
 */
function bot_get_last_location_id($context) {
    return db_scalar_query("SELECT `location_id` FROM `locations` WHERE `game_id` = {$context->get_game_id()} AND `is_end` = 1 LIMIT 1");
}

/*** TRACKS, PUZZLES, AND ASSIGNMENTS ***/

/**
 * Assigns a random, not assigned previously, riddle to a group.
 * @return Riddle ID if assigned correctly,
 *         null if no riddle can be assigned,
 *         false otherwise.
 */
function bot_assign_random_riddle($context, $user_id = null) {
    if($user_id === null) {
        $user_id = $context->get_user_id();
    }

    Logger::debug("Assigning random riddle to group #{$user_id}", __FILE__, $context);

    $riddle_id = db_scalar_query("SELECT `riddle_id` FROM `riddles` WHERE `event_id` = {$context->get_event_id()} AND `riddle_id` NOT IN (SELECT `riddle_id` FROM `assigned_riddles` WHERE `event_id` = {$context->get_event_id()} AND `group_id` = {$user_id}) ORDER BY RAND() LIMIT 1");
    if($riddle_id === false) {
        return false;
    }
    else if($riddle_id === null) {
        Logger::error("No unassigned riddle found for user", __FILE__, $context);
        return null;
    }

    // Write new riddle
    if(db_perform_action("INSERT INTO `assigned_riddles` (`event_id`, `riddle_id`, `group_id`, `assigned_on`) VALUES({$context->get_event_id()}, {$riddle_id}, {$user_id}, NOW())") === false) {
        return false;
    }
    $context->set_state(STATE_GAME_PUZZLE);

    Logger::info("Riddle #{$riddle_id} assigned to group #{$user_id}", __FILE__, $context);

    return $riddle_id;
}

/**
 * Assigns the next location to a group and updates group state.
 * @return Associative array ('location_id', 'cluster_id', 'reached_locations', 'end_of_track')
 *         or false on failure.
 */
function bot_advance_track_location($context, $group_id = null) {
    if($group_id == null) {
        $group_id = $context->get_user_id();
    }
    $target_locations = $context->get_game_num_locations();
    $count_locations = bot_get_count_of_reached_locations($context);
    $next_cluster_id = $context->get_next_location_cluster_id($count_locations);

    Logger::info("Attempting to progress group to next location (reached {$count_locations}/{$target_locations} locations, timed out: {$context->is_timed_out()})", __FILE__, $context);

    if($next_cluster_id == null || $context->is_timed_out()) {
        // This is the end, my only friend
        Logger::info("Reached end of track", __FILE__, $context);

        $context->set_state(STATE_GAME_LAST_LOC);

        return array(
            'location_id' => bot_get_last_location_id($context),
            'end_of_track' => true,
            'reached_locations' => $count_locations
        );
    }
    else {
        $next_location_id = db_scalar_query("SELECT `location_id` FROM `locations` WHERE `game_id` = {$context->get_game_id()} AND `cluster_id` = {$next_cluster_id} AND `is_start` = 0 AND `is_end` = 0 AND `location_id` NOT IN (SELECT `location_id` FROM `assigned_locations` WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$group_id}) ORDER BY RAND() LIMIT 1");

        if(!$next_location_id) {
            Logger::error("Failed to find next location", __FILE__, $context);
            return false;
        }

        if(db_perform_action("INSERT INTO `assigned_locations` (`game_id`, `location_id`, `group_id`, `assigned_on`) VALUES({$context->get_game_id()}, {$next_location_id}, {$group_id}, NOW())") === false) {
            return false;
        }

        Logger::info("Assigned location #{$next_location_id} in cluster #{$next_cluster_id}", __FILE__, $context);

        $context->set_state(STATE_GAME_LOCATION);

        return array(
            'location_id' => $next_location_id,
            'cluster_id' => $next_cluster_id,
            'end_of_track' => false,
            'reached_locations' => $count_locations
        );
    }
}

/**
 * Gets the expected location ID for the current group.
 * @return Location ID as an integer, null if no location assigned,
 *         false on failure.
 */
function bot_get_expected_location_id($context) {
    $state = $context->get_group_state();

    if($state === STATE_GAME_LOCATION) {
        // General case, directed to a location
        return db_scalar_query("SELECT l.`location_id` FROM `assigned_locations` AS ass LEFT JOIN `locations` AS l ON ass.`location_id` = l.`location_id` WHERE ass.`game_id` = {$context->get_game_id()} AND ass.`group_id` = {$context->get_user_id()} AND ass.`reached_on` IS NULL ORDER BY ass.`assigned_on` DESC LIMIT 1");
    }
    else if($state === STATE_REG_READY) {
        // Directed to first location
        return db_scalar_query("SELECT `location_id` FROM `locations` WHERE `game_id` = {$context->get_game_id()} AND `is_start` = 1 LIMIT 1");
    }
    else if($state === STATE_GAME_LAST_LOC) {
        // Directed to last location
        return db_scalar_query("SELECT `location_id` FROM `locations` WHERE `game_id` = {$context->get_game_id()} AND `is_end` = 1 LIMIT 1");
    }
    else {
        return null;
    }
}

/**
 * Group reaches location through a code.
 */
function bot_reach_location($context, $location_id, $game_id) {
    if($game_id != $context->get_game_id()) {
        Logger::warning("Location code does not match currently played game", __FILE__, $context);
        return false;
    }

    $expected_id = bot_get_expected_location_id($context);
    Logger::debug("Expecting location ID {$expected_id}", __FILE__, $context);

    if($expected_id === false) {
        return false;
    }
    else if($expected_id == null) {
        return 'unexpected';
    }
    else if($location_id !== $expected_id) {
        return 'wrong';
    }

    $state = $context->get_group_state();
    if($state === STATE_GAME_LOCATION) {
        Logger::info("Group reached its next location", __FILE__, $context);

        $reached_rows = db_perform_action("UPDATE `assigned_locations` SET `reached_on` = NOW() WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$context->get_user_id()} AND `reached_on` IS NULL");
        if($reached_rows !== 1) {
            Logger::error("Marking location as reached updated {$reached_rows} rows", __FILE__, $context);
            return false;
        }

        if(!$context->set_state(STATE_GAME_SELFIE)) {
            return false;
        }

        return true;
    }
    else if($state === STATE_REG_READY) {
        Logger::info("Group reached first location", __FILE__, $context);

        if(!$context->set_state(STATE_GAME_SELFIE)) {
            return false;
        }

        return 'first';
    }
    else if($state === STATE_GAME_LAST_LOC) {
        Logger::info("Group reached the final location", __FILE__, $context);

        if(!$context->set_state(STATE_GAME_LAST_PUZ)) {
            return false;
        }

        return 'last';
    }
}

/**
 * Attempts to give solution to current riddle.
 * @return True if solution given correctly,
 *         positive int of seconds to wait,
 *         'wrong' if solution is not correct,
 *         false otherwise.
 */
function bot_give_solution($context, $solution) {
    $riddle_info = db_row_query("SELECT TIMESTAMPDIFF(SECOND, ass.`last_answer_on`, NOW()), r.`solution`, r.`riddle_id` FROM `assigned_riddles` AS ass LEFT JOIN `riddles` AS r ON ass.`riddle_id` = r.`riddle_id` WHERE ass.`event_id` = {$context->get_event_id()} AND ass.`group_id` = {$context->get_user_id()} AND ass.`solved_on` IS NULL ORDER BY `assigned_on` DESC LIMIT 1");
    if($riddle_info === null || $riddle_info === false) {
        Logger::error("Unable to load current riddle info", __FILE__, $context);
        return false;
    }

    // Timeout
    $second_interval = $riddle_info[0];
    if($second_interval && intval($second_interval) <= 60) {
        return 61 - $second_interval;
    }

    $correct_answer = $riddle_info[1];
    $riddle_id = intval($riddle_info[2]);
    if($correct_answer != $solution) {
        Logger::debug("Wrong answer ('{$correct_answer}' expected)", __FILE__, $context);

        db_perform_action("UPDATE `assigned_riddles` SET `last_answer_on` = NOW() WHERE `event_id` = {$context->get_event_id()} AND `riddle_id` = {$riddle_id} AND `group_id` = {$context->get_user_id()}");

        return 'wrong';
    }

    Logger::debug('Correct answer', __FILE__, $context);

    if(db_perform_action("UPDATE `assigned_riddles` SET `last_answer_on` = NOW(), `solved_on` = NOW() WHERE `event_id` = {$context->get_event_id()} AND `riddle_id` = {$riddle_id} AND `group_id` = {$context->get_user_id()}") === false) {
        return false;
    }

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
 * Gets the total count of participants in groups that are ready.
 * Excludes groups by administrators.
 */
function bot_get_ready_participants_count($context) {
    return db_scalar_query("SELECT sum(g.`participants_count`) FROM `groups` AS g WHERE g.`game_id` = {$context->get_game_id()} AND g.`state` >= " . STATE_REG_READY);
}

/**
 * Gets a list of Telegram IDs and names of all registered groups.
 * @param $min_state_level Minimum level the groups must have.
 * @return array List of (Telegram ID, Leader name, Group name).
 */
function bot_get_telegram_ids_of_groups($context, $min_state_level = STATE_NEW, $max_state_level = STATE_GAME_WON) {
    $sql = "SELECT i.`telegram_id`, s.`group_id`,  i.`full_name`, s.`name` FROM `groups` AS s LEFT JOIN `identities` AS i ON s.`group_id` = i.`id` WHERE s.`game_id` = {$context->get_game_id()} AND s.`state` >= {$min_state_level} AND s.`state` <= {$max_state_level};";

    return db_table_query($sql);
}

/**
 * Gets a list of Telegram IDs and names of all playing groups.
 *
 * Please note that as "playing" group is intended a group which,
 * at least, already had an assigned location to reach.
 *
 * @return array List of (Telegram ID, Leader name, Group name).
 */
function bot_get_telegram_ids_of_playing_groups($context) {
    return bot_get_telegram_ids_of_groups($context, STATE_GAME_LOCATION);
}

/**
 * Get whether the game has been won by a group.
 * Returns group ID and name, if game is won. Returns null if no group has won yet.
 * Returns false on error.
 */
function bot_get_winning_group($context) {
    return db_row_query("SELECT `group_id`, `name` FROM `groups` WHERE `game_id` = {$context->get_game_id()} AND `state` = " . STATE_GAME_WON);
}

/**
 * Gets the count of reached locations by a group.
 * @param $group_id ID of the group or null for the current group.
 */
function bot_get_count_of_reached_locations($context, $group_id = null) {
    if($group_id == null) {
        $group_id = $context->get_user_id();
    }

    return db_scalar_query("SELECT count(*) FROM `assigned_locations` WHERE `game_id` = {$context->get_game_id()} AND `group_id` = {$group_id} AND `reached_on` IS NOT NULL");
}


/**
 * Gets the last assigned location.
 *
 * @param $context
 * @param $group_id
 * @return array List of (Location Id, Lat, Lng, Internal note)
 */
function bot_get_group_last_assigned_location($context, $group_id) {
    return db_table_query("SELECT al.`location_id`, `lat`, `lng`, `internal_note` FROM locations RIGHT JOIN (SELECT * FROM assigned_locations WHERE game_id = {$context->get_game_id()}  AND group_id = {$group_id} AND assigned_on IS NOT NULL ORDER BY assigned_on DESC LIMIT 1) AS al ON al.location_id = locations.location_id LIMIT 1;");
}

/**
 * Gets the last reached location.
 *
 * @param $context
 * @param $group_id
 * @return array List of (Location Id, Lat, Lng, Internal note)
 */
function bot_get_group_last_reached_location($context, $group_id) {
    return db_table_query("SELECT al.`location_id`, `lat`, `lng`, `internal_note` FROM locations RIGHT JOIN (SELECT * FROM assigned_locations WHERE game_id = {$context->get_game_id()}  AND group_id = {$group_id} AND reached_on IS NOT NULL ORDER BY reached_on DESC LIMIT 1) AS al ON al.location_id = locations.location_id LIMIT 1;");
}

/**
 * Gets the amount of reached locations for all playing group.
 */
function bot_get_count_of_reached_location_for_playing_groups($context) {
    return  db_table_query("SELECT i.`telegram_id`, i.`id`, g.`name`, l.c FROM groups AS g LEFT JOIN `identities` AS i ON g.`group_id` = i.`id`INNER JOIN (SELECT group_id, game_id, COUNT(*) AS c FROM assigned_locations WHERE game_id = {$context->get_game_id()} AND reached_on IS NOT NULL GROUP BY group_id) AS l ON l.group_id = g.group_id WHERE g.name IS NOT NULL ORDER BY l.`c` DESC;");
}

/**
 * Gets the current rank for playing groups.
 *
 * Groups are ordered following these criteria:
 * 1) number of reached location;
 * 2) group status;
 * 3) last_state_change (ASC).
 *
 * @param $context
 * @return array|bool
 */
function bot_get_current_chart_of_playing_groups($context) {
    return db_table_query("SELECT i.`telegram_id`, i.`id`, g.`name`, l.c, g.state, g.last_state_change FROM groups AS g LEFT JOIN `identities` AS i ON g.`group_id` = i.`id`INNER JOIN (SELECT group_id, game_id, COUNT(*) AS c FROM assigned_locations WHERE game_id = {$context->get_game_id()} AND reached_on IS NOT NULL GROUP BY group_id) AS l ON l.group_id = g.group_id WHERE g.name IS NOT NULL ORDER BY l.`c`DESC, g.`state` DESC, g.last_state_change ASC");
}

function bot_get_group_status($context, $group_id) {
    return db_scalar_query("SELECT `state` FROM `groups` WHERE `group_id` = {$group_id};");
}

/**
 * Gets the amount of assigned locations for all playing group.
 */
function bot_get_count_of_assigned_location_for_playing_groups($context) {
    return  db_table_query("SELECT i.`telegram_id`, i.`id`, g.`name`, l.c FROM groups AS g LEFT JOIN `identities` AS i ON g.`group_id` = i.`id`INNER JOIN (SELECT group_id, game_id, COUNT(*) AS c FROM assigned_locations WHERE game_id = {$context->get_game_id()} AND assigned_on IS NOT NULL GROUP BY group_id) AS l ON l.group_id = g.group_id WHERE g.name IS NOT NULL ORDER BY l.`c` DESC;");
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
