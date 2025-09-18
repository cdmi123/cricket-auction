-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 19, 2025 at 03:55 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cricket_auction`
--

-- --------------------------------------------------------

--
-- Table structure for table `auction`
--

CREATE TABLE `auction` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('Upcoming','Live','Completed','Paused') DEFAULT 'Upcoming',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `current_player_id` int(11) DEFAULT NULL,
  `current_player_end_time` datetime DEFAULT NULL,
  `min_bid_increment` decimal(10,2) NOT NULL DEFAULT 10000.00,
  `timer_seconds` int(11) NOT NULL DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auction`
--

INSERT INTO `auction` (`id`, `name`, `status`, `start_time`, `end_time`, `current_player_id`, `current_player_end_time`, `min_bid_increment`, `timer_seconds`, `created_at`) VALUES
(1, 'CREATIVE', 'Completed', '2025-08-18 12:43:00', NULL, 2, '2025-08-18 12:43:30', 10000.00, 30, '2025-08-18 07:11:46'),
(2, 'CDMI', 'Completed', '2025-08-18 16:00:00', NULL, 3, '2025-08-18 16:01:05', 10000.00, 30, '2025-08-18 10:29:28');

-- --------------------------------------------------------

--
-- Table structure for table `auto_bids`
--

CREATE TABLE `auto_bids` (
  `id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `max_bid` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bids`
--

CREATE TABLE `bids` (
  `id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `bid_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bids`
--

INSERT INTO `bids` (`id`, `player_id`, `team_id`, `amount`, `bid_time`) VALUES
(1, 3, 1, 20000.00, '2025-08-18 10:43:48'),
(2, 3, 1, 25000.00, '2025-08-18 10:46:26'),
(3, 3, 1, 50000.00, '2025-08-18 10:46:49'),
(4, 3, 2, 150000.00, '2025-08-18 10:47:59'),
(5, 3, 2, 200000.00, '2025-08-18 10:56:19');

-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `country` varchar(50) NOT NULL,
  `role` enum('Batsman','Bowler','All-rounder','Wicket-keeper') NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `specialty` varchar(255) DEFAULT NULL,
  `statistics` text DEFAULT NULL,
  `status` enum('Available','Sold','Unsold') DEFAULT 'Available',
  `sold_to` int(11) DEFAULT NULL,
  `sold_price` decimal(10,2) DEFAULT NULL,
  `age` int(11) NOT NULL DEFAULT 22,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `players`
--

INSERT INTO `players` (`id`, `name`, `country`, `role`, `base_price`, `image`, `specialty`, `statistics`, `status`, `sold_to`, `sold_price`, `age`, `created_at`) VALUES
(2, 'RAVI JASOLIYA', 'INDIA', 'All-rounder', 15000.00, '1755500852_user2.jpg', 'ALL', '{\"matches\": 50, \"runs\": 1500, \"wickets\": 25, \"average\": 35.5}', 'Unsold', NULL, NULL, 22, '2025-08-18 07:07:32'),
(3, 'JAY KOLADIYA', 'INDIA', 'Batsman', 20000.00, '1755501031_user4.jpg', 'ALL', '{\"matches\": 50, \"runs\": 1500, \"wickets\": 25, \"average\": 35.5}', 'Sold', 2, 200000.00, 22, '2025-08-18 07:10:31');

-- --------------------------------------------------------

--
-- Table structure for table `player_schedule`
--

CREATE TABLE `player_schedule` (
  `id` int(11) NOT NULL,
  `auction_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `player_schedule`
--

INSERT INTO `player_schedule` (`id`, `auction_id`, `player_id`, `start_time`, `end_time`, `created_at`) VALUES
(1, 1, 2, '2025-08-18 12:43:00', '2025-08-18 12:43:30', '2025-08-18 07:11:46'),
(2, 1, 3, '2025-08-18 12:43:35', '2025-08-18 12:44:05', '2025-08-18 07:11:46'),
(3, 2, 2, '2025-08-18 16:00:00', '2025-08-18 16:00:30', '2025-08-18 10:29:28'),
(4, 2, 3, '2025-08-18 16:00:35', '2025-08-18 16:01:05', '2025-08-18 10:29:28');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `budget` decimal(10,2) NOT NULL,
  `remaining_budget` decimal(10,2) NOT NULL,
  `max_players` int(11) NOT NULL DEFAULT 25,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` text NOT NULL DEFAULT 'team'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `name`, `logo`, `budget`, `remaining_budget`, `max_players`, `created_at`, `role`) VALUES
(1, 'india', NULL, 250000.00, 250000.00, 15, '2025-08-18 05:25:41', 'team'),
(2, 'BLACK BIRDS', NULL, 850000.00, 650000.00, 15, '2025-08-18 07:08:29', 'team');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','team') NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `team_id`, `created_at`) VALUES
(1, 'admin', '$2y$10$j6JvSqMoScDth1TiICKE6eJL0QWDGG8bPWT977MEfT.qqvYTOpiCy', 'admin', 1, '2025-08-18 03:36:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auction`
--
ALTER TABLE `auction`
  ADD PRIMARY KEY (`id`),
  ADD KEY `current_player_id` (`current_player_id`);

--
-- Indexes for table `auto_bids`
--
ALTER TABLE `auto_bids`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `bids`
--
ALTER TABLE `bids`
  ADD PRIMARY KEY (`id`),
  ADD KEY `player_id` (`player_id`),
  ADD KEY `team_id` (`team_id`);

--
-- Indexes for table `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sold_to` (`sold_to`);

--
-- Indexes for table `player_schedule`
--
ALTER TABLE `player_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auction_id` (`auction_id`),
  ADD KEY `player_id` (`player_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `team_id` (`team_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auction`
--
ALTER TABLE `auction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `auto_bids`
--
ALTER TABLE `auto_bids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bids`
--
ALTER TABLE `bids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `players`
--
ALTER TABLE `players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `player_schedule`
--
ALTER TABLE `player_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auction`
--
ALTER TABLE `auction`
  ADD CONSTRAINT `auction_ibfk_1` FOREIGN KEY (`current_player_id`) REFERENCES `players` (`id`);

--
-- Constraints for table `auto_bids`
--
ALTER TABLE `auto_bids`
  ADD CONSTRAINT `auto_bids_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`),
  ADD CONSTRAINT `auto_bids_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`);

--
-- Constraints for table `bids`
--
ALTER TABLE `bids`
  ADD CONSTRAINT `bids_ibfk_1` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`),
  ADD CONSTRAINT `bids_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`);

--
-- Constraints for table `players`
--
ALTER TABLE `players`
  ADD CONSTRAINT `players_ibfk_1` FOREIGN KEY (`sold_to`) REFERENCES `teams` (`id`);

--
-- Constraints for table `player_schedule`
--
ALTER TABLE `player_schedule`
  ADD CONSTRAINT `player_schedule_ibfk_1` FOREIGN KEY (`auction_id`) REFERENCES `auction` (`id`),
  ADD CONSTRAINT `player_schedule_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
