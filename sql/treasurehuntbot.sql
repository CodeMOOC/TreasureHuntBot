-- phpMyAdmin SQL Dump
-- version 4.6.4
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 01, 2017 at 08:09 PM
-- Server version: 5.5.53-0+deb8u1
-- PHP Version: 5.6.27-0+deb8u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `treasurehuntbot`
--

-- --------------------------------------------------------

--
-- Table structure for table `assigned_locations`
--

CREATE TABLE `assigned_locations` (
  `game_id` int(10) UNSIGNED NOT NULL,
  `location_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `assigned_on` datetime NOT NULL,
  `reached_on` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assigned_riddles`
--

CREATE TABLE `assigned_riddles` (
  `event_id` int(10) UNSIGNED NOT NULL,
  `riddle_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `assigned_on` datetime NOT NULL,
  `last_answer_on` datetime DEFAULT NULL,
  `solved_on` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  `registration_code` char(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `victory_code` char(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `logo_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `registered_on` datetime NOT NULL,
  `min_num_locations` tinyint(3) UNSIGNED NOT NULL DEFAULT '10' COMMENT 'Minimum number of locations',
  `max_num_locations` tinyint(3) UNSIGNED NOT NULL DEFAULT '30' COMMENT 'Maximum number of locations',
  `organizer_id` int(10) UNSIGNED NOT NULL,
  `min_avg_distance` float DEFAULT NULL COMMENT 'Minimum average distance between locations (in kms)',
  `telegram_channel` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `game_id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED DEFAULT NULL,
  `state` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  `registration_code` char(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_lat` float DEFAULT NULL,
  `location_lng` float DEFAULT NULL,
  `organizer_id` int(10) UNSIGNED NOT NULL,
  `organizer_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `telegram_channel` varchar(80) COLLATE utf8_unicode_ci DEFAULT NULL,
  `telegram_channel_censor_photo` bit(1) NOT NULL DEFAULT b'0' COMMENT 'Set if pictures should not be sent on the channel',
  `registered_on` datetime NOT NULL,
  `tmp_location_lat` float DEFAULT NULL,
  `tmp_location_lng` float DEFAULT NULL,
  `tmp_location_image_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tmp_location_description` text COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_location_clusters`
--

CREATE TABLE `game_location_clusters` (
  `game_id` int(10) UNSIGNED NOT NULL,
  `cluster_id` tinyint(3) UNSIGNED NOT NULL,
  `num_locations` tinyint(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Number of locations to reach in this cluster',
  `description` text COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `game_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  `participants_count` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `photo_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Path to the group''s photo',
  `registered_on` datetime NOT NULL COMMENT 'Original generation timestamp',
  `last_state_change` datetime NOT NULL COMMENT 'Timestamp of last state change'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hints`
--

CREATE TABLE `hints` (
  `event_id` int(10) UNSIGNED NOT NULL,
  `order_index` tinyint(3) UNSIGNED NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `identities`
--

CREATE TABLE `identities` (
  `id` int(10) UNSIGNED NOT NULL COMMENT 'Internal ID',
  `telegram_id` int(11) NOT NULL,
  `first_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `first_seen_on` datetime NOT NULL,
  `last_access` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `game_id` int(10) UNSIGNED NOT NULL,
  `location_id` int(10) UNSIGNED NOT NULL,
  `cluster_id` tinyint(3) UNSIGNED NOT NULL,
  `code` char(8) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'Unique identifying code',
  `internal_note` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lat` float NOT NULL,
  `lng` float NOT NULL,
  `image_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `is_start` bit(1) NOT NULL DEFAULT b'0',
  `is_end` bit(1) NOT NULL DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `log_id` int(10) UNSIGNED NOT NULL,
  `severity` tinyint(3) UNSIGNED NOT NULL,
  `tag` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` datetime NOT NULL,
  `identity_id` int(10) UNSIGNED DEFAULT NULL,
  `game_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riddles`
--

CREATE TABLE `riddles` (
  `event_id` int(10) UNSIGNED NOT NULL,
  `riddle_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `text` text COLLATE utf8_unicode_ci NOT NULL,
  `solution` varchar(60) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assigned_locations`
--
ALTER TABLE `assigned_locations`
  ADD PRIMARY KEY (`game_id`,`location_id`,`group_id`),
  ADD KEY `assloc_group_constraint` (`group_id`);

--
-- Indexes for table `assigned_riddles`
--
ALTER TABLE `assigned_riddles`
  ADD PRIMARY KEY (`event_id`,`riddle_id`,`group_id`),
  ADD KEY `assriddles_group_constraint` (`group_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD UNIQUE KEY `registration_code_index` (`registration_code`),
  ADD UNIQUE KEY `victory_code_index` (`victory_code`),
  ADD KEY `event_organizer_index` (`organizer_id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`game_id`),
  ADD UNIQUE KEY `registration_code_index` (`registration_code`),
  ADD KEY `game_event_index` (`event_id`),
  ADD KEY `game_organizer_index` (`organizer_id`);

--
-- Indexes for table `game_location_clusters`
--
ALTER TABLE `game_location_clusters`
  ADD PRIMARY KEY (`game_id`,`cluster_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`game_id`,`group_id`),
  ADD KEY `status_group_constraint` (`group_id`);

--
-- Indexes for table `hints`
--
ALTER TABLE `hints`
  ADD PRIMARY KEY (`event_id`,`order_index`);

--
-- Indexes for table `identities`
--
ALTER TABLE `identities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `telegram_id` (`telegram_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`game_id`,`location_id`),
  ADD UNIQUE KEY `location_code_index` (`game_id`,`code`) USING BTREE,
  ADD KEY `cluster_id` (`cluster_id`),
  ADD KEY `location_cluster_constraint` (`game_id`,`cluster_id`);

--
-- Indexes for table `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `tag` (`tag`),
  ADD KEY `log_context_index` (`identity_id`,`game_id`),
  ADD KEY `log_game_constraint` (`game_id`);

--
-- Indexes for table `riddles`
--
ALTER TABLE `riddles`
  ADD PRIMARY KEY (`event_id`,`riddle_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `game_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `identities`
--
ALTER TABLE `identities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Internal ID';
--
-- AUTO_INCREMENT for table `log`
--
ALTER TABLE `log`
  MODIFY `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `assigned_locations`
--
ALTER TABLE `assigned_locations`
  ADD CONSTRAINT `assloc_group_constraint` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assloc_location_constraint` FOREIGN KEY (`game_id`,`location_id`) REFERENCES `locations` (`game_id`, `location_id`);

--
-- Constraints for table `assigned_riddles`
--
ALTER TABLE `assigned_riddles`
  ADD CONSTRAINT `assriddles_group_constraint` FOREIGN KEY (`group_id`) REFERENCES `groups` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assriddles_riddle_constraint` FOREIGN KEY (`event_id`,`riddle_id`) REFERENCES `riddles` (`event_id`, `riddle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assriddle_event_constraint` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_identity_constraint` FOREIGN KEY (`organizer_id`) REFERENCES `identities` (`id`);

--
-- Constraints for table `games`
--
ALTER TABLE `games`
  ADD CONSTRAINT `game_event_constraint` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_organizer_constraint` FOREIGN KEY (`organizer_id`) REFERENCES `identities` (`id`);

--
-- Constraints for table `game_location_clusters`
--
ALTER TABLE `game_location_clusters`
  ADD CONSTRAINT `game_location_clusters_game_constraint` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_identity_constraint` FOREIGN KEY (`group_id`) REFERENCES `identities` (`id`);

--
-- Constraints for table `hints`
--
ALTER TABLE `hints`
  ADD CONSTRAINT `hint_event_constraint` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;

--
-- Constraints for table `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `location_cluster_constraint` FOREIGN KEY (`game_id`,`cluster_id`) REFERENCES `game_location_clusters` (`game_id`, `cluster_id`),
  ADD CONSTRAINT `location_game_constraint` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `log`
--
ALTER TABLE `log`
  ADD CONSTRAINT `log_game_constraint` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `log_identity_constraint` FOREIGN KEY (`identity_id`) REFERENCES `identities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `riddles`
--
ALTER TABLE `riddles`
  ADD CONSTRAINT `riddle_event_constraint` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE;
