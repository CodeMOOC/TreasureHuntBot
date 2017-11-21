<?php
/**
 * Created by PhpStorm.
 * User: saver
 * Date: 22/03/2017
 * Time: 17:29
 */
/*header("Content-type:application/json; charset=UTF-8");
header("access-control-allow-origin: *");
$data = array(
'is_timeout_th' => 1,
'timeout_value' => '2017-03-30T16:00:00.000Z');
echo json_encode((object)$data);
return;
*/
require_once(dirname(__FILE__) . '/../game.php');
require_once(dirname(__FILE__) . '/../lib.php');
require_once(dirname(__FILE__) . '/backend_lib.php');
require_once(dirname(__FILE__) . '/web_config.php');


$data = [];
$context = Context::create_for_game_admin(GAME_ID);
$timeout = bot_get_game_absolute_timeout($context);

$data = array( 'is_timeout_th' => $timeout != false,
    'timeout_value' => $timeout );


header("Content-type:application/json; charset=UTF-8");
header("access-control-allow-origin: *");

echo json_encode((object)$data);
