<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Basic message processing in pull mode for your bot.
 */

include('lib.php');

// Reload latest update ID received (if any) from persistent store
$last_update = @file_get_contents("pull-last-update.txt");

// Fetch updates from API
// Note: we remember the last fetched ID and query for the next one, if available.
//       The third parameter enabled long-polling. Switch to any number of seconds
//       to enable (the request will hang until timeout or until a message is received).
$content = telegram_get_updates(intval($last_update) + 1, 1, 60);
if($content === false) {
    error_log('Failed to fetch updates from API');
    exit;
}
if(count($content) == 0) {
    echo 'No new messages.' . PHP_EOL;
    exit;
}

$first_update = $content[0];

echo 'New update received:' . PHP_EOL;
print_r($first_update);

// Updates have the following structure:
// [
//     {
//         "update_id": 123456789,
//         "message": {
//              ** message object **
//         }
//     }
// ]

$update_id = $first_update['update_id'];
$message = $first_update['message'];

// Update persistent store with latest update ID received
file_put_contents("pull-last-update.txt", $update_id);

include 'msg_processing_core.php';
