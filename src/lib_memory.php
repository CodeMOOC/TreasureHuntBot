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

    if($data === false || $data === null) {
        return array();
    }

    Logger::debug("Memory load: '{$data}'", __FILE__);

    return json_decode($data, true);
}

function memory_persist($telegram_id, $data) {
    // Clean-up data, removing null entries
    $data = array_filter($data, function($val) {
        return !is_null($val);
    });

    $encoded = json_encode($data);

    if(db_perform_action(sprintf(
        "REPLACE INTO `conversation_memories` (`telegram_id`, `data`, `last_update`) VALUES(%d, '%s', NOW())",
        $telegram_id,
        db_escape($encoded)
    )) === false) {
        Logger::warning('Failed to store conversation memory', __FILE__);
    }

    Logger::debug("Memory store: '{$encoded}'", __FILE__);
}
