<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Questionnaire sending script.
 */

require_once(dirname(__FILE__) . '/game.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/model/context.php');

require_once(dirname(__FILE__) . '/msg_processing_state.php');

if(!isset($argv[1])) {
    die("Usage: " . basename(__FILE__) . " <EVENT ID> [<GROUP ID>]\n");
}
$event_id = intval($argv[1]);

$group_focus = null;
if(isset($argv[2])) {
    $group_focus = intval($argv[2]);
}

$groups = db_table_query(sprintf(
    'SELECT `groups`.`group_id`, `groups`.`name`, `groups`.`state`, `games`.`name` FROM `games` LEFT OUTER JOIN `groups` ON `games`.`game_id` = `groups`.`game_id` WHERE `games`.`event_id` = %d AND `groups`.`state` = %d',
    $event_id,
    STATE_GAME_WON
));

printf("Found %d teams in event %d.\n", sizeof($groups), $event_id);

foreach($groups as $group_info) {
    if($group_focus && $group_focus != $group_info[0]) {
        continue;
    }

    printf("Processing team '%s' #%d...\n", $group_info[1], $group_info[0]);

    $context = new Context((int)$group_info[0]);

    bot_set_group_state($context, STATE_FEEDBACK);

    $context->comm->reply("Hello, team “%GROUP_NAME%”! 😊\nYou recently took part in the treasure hunt for the european CodeWeek 2017. We would like to ask a couple of simple questions about the game. Once you answer all questions, we will send you a certificate for your victory. 🏅");
    $context->comm->reply("Let’s begin!");

    msg_processing_handle_group_state($context);
}
