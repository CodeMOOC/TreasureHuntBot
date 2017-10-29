<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Generates individual location QR Codes packages.
 */

require_once(dirname(__FILE__) . '/game.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/model/context.php');

require_once(dirname(__FILE__) . '/msg_processing_state.php');

if(!isset($argv[1])) {
    die("Usage: " . basename(__FILE__) . " <GAME ID>\n");
}
$game_id = intval($argv[1]);

$organizer_id = db_scalar_query(sprintf(
    'SELECT `organizer_id` FROM `games` WHERE `game_id` = %d',
    $game_id
));
if(!$organizer_id) {
    die("Unable to load organizer of game #$game_id\n");
}

// Load organizer context and force switch to set game (organizer might be using different game)
$context = new Context((int)$organizer_id);
$context->set_active_game($game_id, true, false);

$final_file = bot_creation_generate_codes($context);

echo "Done." . PHP_EOL;
