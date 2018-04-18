<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Generates individual certificates for members of a game.
 */

require_once(dirname(__FILE__) . '/game.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/model/context.php');

require_once(dirname(__FILE__) . '/msg_processing_state.php');

if(!isset($argv[1])) {
    die("Usage: " . basename(__FILE__) . " <GAME ID>\n");
}
$game_id = intval($argv[1]);

$groups = db_table_query(sprintf(
    'SELECT `groups`.`group_id`, `groups`.`name`, `groups`.`state`, `games`.`name` FROM `games` LEFT OUTER JOIN `groups` ON `games`.`game_id` = `groups`.`game_id` WHERE `games`.`game_id` = %d AND `groups`.`state` >= %d',
    $game_id,
    STATE_GAME_WON
));

printf("Found %d teams in game %d.\n", sizeof($groups), $game_id);

foreach($groups as $group_info) {
    printf("Processing team '%s' #%d...\n", $group_info[1], $group_info[0]);

    // Generate certificate and montages
    $reached_locations_count = db_scalar_query(sprintf(
        'SELECT count(*) FROM `assigned_locations` WHERE `game_id` = %d AND `group_id` = %d AND `reached_on` IS NOT NULL',
        $game_id,
        $group_info[0]
    ));
    $total_locations_count = $reached_locations_count + 1 + (($group_info[2] > STATE_GAME_LAST_SELF) ? 1 : 0); // start and end

    $rootdir = realpath(dirname(__FILE__) . '/..');
    $identifier = "{$game_id}-{$group_info[0]}";

    printf("Generating montage...\n");

    exec("montage {$rootdir}/data/selfies/{$identifier}-*.jpg -background \"#0000\" -auto-orient -geometry 150x150 +polaroid -tile {$total_locations_count}x1 {$rootdir}/data/certificates/individual/{$identifier}-montage.png");

    printf("Generating certificate...\n");

    exec("php {$rootdir}/html2pdf/ind-cert-gen.php \"{$rootdir}/data/certificates/individual/{$identifier}-certificate.pdf\" 0 \"{$group_info[1]}\" \"completed\" \"{$group_info[3]}\" \"{$identifier}\"");
}
