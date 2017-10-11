<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Bot stats generation logic.
 */

require_once(dirname(__FILE__) . '/model/context.php');

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
    $data = db_table_query("SELECT `groups`.`state`, count(*) FROM `groups` WHERE `groups`.`game_id` = {$context->get_game_id()} AND `groups`.`group_id` NOT IN (SELECT `organizer_id` FROM `games` WHERE `game_id` = {$context->get_game_id()}) GROUP BY `groups`.`state` ORDER BY `groups`.`state` ASC");

    $map = array();
    foreach(STATE_ALL as $c) {
        $map[$c] = 0;
    }
    foreach($data as $d) {
        $map[$d[0]] = $d[1];
    }

    return $map;
}

/**
 * Gets the current rank for playing groups.
 *
 * Groups are ordered following these criteria:
 * 1) number of reached location;
 * 2) group status;
 * 3) last_state_change (ASC).
 *
 * @return array|bool Table of (Telegram ID, group ID, group name, # reached locations,
 *                    group state, last state change) or false on failure.
 */
function bot_get_current_chart_of_playing_groups($context) {
    return db_table_query("SELECT i.`telegram_id`, i.`id`, g.`name`, l.c, g.`state`, TIMESTAMPDIFF(MINUTE, g.`last_state_change`, NOW()) FROM groups AS g LEFT JOIN `identities` AS i ON g.`group_id` = i.`id`INNER JOIN (SELECT group_id, game_id, COUNT(*) AS c, MAX(reached_on) as max_r  FROM assigned_locations WHERE game_id = {$context->get_game_id()} AND reached_on IS NOT NULL GROUP BY group_id) AS l ON l.group_id = g.group_id WHERE g.name IS NOT NULL AND g.game_id = {$context->get_game_id()} ORDER BY l.`c`DESC, g.`state` DESC, l.max_r ASC");
}
