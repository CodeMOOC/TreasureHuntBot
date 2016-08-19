-- phpMyAdmin SQL Dump
-- version 4.6.0
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 19, 2016 at 07:29 PM
-- Server version: 5.5.46-0+deb8u1
-- PHP Version: 5.6.17-0+deb8u1

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
  `game_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `assigned_on` datetime NOT NULL,
  `reached_on` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assigned_riddles`
--

CREATE TABLE `assigned_riddles` (
  `game_id` int(11) NOT NULL,
  `riddle_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `last_answer` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `leader_telegram_id` int(11) NOT NULL,
  `leader_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `registration` datetime NOT NULL,
  `photo_path` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `game_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `lat` float NOT NULL,
  `lng` float NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `timestamp` datetime NOT NULL,
  `tag` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `severity` enum('debug','info','warning','error') COLLATE utf8_unicode_ci NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `telegram_chat_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riddles`
--

CREATE TABLE `riddles` (
  `game_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `image_path` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `text` text COLLATE utf8_unicode_ci NOT NULL,
  `solution` text COLLATE utf8_unicode_ci NOT NULL,
  `hint` text COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `game_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `participants_count` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `state` enum('new','reg_verified','reg_name','reg_confirmed','reg_number','reg_ready','location_assigned','location_reached','selfie_taken','last_riddle_assigned') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'new',
  `assigned_riddle_id` int(11) DEFAULT NULL,
  `track_id` int(11) NOT NULL,
  `track_index` tinyint(3) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tracks`
--

CREATE TABLE `tracks` (
  `game_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `order_index` tinyint(3) UNSIGNED NOT NULL
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
  ADD PRIMARY KEY (`game_id`,`riddle_id`,`group_id`),
  ADD KEY `assriddles_group_constraint` (`group_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`game_id`,`id`);

--
-- Indexes for table `log`
--
ALTER TABLE `log`
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `tag` (`tag`),
  ADD KEY `telegram_chat_id` (`telegram_chat_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `riddles`
--
ALTER TABLE `riddles`
  ADD PRIMARY KEY (`game_id`,`id`);

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`game_id`,`group_id`),
  ADD KEY `status_group_constraint` (`group_id`);

--
-- Indexes for table `tracks`
--
ALTER TABLE `tracks`
  ADD PRIMARY KEY (`game_id`,`id`,`location_id`,`order_index`),
  ADD KEY `track_location_constraint` (`game_id`,`location_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assigned_locations`
--
ALTER TABLE `assigned_locations`
  ADD CONSTRAINT `assloc_group_constraint` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  ADD CONSTRAINT `assloc_location_constraint` FOREIGN KEY (`game_id`,`location_id`) REFERENCES `locations` (`game_id`, `id`);

--
-- Constraints for table `assigned_riddles`
--
ALTER TABLE `assigned_riddles`
  ADD CONSTRAINT `assriddles_group_constraint` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  ADD CONSTRAINT `assriddles_riddle_constraint` FOREIGN KEY (`game_id`,`riddle_id`) REFERENCES `riddles` (`game_id`, `id`);

--
-- Constraints for table `log`
--
ALTER TABLE `log`
  ADD CONSTRAINT `log_group_constraint` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Constraints for table `status`
--
ALTER TABLE `status`
  ADD CONSTRAINT `status_group_constraint` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`);

--
-- Constraints for table `tracks`
--
ALTER TABLE `tracks`
  ADD CONSTRAINT `track_location_constraint` FOREIGN KEY (`game_id`,`location_id`) REFERENCES `locations` (`game_id`, `id`);
