<?php
/**
 * Created by PhpStorm.
 * User: saver
 * Date: 28/03/2017
 * Time: 16:42
 */

function get_group_points($context, $group_id){
    $point = bot_get_count_of_reached_locations($context, $group_id);

    return $point;
}
