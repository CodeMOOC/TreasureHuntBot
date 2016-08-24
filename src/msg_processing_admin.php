<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Administrator command message processing.
 */

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

        telegram_send_message($group[0], $hydrated, array(
            'parse_mode' => 'Markdown'
        ));
    }

    Logger::info("Sent broadcast message to " . sizeof($groups) . " groups", __FILE__, $context, true);
}

function msg_processing_admin($context) {
    $text = $context->get_message()->text;

    if(starts_with($text, '/help')) {
        $context->reply(
            "👑 *Administration commands*\n" .
            "/status: status of the game and group statistics.\n" .
            "/broadcast\_reserved, /broadcast\_ready, /broadcast\_playing, /broadcast\_all, /broadcast\_admin: broadcasts following text to reserved, ready, playing, all, or admin-owned groups respectively. You may use _%NAME%_ (leader’s name) and _%GROUP%_ (group name) placeholders in the message."
        );
        return true;
    }

    /* Status */
    if(starts_with($text, '/status')) {
        $states = bot_get_group_count_by_state($context);
        $context->reply(
            "*Group registration* ✍\n" .
            "1) New: {$states[STATE_NEW]}\n" .
            "2) Verified (puzzle ok): {$states[STATE_REG_VERIFIED]}\n" .
            "3) Reserved (name ok): {$states[STATE_REG_NAME]}\n" .
            "4) Confirmed: {$states[STATE_REG_CONFIRMED]}\n" .
            "5) Counted (participants ok): {$states[STATE_REG_NUMBER]}\n" .
            "6) Ready (avatar ok): {$states[STATE_REG_READY]}\n" .
            "*Game status* 🗺\n" .
            "Moving to location: {$states[STATE_GAME_LOCATION]}\n" .
            "Taking selfie: {$states[STATE_GAME_SELFIE]}\n" .
            "Solving puzzle: {$states[STATE_GAME_PUZZLE]}\n" .
            "Moving to last location: {$states[STATE_GAME_LAST_LOC]}\n" .
            "Solving last puzzle: {$states[STATE_GAME_LAST_PUZ]}\n" .
            "Won: {$states[STATE_GAME_WON]} 🏆"
        );

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