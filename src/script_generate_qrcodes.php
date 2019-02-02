<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Generates QR Code package for a given game.
 */

require_once(dirname(__FILE__) . '/game.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/model/context.php');

require_once(dirname(__FILE__) . '/msg_processing_state.php');

if(!isset($argv[1])) {
    die("Usage: " . basename(__FILE__) . " <GAME ID>\n");
}
$game_id = intval($argv[1]);

$context = Context::create_for_game_admin($game_id);

echo "Impersonating game administrator " . $context->sender->get_full_name() . PHP_EOL;

$output = bot_creation_generate_codes($context);

echo "Generated QR Code pack at " . $output . PHP_EOL;
