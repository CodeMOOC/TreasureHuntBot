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
        return $context->get_internal_id();
    }

    return db_scalar_query("SELECT `telegram_id` FROM `identities` WHERE `id` = {$group_id}");
}

/**
 * Gets information about a group in the current game.
 * @return array Row with (ID, group name, team leader name, state, minutes since
 *               last state change, participants, registration timestamp).
 */
function bot_get_group_info($context, $group_id = null) {
    if($group_id == null) {
        $group_id = $context->get_telegram_id();
    }

    return db_row_query(sprintf(
        "SELECT `groups`.`group_id`, `groups`.`name`, `identities`.`full_name`, `groups`.`state`, TIMESTAMPDIFF(MINUTE, `groups`.`last_state_change`, NOW()), `groups`.`participants_count`, `groups`.`registered_on` FROM `groups` LEFT OUTER JOIN `identities` ON `groups`.`group_id` = `identities`.`id` WHERE `groups`.`game_id` = %d AND `groups`.`group_id` = %d",
        $context->game->game_id,
        $group_id
    ));
}

/**
 * Gets information about a riddle:
 * (riddle type, riddle template parameter, image path, solution).
 */
function bot_get_riddle_info($context, $riddle_id) {
    return db_row_query(sprintf(
        "SELECT `riddle_type`, `riddle_param`, `image_path`, `solution` FROM `riddles` WHERE `event_id` = %d AND `riddle_id` = %d",
        $context->game->event_id,
        $riddle_id
    ));
}

/**
 * Get lat, long, description, image path, and internal note of a location, by ID.
 */
function bot_get_location_info($context, $location_id) {
    return db_row_query(sprintf(
        "SELECT `lat`, `lng`, `description`, `image_path`, `internal_note`, `hint` FROM `locations` WHERE `game_id` = %d AND `location_id` = %d",
        $context->game->game_id,
        $location_id
    ));
}

/**
 * Get ID of the first location of the game.
 */
function bot_get_first_location_id($context) {
    return db_scalar_query(sprintf(
        "SELECT `location_id` FROM `locations` WHERE `game_id` = %d AND `is_start` = 1 LIMIT 1",
        $context->game->game_id
    ));
}

/**
 * Get ID of the last location of the game.
 */
function bot_get_last_location_id($context) {
    return db_scalar_query(sprintf(
        "SELECT `location_id` FROM `locations` WHERE `game_id` = %d AND `is_end` = 1 LIMIT 1",
        $context->game->game_id
    ));
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
        $user_id = $context->get_internal_id();
    }

    Logger::debug("Assigning random riddle to group of user #{$user_id}", __FILE__, $context);

    $riddle_id = db_scalar_query(sprintf(
        'SELECT `riddle_id` FROM `riddles` WHERE `event_id` = %3$d AND `riddle_id` NOT IN (SELECT `riddle_id` FROM `assigned_riddles` WHERE `event_id` = %3$d AND `game_id` = %1$d AND `group_id` = %2$d) ORDER BY RAND() LIMIT 1',
        $context->game->game_id,
        $user_id,
        $context->game->event_id
    ));
    if($riddle_id === false) {
        return false;
    }
    else if($riddle_id === null) {
        Logger::error("No unassigned riddle found for user", __FILE__, $context);
        return null;
    }

    // Write new riddle
    if(db_perform_action(sprintf(
        "INSERT INTO `assigned_riddles` (`event_id`, `game_id`, `riddle_id`, `group_id`, `assigned_on`) VALUES(%d, %d, %d, %d, NOW())",
        $context->game->event_id,
        $context->game->game_id,
        $riddle_id,
        $user_id
    )) === false) {
        return false;
    }
    bot_set_group_state($context, STATE_GAME_PUZZLE);

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
        $group_id = $context->get_internal_id();
    }
    $target_locations = $context->game->get_game_num_locations();
    $count_locations = bot_get_count_of_reached_locations($context, $group_id);
    $next_cluster_id = $context->game->get_next_location_cluster_id($count_locations);

    Logger::info("Attempting to progress group #{$group_id} to next location (reached {$count_locations}/{$target_locations} locations, timed out: {$context->game->game_timed_out})", __FILE__, $context);

    if($next_cluster_id == null || $context->game->game_timed_out) {
        // This is the end, my only friend
        Logger::info("Reached end of track", __FILE__, $context);

        bot_set_group_state($context, STATE_GAME_LAST_LOC);

        return array(
            'location_id' => bot_get_last_location_id($context),
            'end_of_track' => true,
            'reached_locations' => $count_locations
        );
    }
    else {
        $next_location_id = db_scalar_query(sprintf(
            'SELECT `location_id` FROM `locations` WHERE `game_id` = %1$d AND `cluster_id` = %2$d AND `is_start` = 0 AND `is_end` = 0 AND `location_id` NOT IN (SELECT `location_id` FROM `assigned_locations` WHERE `game_id` = %1$d AND `group_id` = %3$d) ORDER BY RAND() LIMIT 1',
            $context->game->game_id,
            $next_cluster_id,
            $group_id
        ));

        if(!$next_location_id) {
            Logger::error("Failed to find next location", __FILE__, $context);
            return false;
        }

        if(db_perform_action(sprintf(
            "INSERT INTO `assigned_locations` (`game_id`, `location_id`, `group_id`, `assigned_on`) VALUES(%d, %d, %d, NOW())",
            $context->game->game_id,
            $next_location_id,
            $group_id
        )) === false) {
            return false;
        }

        Logger::info("Assigned location #{$next_location_id} in cluster #{$next_cluster_id}", __FILE__, $context);

        bot_set_group_state($context, STATE_GAME_LOCATION);

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
    $state = $context->game->group_state;

    if($state === STATE_GAME_LOCATION) {
        // General case, directed to a location
        return db_scalar_query(sprintf(
            "SELECT l.`location_id` FROM `assigned_locations` AS ass LEFT JOIN `locations` AS l ON ass.`location_id` = l.`location_id` WHERE ass.`game_id` = %d AND ass.`group_id` = %d AND ass.`reached_on` IS NULL ORDER BY ass.`assigned_on` DESC LIMIT 1",
            $context->game->game_id,
            $context->get_internal_id()
        ));
    }
    else if($state === STATE_REG_READY) {
        // Directed to first location
        return db_scalar_query(sprintf(
            "SELECT `location_id` FROM `locations` WHERE `game_id` = %d AND `is_start` = 1 LIMIT 1",
            $context->game->game_id
        ));
    }
    else if($state === STATE_GAME_LAST_LOC) {
        // Directed to last location
        return db_scalar_query(sprintf(
            "SELECT `location_id` FROM `locations` WHERE `game_id` = %d AND `is_end` = 1 LIMIT 1",
            $context->game->game_id
        ));
    }
    else {
        return null;
    }
}

/**
 * Gets the time elapsed since the last location assignment.
 */
function bot_get_time_since_location_assignment($context) {
    $elapsed_time = db_scalar_query(sprintf(
        "SELECT TIMESTAMPDIFF(SECOND, `assigned_on`, NOW()) AS `elapsed` FROM `assigned_locations` WHERE `game_id` = %d AND `group_id` = %d && `reached_on` IS NULL ORDER BY `assigned_on` DESC LIMIT 1",
        $context->game->game_id,
        $context->get_internal_id()
    ));
    
    if($elapsed_time) {
        return $elapsed_time;
    }
    else {
        return 0;
    }
}

/**
 * Group reaches location through a code.
 */
function bot_reach_location($context, $location_id, $game_id) {
    if(!$context->game || $game_id != $context->game->game_id) {
        Logger::info("Location code does not match currently played game", __FILE__, $context);
        return 'wrong';
    }

    $game_check_result = game_check_can_play($context->game->event_state, $context->game->game_state);
    if($game_check_result !== true) {
        return $game_check_result;
    }

    $expected_id = bot_get_expected_location_id($context);
    Logger::debug("Expecting location ID {$expected_id}", __FILE__, $context);

    if($expected_id === false) {
        return false;
    }
    else if($expected_id === null) {
        return 'unexpected';
    }
    else if($location_id !== $expected_id) {
        return 'wrong';
    }

    $state = $context->game->group_state;
    if($state === STATE_GAME_LOCATION) {
        Logger::info("Group reached its next location", __FILE__, $context);

        $reached_rows = db_perform_action(sprintf(
            "UPDATE `assigned_locations` SET `reached_on` = NOW() WHERE `game_id` = %d AND `group_id` = %d AND `reached_on` IS NULL",
            $context->game->game_id,
            $context->get_internal_id()
        ));
        if($reached_rows !== 1) {
            Logger::error("Marking location as reached updated {$reached_rows} rows", __FILE__, $context);
            return false;
        }

        if(!bot_set_group_state($context, STATE_GAME_SELFIE)) {
            return false;
        }

        return true;
    }
    else if($state === STATE_REG_READY) {
        Logger::info("Group reached first location", __FILE__, $context);

        if(!bot_set_group_state($context, STATE_GAME_SELFIE)) {
            return false;
        }

        return 'first';
    }
    else if($state === STATE_GAME_LAST_LOC) {
        Logger::info("Group reached the final location", __FILE__, $context);

        if(!bot_set_group_state($context, STATE_GAME_LAST_SELF)) {
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
    $riddle_info = bot_get_current_assigned_riddle($context);
    if($riddle_info == null || $riddle_info === false) {
        Logger::error("Unable to load current riddle info: " . var_dump($riddle_info), __FILE__, $context);
    }

    // Timeout
    $second_interval = $riddle_info[0];
    if($second_interval && intval($second_interval) <= 30) {
        return 31 - $second_interval;
    }

    $correct_answer = $riddle_info[1];
    $riddle_id = intval($riddle_info[2]);
    if($correct_answer != $solution) {
        Logger::info("Wrong answer '{$solution}' ('{$correct_answer}' expected)", __FILE__, $context);

        db_perform_action(sprintf(
            "UPDATE `assigned_riddles` SET `last_answer_on` = NOW() WHERE `event_id` = %d AND `game_id` = %d AND `riddle_id` = %d AND `group_id` = %d",
            $context->game->event_id,
            $context->game->game_id,
            $riddle_id,
            $context->get_internal_id()
        ));

        return 'wrong';
    }

    Logger::debug('Correct answer', __FILE__, $context);

    if(db_perform_action(sprintf(
        "UPDATE `assigned_riddles` SET `last_answer_on` = NOW(), `solved_on` = NOW() WHERE `event_id` = %d AND `game_id` = %d AND `riddle_id` = %d AND `group_id` = %d",
        $context->game->event_id,
        $context->game->game_id,
        $riddle_id,
        $context->get_internal_id()
    )) === false) {
        return false;
    }

    return true;
}

/**
 * Gets the current hint for the last solved riddle, if any.
 */
function bot_get_current_hint($context) {
    // Hints are assigned for solved riddles, check count of currently solved riddles
    $solved = db_scalar_query(sprintf(
        'SELECT count(*) FROM `assigned_riddles` WHERE `solved_on` IS NOT NULL AND `event_id` = %d AND `game_id` = %d AND `group_id` = %d',
        $context->game->event_id,
        $context->game->game_id,
        $context->get_internal_id()
    ));
    if($solved === false) {
        return false;
    }

    if($solved === 0) {
        return null;
    }

    return db_scalar_query(sprintf(
        'SELECT `content` FROM `hints` WHERE `event_id` = %d AND `riddles_solved_count` = %d',
        $context->game->event_id,
        $solved
    ));
}

/**
 * Attempts to assign a direct victory to the player.
 * @param $event_id Event ID or null.
 * @param $game_id Game ID or null.
 * @return Variety of strings or false on error,
 *         array (condition, winning group, arrival index) on success.
 */
function bot_direct_win($context, $event_id, $game_id) {
    if($event_id != $context->game->event_id &&
       $game_id  != $context->game->game_id) {
        Logger::warning("Victory code does not match currently played event or game", __FILE__, $context);

        return 'wrong';
    }

    $game_check_result = game_check_can_play($context->game->event_state, $context->game->game_state);
    if($game_check_result !== true) {
        return $game_check_result;
    }

    if($context->game->group_state < STATE_GAME_LAST_LOC) {
        return 'too_soon';
    }

    // Check for previous winners
    $winning_groups = bot_get_winning_groups($context);
    if($winning_groups === false) {
        return false;
    }

    bot_set_group_state($context, STATE_GAME_WON);

    if($winning_groups == null) {
        // Game has no winning group
        Logger::info("Group has reached the prize first", __FILE__, $context);

        return array('first', null, 1);
    }
    else {
        Logger::info("Group has reached the prize (not first)", __FILE__, $context);

        return array('not_first', $winning_groups[0][1], count($winning_groups) + 1);
    }
}

/*** COUNTING AND AUXILIARY METHODS ***/

/**
 * Gets a list of Telegram IDs and names of all registered groups.
 * @param $min_state_level Minimum level the groups must have.
 * @return array List of (Telegram ID, Leader name, Group name).
 */
function bot_get_telegram_ids_of_groups($context, $min_state_level = STATE_NEW, $max_state_level = 255) {
    return db_table_query(sprintf(
        "SELECT i.`telegram_id`, s.`group_id`,  i.`full_name`, s.`name` FROM `groups` AS s LEFT JOIN `identities` AS i ON s.`group_id` = i.`id` WHERE s.`game_id` = %d AND s.`state` >= %d AND s.`state` <= %d",
        $context->game->game_id,
        $min_state_level,
        $max_state_level
    ));
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
 * Get whether the game has been won by a group and returns the winning groups.
 * Returns table of group ID and name, if game is won. Returns null if no group has won yet.
 * Returns false on error.
 */
function bot_get_winning_groups($context) {
    return db_table_query(sprintf(
        "SELECT `group_id`, `name` FROM `groups` WHERE `game_id` = %d AND `state` >= %d ORDER BY `last_state_change` ASC",
        $context->game->game_id,
        STATE_GAME_WON
    ));
}

/**
 * Gets the count of reached locations by a group.
 * @param $group_id ID of the group or null for the current group.
 */
function bot_get_count_of_reached_locations($context, $group_id = null) {
    if($group_id == null) {
        $group_id = $context->get_internal_id();
    }

    return db_scalar_query(sprintf(
        "SELECT count(*) FROM `assigned_locations` WHERE `game_id` = %d AND `group_id` = %d AND `reached_on` IS NOT NULL",
        $context->game->game_id,
        $group_id
    ));
}

/**
 * Gets the last assigned location for a group, if any.
 * @return array Row of (Location Id, Lat, Lng, Internal note)
 */
function bot_get_last_assigned_location($context, $group_id = null) {
    if($group_id == null) {
        $group_id = $context->get_internal_id();
    }

    return db_row_query(sprintf(
        'SELECT al.`location_id`, `lat`, `lng`, `internal_note` FROM locations RIGHT JOIN (SELECT * FROM assigned_locations WHERE game_id = %1$d AND group_id = %2$d ORDER BY assigned_on DESC LIMIT 1) AS al ON al.location_id = locations.location_id  WHERE locations.game_id = %1$d LIMIT 1',
        $context->game->game_id,
        $group_id
    ));
}

/**
 * Gets the last reached location for a group, if any.
 * @return array Row of (Location Id, Lat, Lng, Internal note, minutes since reached)
 */
function bot_get_last_reached_location($context, $group_id) {
    if($group_id == null) {
        $group_id = $context->get_internal_id();
    }

    return db_row_query(sprintf(
        'SELECT `locations`.`location_id`, `locations`.`lat`, `locations`.`lng`, `locations`.`internal_note`, TIMESTAMPDIFF(MINUTE, `assigned_locations`.`reached_on`, NOW()) FROM `assigned_locations` LEFT JOIN `locations` ON `locations`.`location_id` = `assigned_locations`.`location_id` AND `locations`.`game_id` = `assigned_locations`.`game_id` WHERE `assigned_locations`.`game_id` = %1$d AND `assigned_locations`.`group_id` = %2$d AND `assigned_locations`.`reached_on` IS NOT NULL ORDER BY `assigned_locations`.`reached_on` DESC LIMIT 1',
        $context->game->game_id,
        $group_id
    ));
}

/**
 * Gets the currently assigned riddle to the group, if any.
 * @param $group_id int Optional group ID.
 * @return array Seconds since last answer, correct solution, riddle ID.
 */
function bot_get_current_assigned_riddle($context, $group_id = null) {
    if($group_id == null) {
        $group_id = $context->get_internal_id();
    }

    return db_row_query(sprintf(
        "SELECT TIMESTAMPDIFF(SECOND, ass.`last_answer_on`, NOW()), r.`solution`, r.`riddle_id` FROM `assigned_riddles` AS ass LEFT JOIN `riddles` AS r ON ass.`riddle_id` = r.`riddle_id` AND `ass`.`event_id` = r.`event_id` WHERE ass.`event_id` = %d AND ass.`game_id` = %d AND ass.`group_id` = %d AND ass.`solved_on` IS NULL ORDER BY `assigned_on` DESC LIMIT 1",
        $context->game->event_id,
        $context->game->game_id,
        $group_id
    ));
}

/**
 * Get all games associated to the user: either because he or she is an organizer,
 * or because he or she has registered a group as player.
 * @return Array Rows of (ID, name, state, is administrator)
 */
function bot_get_associated_games($context) {
    return db_table_query(sprintf(
        'SELECT `games`.`game_id`, `games`.`name`, `games`.`state`, 1 FROM `games` WHERE `games`.`state` < %2$d AND `games`.`organizer_id` = %1$d ' .
        'UNION ' .
        'SELECT `games`.`game_id`, `games`.`name`, `games`.`state`, 0 FROM `games` LEFT OUTER JOIN `groups` ON `games`.`game_id` = `groups`.`game_id` WHERE `games`.`state` < %2$d AND `groups`.`group_id` = %1$d',
        $context->get_internal_id(),
        GAME_STATE_DEAD
    ));
}

// TODO: to be removed/refactored someday

function bot_get_group_status($context, $group_id) {
    return db_scalar_query("SELECT `state` FROM `groups` WHERE `group_id` = {$group_id} AND `game_id` = {$context->game->game_id};");
}

function bot_get_game_absolute_timeout($context){
    return db_scalar_query("SELECT timeout_absolute FROM games WHERE game_id = {$context->game->game_id};");
}
