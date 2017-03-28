<?php
/**
 * Created by PhpStorm.
 * User: saver
 * Date: 22/03/2017
 * Time: 17:29
 */

function random(){
    return mt_rand() / mt_getrandmax();
}

header("Content-type:application/json; charset=UTF-8");
header("access-control-allow-origin: *");

$data = array();
for($i = 0; $i < 5; $i++) {
    $data[] = (object)array('team' => 'Team '.random()*10,
        'lat' => 43.72 + (random()*0.01),
        'lng' => 12.63 + (random()*0.01),
        'next_lat' => 43.72 + (random()*0.01),
        'next_lng' => 12.63 + (random()*0.01),
        'pos' => random()*18);
}

echo json_encode($data);