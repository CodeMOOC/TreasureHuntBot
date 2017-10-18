<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Administrator command message processing.
 */

function msg_processing_admin($context) {
    if(!$context->game || !$context->game->is_admin) {
        return false;
    }

    if(!$context->is_message()) {
        return false;
    }
    $text = $context->message->text;

    if($text === '/help') {
        $context->comm->reply(
            "You are running game “%GAME_NAME%” <code>#%GAME_ID%</code>.\n\n" .
            "👑 <b>Administration commands:</b>\n" .
            "/overview: general overview of teams and their state.\n" .
            "/leaderboard: leaderboard of playing teams.\n" .
            "/info <code>id</code>: get details about a team."
        );
        return true;
    }

    /* Status */
    else if(starts_with($text, '/overview')) {
        $states = bot_stats_generate_group_state_map($context);
        $group_count = bot_stats_ready_groups($context);

        $context->comm->reply(
            "<b>Team registration</b> ✍\n" .
            "1) New: {$states[STATE_NEW]}\n" .
            "2) Verified (puzzle ok): {$states[STATE_REG_VERIFIED]}\n" .
            "3) Reserved (name ok): {$states[STATE_REG_NAME]}\n" .
            "5) Counted (participants ok): {$states[STATE_REG_NUMBER]}\n" .
            "6) Ready (avatar ok): {$states[STATE_REG_READY]}\n\n" .
            "<b>Playing teams</b>\n" .
            "Moving to location: {$states[STATE_GAME_LOCATION]}\n" .
            "Taking selfie: {$states[STATE_GAME_SELFIE]}\n" .
            "Solving puzzle: {$states[STATE_GAME_PUZZLE]}\n" .
            "Moving to last location: {$states[STATE_GAME_LAST_LOC]}\n" .
            "Solving last puzzle: {$states[STATE_GAME_LAST_PUZ]}\n" .
            "Won: {$states[STATE_GAME_WON]} 🏆\n\n" .
            "<b>" . (int)$group_count[1] . " participants in " . (int)$group_count[0] . " groups</b> (playing) 👥"
        );

        return true;
    }

    else if(starts_with($text, '/info ')) {
        $payload = extract_command_payload($text);
        $group_id = intval($payload);

        if($group_id === 0) {
            $context->comm->reply('Use "/info <code>group_id</code>"!');
            return true;
        }

        $info = bot_get_group_info($context, $group_id);
        if($info === false) {
            $context->comm->reply(__('failure_general'));
            return true;
        }
        if($info == null) {
            $context->comm->reply("No group with that ID in current game. See /leaderboard.");
            return true;
        }

        $outbound =
            "👥 <b>Group #{$group_id} “{$info[1]}”</b>\n" .
            "Led by {$info[2]}, with {$info[5]} participants.\n" .
            map_state_to_string(STATE_READABLE_MAP, intval($info[3])) . ", {$info[4]} minutes ago.\n";

        if($info[3] == STATE_GAME_LOCATION) {
            $location_info = bot_get_last_assigned_location($context, $group_id);
            if($location_info) {
                $outbound .= "Assigned location #{$location_info[0]} “{$location_info[3]}”.\n";
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
            $outbound .= "Last seen at location #{$last_location_info[0]} <i>{$last_location_info[3]}</i>, {$last_location_info[4]} minutes ago.\n";
        }

        $context->comm->reply($outbound);

        return true;
    }

    else if($text === '/leaderboard' || $text === '/groups' || $text === '/info') {
        $groups = bot_get_current_chart_of_playing_groups($context);

        $outbound = "👥 <b>Leaderboard:</b>";
        foreach($groups as $group) {
            $outbound .= "\n· <code>#{$group[1]}</code> “{$group[2]}”: {$group[3]} loc’s, " . mb_strtolower(map_state_to_string(STATE_READABLE_MAP, $group[4])) . " ({$group[5]} mins ago).";
        }

        $context->comm->reply($outbound);

        return true;
    }

    /* QR Code debugging */
    else if(starts_with($text, '/start')) {
        $payload = extract_command_payload($text);

        $code_info = db_row_query(sprintf(
            'SELECT `type`, `event_id`, `game_id`, `location_id`, `is_disabled` FROM `code_lookup` WHERE `code` = \'%s\'',
            db_escape($payload)
        ));
        if($code_info == null || $code_info === false) {
            $context->comm->reply("Unknown code");
            return true;
        }

        if($code_info[0] === 'creation' || $code_info[0] === 'registration') {
            // Skip! Creaton QR Codes must not be handled, otherwise administrators
            // will not be able to create new events
            return false;
        }

        $event_id = intval($code_info[1]);
        $game_id = intval($code_info[2]);
        $location_id = intval($code_info[3]);
        $is_disabled = ($code_info[4] == 1);

        $outbound = "<b>QR Code:</b> <code>{$payload}</code>\nType: <i>{$code_info[0]}</i>";
        if($event_id !== 0) $outbound .= "\nEvent #{$event_id}";
        if($game_id !== 0) $outbound .= "\nGame #{$game_id}";
        if($location_id !== 0) $outbound .= "\nLocation #{$location_id}";
        if($is_disabled) $outbound .= "\n<i>Code disabled.</i>";

        if($game_id === $context->game->game_id && $location_id !== 0) {
            // Location info
            $location_info = bot_get_location_info($context, $location_id);
            if($location_info == null || $location_info === false) {
                $outbound .= " (Linked location does not exist in game! ⚠️)";
            }
            else {
                $outbound .= " ({$location_info[4]})";
            }
        }

        $context->comm->reply($outbound);

        // Location position, if set
        if(isset($location_info)) {
            telegram_send_location($context->comm->get_telegram_id(), $location_info[0], $location_info[1]);
        }

        return true;
    }

    return false;
}
