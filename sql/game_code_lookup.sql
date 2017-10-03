# Shows codes for a game
SELECT `code_lookup`.`code`, `code_lookup`.`type`, `code_lookup`.`location_id`, `locations`.`internal_note`, `locations`.`is_start`, `locations`.`is_end` FROM `code_lookup` LEFT OUTER JOIN `locations` ON `code_lookup`.`game_id` = `locations`.`game_id` AND `code_lookup`.`location_id` = `locations`.`location_id` WHERE `code_lookup`.`game_id` = 1;
