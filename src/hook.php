<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Basic message processing webhook end-point for your bot.
 */

require_once(dirname(__FILE__) . '/lib.php');

// Get input contents
// Notice: we use php://input (the HTTP request body) normally, but switch
//         over to php://stdin (standard input channel) when running from
//         command line, in order to let you test the script via input pipe
$content = file_get_contents(is_cli() ? "php://stdin" : "php://input");

// Decode contents as JSON
$update = json_decode($content, true);

if (!$update) {
    Logger::fatal('Bad message received (not JSON)', __FILE__);
}
else {
    include 'msg_processing_core.php';
}
