-- Replicates an existing game, duplicating all locations and moving existing codes

-- Game variables
SET @prev_game = 166;
SET @next_game = 184;

-- Duplicate clusters
CREATE TEMPORARY TABLE `tmp_clusters` SELECT * FROM `game_location_clusters` WHERE `game_id` = @prev_game;
UPDATE `tmp_clusters` SET `game_id` = @next_game;
INSERT INTO `game_location_clusters` SELECT * FROM `tmp_clusters`;

-- Duplicate locations
CREATE TEMPORARY TABLE `tmp_locations` SELECT * FROM `locations` WHERE `game_id` = @prev_game;
UPDATE `tmp_locations` SET `game_id` = @next_game;
INSERT INTO `locations` SELECT * FROM `tmp_locations`;

-- Codes for previous game must point to new game
UPDATE `code_lookup` SET `game_id` = @next_game WHERE `game_id` = @prev_game;

-- Placeholder code changes
-- UPDATE `code_lookup` SET `location_id` = 13 WHERE `code` = '166-23-GOlSCap';
-- UPDATE `code_lookup` SET `location_id` = 12 WHERE `code` = '166-21-oH7sx5i';
-- UPDATE `code_lookup` SET `location_id` = 10 WHERE `code` = '166-35-CNksIkX';

-- Display final locations list
SELECT `locations`.`location_id`, `locations`.`internal_note`, `locations`.`lat`, `locations`.`lng`, `locations`.`is_start`, `locations`.`is_end`, `code_lookup`.`code` FROM `locations` LEFT OUTER JOIN `code_lookup` ON `locations`.`game_id` = `code_lookup`.`game_id` AND `locations`.`location_id` = `code_lookup`.`location_id` WHERE `locations`.`game_id` = @next_game ORDER BY `locations`.`location_id` ASC;
