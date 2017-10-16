# Global, per-event leaderboard
SELECT `groups`.`game_id`, `games`.`name`, `groups`.`group_id`, `groups`.`name`, `groups`.`state`, count(`assigned_locations`.`group_id`) AS `reached`, `groups`.`last_state_change` FROM `games` LEFT OUTER JOIN `groups` ON `games`.`game_id` = `groups`.`game_id` LEFT OUTER JOIN `assigned_locations` ON `assigned_locations`.`game_id` = `games`.`game_id` AND `assigned_locations`.`group_id` = `groups`.`group_id` WHERE `games`.`event_id` = 7 AND `games`.`state` >= 128 GROUP BY `assigned_locations`.`group_id` ORDER BY `reached` DESC, `groups`.`state` DESC, `groups`.`last_state_change` ASC;

# Number of groups and participants
SELECT `games`.`name`, count(*), SUM(`groups`.`participants_count`) FROM `games` LEFT OUTER JOIN `groups` ON `games`.`game_id` = `groups`.`game_id` WHERE `games`.`event_id` = 7 AND `games`.`state` >= 128 GROUP BY `games`.`game_id`;

# Number of participants
SELECT count(*), SUM(`groups`.`participants_count`) FROM `games` LEFT OUTER JOIN `groups` ON `games`.`game_id` = `groups`.`game_id` WHERE `games`.`event_id` = 7 AND `games`.`state` >= 128;
