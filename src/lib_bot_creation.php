<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Code Hunting Game creation logic.
 */

const DEFAULT_CLUSTER_ID = 1;
const DEFAULT_START_LOCATION_ID = 1;
const DEFAULT_END_LOCATION_ID = 2;
const DEFAULT_LOCATION_ID_OFFSET = 10;

const MEMORY_CREATION_MIN_LOCATIONS = 'creation_min_locations';
const MEMORY_CREATION_MIN_DISTANCE = 'creation_min_distance';
const MEMORY_CREATION_CHANNEL_TESTED = 'creation_channel_tested';
const MEMORY_CREATION_CHANNEL_NAME = 'creation_channel_name';
const MEMORY_CREATION_LOCATION_LAT = 'creation_location_lat';
const MEMORY_CREATION_LOCATION_LNG = 'creation_location_lng';
const MEMORY_CREATION_LOCATION_NAME = 'creation_location_name';
const MEMORY_CREATION_LOCATION_PICTURE = 'creation_location_picture';

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

/**
 * Updates a game's state.
 */
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
        'SELECT `min_num_locations`, `min_avg_distance`, `state` FROM `events` WHERE `event_id` = %d',
        $event_id
    ));
    if(!$event_data) {
        Logger::error("Unable to load event #{$event_id}", __FILE__, $context);
        return false;
    }

    $event_state = (int)$event_data[2];
    $event_check_result = event_check_can_create($event_state);
    if($event_check_result !== true) {
        Logger::debug("Cannot create game for event in state " . EVENT_STATE_MAP[$event_state] . ": {$event_check_result}", __FILE__, $context);

        return $event_check_result;
    }

    $context->memory[MEMORY_CREATION_MIN_LOCATIONS] = intval($event_data[0]);
    $context->memory[MEMORY_CREATION_MIN_DISTANCE] = floatval($event_data[1]);

    // Create new entries
    $game_id = db_perform_action(sprintf(
        'INSERT INTO `games` (`game_id`, `event_id`, `state`, `organizer_id`, `registered_on`, `language`) VALUES(DEFAULT, %d, %d, %d, NOW(), %s)',
        $event_id,
        GAME_STATE_NEW,
        $context->get_internal_id(),
        ($context->language_override) ? ("'" . db_escape($context->language_override) . "'") : 'DEFAULT'
    ));
    if($game_id === false) {
        Logger::error("Failed inserting new game", __FILE__, $context);
        return false;
    }

    if(db_perform_action(sprintf(
        'INSERT INTO `game_location_clusters` (`game_id`, `cluster_id`, `num_locations`) VALUES(%d, %d, %d)',
        $game_id,
        DEFAULT_CLUSTER_ID,
        $context->memory[MEMORY_CREATION_MIN_LOCATIONS]
    )) === false) {
        Logger::error("Failed inserting new game cluster", __FILE__, $context);
        return false;
    }

    code_lookup_generate($context, 'registration', $event_id, $game_id, null);

    Logger::info("Game #{$game_id} creation started", __FILE__, $context);

    $context->set_active_game($game_id, true);

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
    if($updates === false) {
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

    if(!$channel_name || mb_strpos($channel_name, ' ') !== false) {
        return 'invalid';
    }

    if(mb_substr($channel_name, 0, 1) !== '@') {
        Logger::debug("Fixing channel name with missing '@' first character", __FILE__, $context);
        $channel_name = '@' . $channel_name;
    }

    Logger::debug("Attempting to send message to '{$channel_name}'", __FILE__, $context);

    Logger::suspend(true);
    $result = telegram_send_message($channel_name, "Test. Remove this message.");
    Logger::suspend(false);

    if(!$result) {
        // TODO: provide better details about error (check for #403 code), etc.
        //       requires better error signaling in telegram_send_message.
        return 'fail_send';
    }

    $updates = db_perform_action(sprintf(
        'UPDATE `games` SET `telegram_channel` = \'%s\' WHERE `game_id` = %d',
        db_escape($channel_name),
        $context->game->game_id
    ));
    if($updates === false) {
        return false;
    }

    Logger::info("Tested and set channel {$channel_name}", __FILE__, $context);

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
        $censor,
        $context->game->game_id
    ));
    if($updates === false) {
        return false;
    }

    if($context->game->game_state == GAME_STATE_REG_CHANNEL) {
        bot_creation_update_state($context, GAME_STATE_REG_EMAIL);
    }

    return true;
}

function bot_creation_set_email($context, $email) {
    if(!bot_creation_verify($context)) {
        return false;
    }

    if(filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return 'invalid';
    }

    $updates = db_perform_action(sprintf(
        'UPDATE `games` SET `organizer_email` = \'%s\' WHERE `game_id` = %d',
        db_escape($email),
        $context->game->game_id
    ));
    if($updates === false) {
        return false;
    }

    if($context->game->game_state == GAME_STATE_REG_EMAIL) {
        bot_creation_update_state($context, GAME_STATE_LOCATIONS_FIRST);
    }

    return true;
}

function bot_creation_set_start($context, $lat, $lng) {
    if(!bot_creation_verify($context)) {
        return false;
    }

    db_perform_action(sprintf(
        'REPLACE INTO `locations` (`game_id`, `location_id`, `cluster_id`, `internal_note`, `lat`, `lng`, `is_start`) VALUES(%d, %d, %d, \'%s\', %F, %F, %d)',
        $context->game->game_id,
        DEFAULT_START_LOCATION_ID,
        DEFAULT_CLUSTER_ID,
        'Starting location',
        $lat,
        $lng,
        1
    ));

    code_lookup_generate($context, 'location', null, $context->game->game_id, DEFAULT_START_LOCATION_ID);

    if($context->game->game_state == GAME_STATE_LOCATIONS_FIRST) {
        bot_creation_update_state($context, GAME_STATE_LOCATIONS_LAST);
    }

    return true;
}

function bot_creation_set_end($context, $lat, $lng, $image_path = null) {
    if(!bot_creation_verify($context)) {
        return false;
    }

    db_perform_action(sprintf(
        'REPLACE INTO `locations` (`game_id`, `location_id`, `cluster_id`, `internal_note`, `lat`, `lng`, `image_path`, `is_end`) VALUES(%d, %d, %d, \'%s\', %F, %F, %s, %d)',
        $context->game->game_id,
        DEFAULT_END_LOCATION_ID,
        DEFAULT_CLUSTER_ID,
        'Ending location',
        $lat,
        $lng,
        (!$image_path) ? 'NULL' : ("'" . db_escape($image_path) . "'"),
        1
    ));

    code_lookup_generate($context, 'location', null, $context->game->game_id, DEFAULT_END_LOCATION_ID);

    if($context->game->game_state == GAME_STATE_LOCATIONS_LAST) {
        bot_creation_update_state($context, GAME_STATE_LOCATIONS);
    }

    return true;
}

function bot_creation_save_location($context, $lat, $lng, $name, $image_path = null) {
    if(!bot_creation_verify($context)) {
        return false;
    }

    $existing_count = bot_get_normal_location_count($context);
    $location_id = DEFAULT_LOCATION_ID_OFFSET + $existing_count;

    if(db_perform_action(sprintf(
        'INSERT INTO `locations` (`game_id`, `location_id`, `cluster_id`, `internal_note`, `lat`, `lng`, `image_path`) VALUES(%d, %d, %d, \'%s\', %F, %F, %s)',
        $context->game->game_id,
        $location_id,
        DEFAULT_CLUSTER_ID,
        db_escape($name),
        $lat,
        $lng,
        (!$image_path) ? 'NULL' : ("'" . db_escape($image_path) . "'")
    )) === false) {
        return false;
    }

    code_lookup_generate($context, 'location', null, $context->game->game_id, $location_id);

    return true;
}

function bot_creation_stop_location($context) {
    if(!bot_creation_verify($context)) {
        return false;
    }

    list($conditions, $count) = bot_creation_check_location_conditions($context);

    if(!$conditions) {
        return 'conditions_not_met';
    }

    if($context->game->game_state == GAME_STATE_LOCATIONS) {
        bot_creation_update_state($context, GAME_STATE_GENERATION);
    }

    Logger::info("Configured {$count} locations", __FILE__, $context);

    return true;
}

/**
 * Terminates game creation phase and activates the game.
 */
function bot_creation_activate($context) {
    if(!bot_creation_verify($context)) {
        return false;
    }

    bot_creation_update_state($context, GAME_STATE_ACTIVE);

    Logger::info("Activated game", __FILE__, $context);

    return true;
}

// *** AUXILIARY ***

/**
 * Gets the count of normal (non-start and non-end) locations for a game.
 */
function bot_get_normal_location_count($context) {
    return db_scalar_query(sprintf(
        'SELECT count(*) FROM `locations` WHERE `game_id` = %d AND `is_start` = 0 AND `is_end` = 0',
        $context->game->game_id
    ));
}

/**
 * Gets information about the location conditions.
 * @return array (bool conditions met, int count normal locations)
 */
function bot_creation_check_location_conditions($context) {
    $locations_data = db_row_query(sprintf(
        'SELECT sum(IF(`is_start` = 0 AND `is_end` = 0, 1, 0)) AS `normals`, sum(`is_start`), sum(`is_end`) FROM `locations` WHERE `game_id` = %d',
        $context->game->game_id
    ));

    // TODO: add average distance check

    return array(
        ($locations_data[0] >= $context->memory[MEMORY_CREATION_MIN_LOCATIONS] && $locations_data[1] >= 1 && $locations_data[2] >= 1),
        intval($locations_data[0])
    );
}

function bot_creation_generate_code_pdf($context, $template_name, $location_id, $code, $lat, $lng, $name, $filename_part) {
    $rootdir = realpath(dirname(__FILE__) . '/..');

    // Generating PNG
    exec(sprintf(
        'php %s "%s" "%s"',
        $rootdir . '/html2pdf/qrcode-gen.php',
        BOT_DEEPLINK_START_ROOT . urlencode($code),
        $rootdir . '/data/qrcodes/tmp/game-' . $context->game->game_id . "-{$filename_part}.png"
    ));

    // Generating PDF
    exec(sprintf(
        'php %s "%s" "%s" "%s" "%F" "%F" "%s" "%d" "%s"',
        $rootdir . '/html2pdf/pdf-gen.php',
        $rootdir . '/html2pdf/' . $template_name,
        $rootdir . '/data/qrcodes/tmp/game-' . $context->game->game_id . "-{$filename_part}.pdf",
        $rootdir . '/data/qrcodes/tmp/game-' . $context->game->game_id . "-{$filename_part}.png",
        $lat,
        $lng,
        $name,
        $location_id,
        BOT_DEEPLINK_START_ROOT . urlencode($code)
    ));
}

/**
 * Generates a QR Code package for the current game.
 * The generated package will be at /data/qrcodes/game-GAMEID.zip
 * @return Full path to the generated package or false on failure.
 */
function bot_creation_generate_codes($context) {
    Logger::info("Generating QR Codes for game {$context->game->game_id}", __FILE__, $context);

    $rootdir = realpath(dirname(__FILE__) . '/..');
    $final_file = $rootdir . "/data/qrcodes/game-{$context->game->game_id}.zip";

    // Registration code
    $registration_code = db_scalar_query(sprintf(
        'SELECT `code` FROM `code_lookup` WHERE `type` = \'registration\' AND `game_id` = %d AND `is_disabled` = 0',
        $context->game->game_id
    ));
    if(!$registration_code) {
        Logger::error("Failed retrieving registration code for game {$context->game->game_id}", __FILE__, $context);
        return false;
    }

    Logger::debug("Generating registration code '{$registration_code}'", __FILE__, $context);

    bot_creation_generate_code_pdf($context, 'template-registration.html', 0, $registration_code, 0, 0, "{$context->game->game_name} (in event ‘{$context->game->event_name}’)", 'registration');

    // Locations
    $location_data = db_table_query(sprintf(
        'SELECT `locations`.`location_id`, `locations`.`internal_note`, `locations`.`lat`, `locations`.`lng`, `code_lookup`.`code` FROM `locations` LEFT OUTER JOIN `code_lookup` ON `locations`.`game_id` = `code_lookup`.`game_id` AND `locations`.`location_id` = `code_lookup`.`location_id` WHERE `locations`.`game_id` = %d',
        $context->game->game_id
    ));
    if($location_data === false || $location_data == null) {
        Logger::error("Failed retrieving location codes for game {$context->game->game_id}", __FILE__, $context);
        return false;
    }

    foreach($location_data as $loc) {
        Logger::debug("Generating location code #{$loc[0]} '{$loc[4]}'", __FILE__, $context);

        bot_creation_generate_code_pdf($context, 'template-location.html', $loc[0], $loc[4], $loc[2], $loc[3], $loc[1], "location{$loc[0]}");
    }

    // Zip everything together
    exec(sprintf(
        'zip -Djq "%s" %s',
        $final_file . '.tmp',
        $rootdir . "/data/qrcodes/tmp/game-{$context->game->game_id}-*.pdf"
    ));

    // Clean up temp files
    exec(sprintf(
        'rm %s',
        $rootdir . "/data/qrcodes/tmp/game-{$context->game->game_id}*"
    ));

    // Switch trick to signal file as ready
    exec(sprintf(
        'mv %s %s',
        $final_file . '.tmp',
        $final_file
    ));

    Logger::info("Code generation completed: {$final_file} " . filesize($final_file) . ' bytes written', __FILE__, $context);

    return $final_file;
}

/**
 * Checks whether the code file for the current game is ready.
 * @return Path to the file or false if not ready.
 */
function bot_creation_check_codes($context) {
    $rootdir = realpath(dirname(__FILE__) . '/..');
    $final_file = $rootdir . "/data/qrcodes/game-{$context->game->game_id}.zip";

    return (file_exists($final_file)) ? $final_file : false;
}
