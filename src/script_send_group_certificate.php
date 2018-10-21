<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Generates individual certificate for a group.
 */

require_once(dirname(__FILE__) . '/game.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/model/context.php');

require_once(dirname(__FILE__) . '/msg_processing_state.php');

if(!isset($argv[1]) || !isset($argv[2])) {
    die("Usage: " . basename(__FILE__) . " <GAME ID> <GROUP ID> [\"Message\"]\n");
}
$game_id = intval($argv[1]);
$group_id = intval($argv[2]);
$add_message = $argv[3];

// Load context
$context = new Context((int)$group_id);
$context->set_active_game($game_id, false, false);

// Generate certificate and montages
$intermediate_locations_count = db_scalar_query(sprintf(
    'SELECT `min_num_locations` FROM `events` WHERE `event_id` = %d',
    $context->game->event_id
));
$total_locations_count = $intermediate_locations_count + 2; // start and end

$rootdir = realpath(dirname(__FILE__) . '/..');
$identifier = "{$context->game->game_id}-{$context->get_internal_id()}";

echo "Generating montage with identifier: '" . $identifier . "'" . PHP_EOL;

exec("montage {$rootdir}/data/selfies/{$identifier}-*.jpg -background \"#0000\" -auto-orient -geometry 150x150 +polaroid -tile {$total_locations_count}x1 {$rootdir}/data/certificates/{$identifier}-montage.png");

exec("php {$rootdir}/html2pdf/cert-gen.php \"{$rootdir}/data/certificates/{$identifier}-certificate.pdf\" {$context->game->group_participants} \"" . addslashes($context->game->group_name) . "\" \"completed\" \"{$context->game->game_name}\" \"{$identifier}\"");

echo "Delivering certificate" . PHP_EOL;

if(!empty($add_message)) {
    echo "Writing out additional message" . PHP_EOL;
    $context->comm->reply($add_message);
}

$context->comm->document("{$rootdir}/data/certificates/{$identifier}-certificate.pdf", __('questionnaire_attachment_caption'));
$context->comm->reply(__('questionnaire_finish_thankyou'));

bot_set_group_state($context, STATE_CERT_SENT);
