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

function admin_broadcast($context, $message, $min_group_state = STATE_NEW, $max_group_state = STATE_GAME_WON, $admins = false) {
    $payload = extract_command_payload($message);
    if(!$payload) {
        return;
    }

    Logger::debug("Broadcasting to groups with state {$min_group_state}-{$max_group_state}: {$payload}", __FILE__, $context);

    $groups = bot_get_telegram_ids_of_groups($context, $min_group_state, $max_group_state, $admins);
    foreach($groups as $group) {
        $hydrated = hydrate($payload, array(
            '%NAME%' => $group[1],
            '%GROUP%' => $group[2]
        ));

        if(telegram_send_message($group[0], $hydrated, array(
            'parse_mode' => 'HTML'
        )) === false) {
            Logger::error("Broadcast failed to ID {$group[0]} ({$group[1]}, group {$group[2]})");
        }
    }

    Logger::info("Sent broadcast message to " . sizeof($groups) . " groups", __FILE__, $context, true);
}

function msg_processing_admin($context) {
    $text = $context->get_message()->text;

    if(starts_with($text, '/help')) {
        $context->reply(
            "👑 *Administration commands*\n" .
            "/send id message: sends a message to a group by ID.\n" .
            "/status: status of the game and group statistics.\n" .
            "/channel: sends a message to the channel.\n" .
            "/confirm ok: confirms all reserved groups and starts 2nd step of registration.\n" .
            "/broadcast\_reserved, /broadcast\_ready, /broadcast\_playing, /broadcast\_all, /broadcast\_admin: broadcasts following text to reserved, ready, playing, all, or admin-owned groups respectively. You may use _%NAME%_ (leader’s name) and _%GROUP%_ (group name) placeholders in the message."
        );
        return true;
    }

    /* Text messages */
    if(starts_with($text, '/send')) {
        $payload = extract_command_payload($text);
        $split_pos = strpos($payload, ' ');
        if($split_pos === false || $split_pos === 0) {
            $context->reply("Specify group ID and message, separated by space.");
            return true;
        }

        $group_id = intval(substr($payload, 0, $split_pos));
        $message = substr($payload, $split_pos + 1);
        if(empty($message)) {
            $context->reply("Specify a valid message to send.");
            return true;
        }

        $telegram_id = bot_get_telegram_id($context, $group_id);
        if(!$telegram_id) {
            $context->reply("Group with ID {$group_id} not found.");
            return true;
        }

        Logger::info("Sending '{$message}' to group #{$group_id} (Telegram ID {$telegram_id})", __FILE__, $context, true);

        if(telegram_send_message($telegram_id, $message, array(
            'parse_mode' => 'HTML',
        )) === false) {
            $context->reply("Failed to send message.");
        }

        return true;
    }

    /* Status */
    if(starts_with($text, '/status')) {
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

    if(starts_with($text, '/info')) {
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

    /* Broadcasting */
    if(starts_with($text, '/broadcast_reserved')) {
        admin_broadcast($context, $text, STATE_REG_NAME, STATE_REG_NAME);
        return true;
    }
    if(starts_with($text, '/broadcast_ready')) {
        admin_broadcast($context, $text, STATE_REG_READY, STATE_REG_READY);
        return true;
    }
    if(starts_with($text, '/broadcast_playing')) {
        admin_broadcast($context, $text, STATE_GAME_LOCATION, STATE_GAME_LAST_PUZ);
        return true;
    }
    if(starts_with($text, '/broadcast_all')) {
        admin_broadcast($context, $text);
        return true;
    }
    if(starts_with($text, '/broadcast_admin')) {
        admin_broadcast($context, $text, STATE_NEW, STATE_GAME_WON, true);
        return true;
    }
    if(starts_with($text, '/broadcast')) {
        $context->reply("Pick one of the following commands: /broadcast\_reserved, /broadcast\_ready, /broadcast\_playing, /broadcast\_all, or /broadcast\_admin. See /help for more info.");
        return true;
    }
}
