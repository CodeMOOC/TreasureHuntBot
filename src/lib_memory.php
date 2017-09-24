<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Support library for conversational memory.
 */

require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/lib_log.php');
require_once(dirname(__FILE__) . '/lib_database.php');

function memory_load_for_user($telegram_id) {
    $data = db_scalar_query("SELECT `data` FROM `conversation_memories` WHERE `telegram_id` = {$telegram_id}");

    if(!$data) {
        return new stdClass();
    }

    return json_decode($data, false);
}

function memory_persist($telegram_id, $data) {
    // Clean-up data, removing null entries
    $data = (object)array_filter((array)$data, function($val) {
        return !is_null($val);
    });

    $encoded = json_encode($data);

    if(db_perform_action("REPLACE INTO `conversation_memories` (`telegram_id`, `data`, `last_update`) VALUES({$telegram_id}, '" . db_escape($encoded) . "', NOW())") === false) {
        Logger::warning('Failed to store conversation memory', __FILE__);
    }
}
