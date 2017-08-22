<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Administrator command message processing.
 */

function admin_translate_group_state($state) {
    switch($state) {
        case STATE_NEW:
            return "Newly registered";
        case STATE_REG_VERIFIED:
            return "Captcha verified";
        case STATE_REG_NAME:
            return "Name recorded";
        case STATE_REG_NUMBER:
            return "Participants recorded";
        case STATE_REG_READY:
            return "Ready to play";
        case STATE_GAME_LOCATION:
            return "Reaching location";
        case STATE_GAME_SELFIE:
            return "Snapping selfie";
        case STATE_GAME_PUZZLE:
            return "Solving puzzle";
        case STATE_GAME_LAST_LOC:
            return "Reaching last location";
        case STATE_GAME_LAST_PUZ:
            return "Solving last puzzle";
        case STATE_GAME_WON:
            return "Won the game";
    }

    return "Other (?)";
}

function admin_broadcast($context, $message, $min_group_state = STATE_NEW, $max_group_state = STATE_GAME_WON) {
    $payload = extract_command_payload($message);
    if(!$payload) {
        $context->reply("No message given.");
        return;
    }

    Logger::debug("Broadcasting to groups with state {$min_group_state}-{$max_group_state}: {$payload}", __FILE__, $context);

    $groups = bot_get_telegram_ids_of_groups($context, $min_group_state, $max_group_state, $admins);
    $failures = 0;
    foreach($groups as $group) {
        $hydrated = hydrate($payload, array(
            '%NAME%' => $group[1],
            '%GROUP%' => $group[2]
        ));

        if(telegram_send_message($group[0], $hydrated, array(
            'parse_mode' => 'HTML'
        )) === false) {
            $failures++;
            Logger::error("Broadcast failed to group #{$group[0]}", __FILE__, $context);
        }
    }

    Logger::info("Sent broadcast message '{$message}' to " . count($groups) . " groups ({$failures} failures)", __FILE__, $context);
    $context->reply("Broadcast message sent to " . count($groups) . " ({$failures} failures)");

    return true;
}

function msg_processing_admin($context) {
    $text = $context->get_message()->text;

    if(starts_with($text, '/help')) {
        $context->reply(
            "👑 <b>Administration commands</b>\n" .
            "/send <code>id</code> <code>message</code>: sends a message to a group by ID.\n" .
            "/status: status of the game and group states.\n" .
            "/groups: leaderbord of playing groups.\n" .
            "/info <code>id</code>: get info about a group.\n" .
            "/broadcast_ready, /broadcast_playing, /broadcast_all: broadcasts following text to ready, playing, or all groups respectively. You may use HTML and <code>%NAME%</code> (leader’s name) and <code>%GROUP%</code> (group name) placeholders in the message."
        );
        return true;
    }

    /* Text messages */
    else if(starts_with($text, '/send')) {
        $payload = extract_command_payload($text);
        $split_pos = strpos($payload, ' ');
        if($split_pos === false || $split_pos === 0) {
            $context->reply('Use /send <code>group_id</code> <code>message</code>, separated by space.');
            return true;
        }

        $group_id = intval(substr($payload, 0, $split_pos));
        if($group_id === 0) {
            $context->reply('Use /send <code>group_id</code> <code>message</code>, with numeric ID.');
            return true;
        }
        $message = substr($payload, $split_pos + 1);
        if(empty($message)) {
            $context->reply("Use /send <code>group_id</code> <code>message</code>, with a non-empty message.");
            return true;
        }

        $telegram_id = bot_get_telegram_id($context, $group_id);
        if($telegram_id === false) {
            $context->reply(TEXT_FAILURE_QUERY);
            return true;
        }
        else if($telegram_id == null) {
            $context->reply("No group with that ID in current game (#%GAME_ID%).");
            return true;
        }

        Logger::info("Sending '{$message}' to group #{$group_id} @ Telegram ID {$telegram_id}", __FILE__, $context);

        if($context->send($telegram_id, $message) === false) {
            $context->reply('Failed to send.');
        }

        return true;
    }

    /* Status */
    else if(starts_with($text, '/status')) {
        $states = bot_get_group_count_by_state($context);
        $participants_count = bot_get_ready_participants_count($context);

        $context->reply(
            "<b>Group registration</b> ✍\n" .
            "1) New: {$states[STATE_NEW]}\n" .
            "2) Verified (puzzle ok): {$states[STATE_REG_VERIFIED]}\n" .
            "3) Reserved (name ok): {$states[STATE_REG_NAME]}\n" .
            "5) Counted (participants ok): {$states[STATE_REG_NUMBER]}\n" .
            "6) Ready (avatar ok): {$states[STATE_REG_READY]}\n" .
            "<b>Game status</b>\n" .
            "Moving to location: {$states[STATE_GAME_LOCATION]}\n" .
            "Taking selfie: {$states[STATE_GAME_SELFIE]}\n" .
            "Solving puzzle: {$states[STATE_GAME_PUZZLE]}\n" .
            "Moving to last location: {$states[STATE_GAME_LAST_LOC]}\n" .
            "Solving last puzzle: {$states[STATE_GAME_LAST_PUZ]}\n" .
            "Won: {$states[STATE_GAME_WON]} 🏆\n\n" .
            "<b>{$participants_count} participants</b> 👥 (ready/playing)\n" .
            "(Data does <i>not</i> include groups by administrators.)"
        );

        return true;
    }

    else if(starts_with($text, '/info')) {
        $payload = extract_command_payload($text);
        $group_id = intval($payload);

        if($group_id === 0) {
            $context->reply('Use "/info <code>group_id</code>"!');
            return true;
        }

        $info = bot_get_group_info($context, $group_id);
        if($info === false) {
            $context->reply(TEXT_FAILURE_QUERY);
            return true;
        }
        if($info == null) {
            $context->reply("No group with that ID in current game (#%GAME_ID%).");
            return true;
        }

        $outbound =
            "👥 <b>Group #{$group_id} '{$info[1]}'</b>\n" .
            "Led by {$info[2]}, with {$info[5]} participants.\n" .
            admin_translate_group_state(intval($info[3])) . " ({$info[3]}), {$info[4]} minutes ago.\n";

        if($info[3] == STATE_GAME_LOCATION) {
            $location_info = bot_get_last_assigned_location($context, $group_id);
            if($location_info) {
                $outbound .= "Assigned location #{$location_info[0]} {$location_info[3]}.\n";
            }
        }
        else if($info[3] == STATE_GAME_PUZZLE) {
            $riddle_info = bot_get_current_assigned_riddle($context, $group_id);
            if($riddle_info) {
                $outbound .= "Riddle #{$riddle_info[2]} (solution <code>{$riddle_info[1]}</code>).\n";
            }
        }

        $last_location_info = bot_get_last_reached_location($context, $group_id);
        if($last_location_info) {
            $outbound .= "Last seen in location #{$last_location_info[0]} {$last_location_info[3]}, {$last_location_info[4]} minutes ago.\n";
        }

        $context->reply($outbound);

        return true;
    }

    else if(starts_with($text, '/groups')) {
        $groups = bot_get_current_chart_of_playing_groups($context);

        $outbound = "👥 <b>Playing groups</b>";
        foreach($groups as $group) {
            $outbound .= "\n<b>· {$group[2]}</b> (#{$group[1]}): {$group[3]} loc’s, " . mb_strtolower(admin_translate_group_state($group[4])) . " ({$group[5]} minutes ago).";
        }

        $context->reply($outbound);

        return true;
    }

    /* Broadcasting */
    else if(starts_with($text, '/broadcast_ready')) {
        admin_broadcast($context, $text, STATE_REG_READY, STATE_REG_READY);
        return true;
    }
    else if(starts_with($text, '/broadcast_playing')) {
        admin_broadcast($context, $text, STATE_GAME_LOCATION, STATE_GAME_LAST_PUZ);
        return true;
    }
    else if(starts_with($text, '/broadcast_all')) {
        admin_broadcast($context, $text);
        return true;
    }
    else if(starts_with($text, '/broadcast')) {
        $context->reply("Pick one of the following commands: /broadcast_ready, /broadcast_playing, or /broadcast_all. See /help for more info.");
        return true;
    }

    /* Code debugging */
    else if(starts_with($text, '/start')) {
        $payload = extract_command_payload($text);

        $code_info = db_row_query("SELECT `type`, `event_id`, `game_id`, `location_id`, `is_disabled` FROM `code_lookup` WHERE `code` = '" . db_escape($payload) . "'");
        if($code_info === false) {
            $context->reply(TEXT_FAILURE_QUERY);
            return true;
        }
        if($code_info == null) {
            $context->reply('Unknown code.');
            return true;
        }

        $event_id = intval($code_info[1]);
        $game_id = intval($code_info[2]);
        $location_id = intval($code_info[3]);
        $is_disabled = ($code_info[4] == 1);

        $outbound = "<b>{$payload}</b>\nType: <code>{$code_info[0]}</code>";
        if($event_id !== 0) $outbound .= "\nEvent #{$event_id}";
        if($game_id !== 0) $outbound .= "\nGame #{$game_id}";
        if($location_id !== 0) $outbound .= "\nLocation #{$location_id}";

        if($game_id === $context->get_game_id() && $location_id !== 0) {
            // Location info
            $location_info = bot_get_location_info($context, $location_id);
            if($location_info == null || $location_info === false) {
                $outbound .= " (Linked location does not exist in game! ⚠️)";
            }
            else {
                $outbound .= " ({$location_info[4]})";
            }
        }

        $context->reply($outbound);

        // Location position, if set
        if(isset($location_info)) {
            telegram_send_location($context->get_telegram_chat_id(), $location_info[0], $location_info[1]);
        }

        return true;
    }
}
