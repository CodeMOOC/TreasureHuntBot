<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Administrator command message processing.
 */

function admin_broadcast($context, $min_group_state, $message) {
    $payload = extract_command_payload($message);
    Logger::debug("Broadcasting to groups with state >= {$min_group_state}: {$payload}", __FILE__, $context);

    $groups = bot_get_telegram_ids_of_groups($context, $min_group_state);
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
            "/broadcast\_registered text\n" .
            "/broadcast\_playing text\n" .
            "/broadcast\_all text\n" .
            "Broadcasts _text_ to registered, playing, or all groups respectively. You may use _%NAME%_ (leader's name) and _%GROUP%_ (group name) placeholders in the message."
        );
        return true;
    }

    /* Broadcasting */
    if(starts_with($text, '/broadcast_registered')) {
        admin_broadcast($context, STATE_REG_NAME, $text);
        return true;
    }
    if(starts_with($text, '/broadcast_playing')) {
        admin_broadcast($context, STATE_GAME_LOCATION, $text);
        return true;
    }
    if(starts_with($text, '/broadcast_all')) {
        admin_broadcast($context, STATE_NEW, $text);
        return true;
    }
    if(starts_with($text, '/broadcast')) {
        $context->reply("Pick one of the following commands: /broadcast\_registered, /broadcast\_playing, or /broadcast\_all. See /help for more info.");
        return true;
    }
}
