# Extract vote count per type and rating
SELECT `name`, count(*) FROM `questionnaire` WHERE `rating` IS NOT NULL GROUP BY `name`, `rating` ORDER BY `name`, `rating`;
