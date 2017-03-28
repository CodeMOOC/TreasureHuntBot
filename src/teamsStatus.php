<?php
/**
 * Created by PhpStorm.
 * User: saver
 * Date: 22/03/2017
 * Time: 17:29
 */

require_once('game.php');
require_once('lib.php');
require_once('web/backend_lib.php');



$data = [];
$context = new Context(2);
$playing_groups = bot_get_current_chart_of_playing_groups($context);

foreach ($playing_groups as $pg) {
    $group_data = array(
        'team' => $pg[2],
        'team_id' => $pg[1]
    );

    $group_data['pos'] = bot_get_group_count_of_reached_locations($context, $group_data['team_id']);

    $last_reached_loc = bot_get_group_last_reached_location($context, $group_data['team_id']);
    if($last_reached_loc && count($last_reached_loc) > 0) {
        $group_data['lat'] = floatval($last_reached_loc[0][1]);
        $group_data['lng'] = floatval($last_reached_loc[0][2]);
    }

    $last_assigned_loc = bot_get_group_last_assigned_location($context, $group_data['team_id']);
    if($last_assigned_loc && count($last_assigned_loc) > 0) {
        $group_data['next_lat'] = floatval($last_assigned_loc[0][1]);
        $group_data['next_lng'] = floatval($last_assigned_loc[0][2]);
    }

    if(true || count($group_data) > 4) {
        $data [] = (object)$group_data;
    }
}

header("Content-type:application/json; charset=UTF-8");
header("access-control-allow-origin: *");
echo json_encode($data);

/** STUB **/
/*
function random(){
    return mt_rand() / mt_getrandmax();
}

$data = array();
for($i = 0; $i < 5; $i++) {
    $data[] = (object)array('team' => 'Team '.random()*10,
        'lat' => 43.72 + (random()*0.01),
        'lng' => 12.63 + (random()*0.01),
        'next_lat' => 43.72 + (random()*0.01),
        'next_lng' => 12.63 + (random()*0.01),
        'pos' => random()*18);
}

echo json_encode($data);*/