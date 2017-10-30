<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Pushes a team to victory automatically.
 */

require_once(dirname(__FILE__) . '/game.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/model/context.php');

require_once(dirname(__FILE__) . '/msg_helpers.php');
require_once(dirname(__FILE__) . '/msg_processing_state.php');

if(!isset($argv[1]) || !isset($argv[2])) {
    die("Usage: " . basename(__FILE__) . " <GAME ID> <GROUP ID>\n");
}
$game_id = intval($argv[1]);
$group_id = intval($argv[2]);

$context = new Context($group_id);
if(!$context->game || $context->game->game_id !== $game_id) {
    die("Group #$group_id is not playing game or playing game $game_id\n");
}
if($context->game->is_admin) {
    die("Individual #$group_id is currently in admin mode\n");
}

msg_process_victory($context, null, $game_id);

echo "Done." . PHP_EOL;
