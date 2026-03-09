-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for survey_form
CREATE DATABASE IF NOT EXISTS `survey_form` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `survey_form`;

-- Dumping structure for table survey_form.answers
CREATE TABLE IF NOT EXISTS `answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `response_id` int DEFAULT NULL,
  `question_id` int DEFAULT NULL,
  `answer` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table survey_form.answers: ~0 rows (approximately)
REPLACE INTO `answers` (`id`, `response_id`, `question_id`, `answer`) VALUES
	(7, 3, 4, 'Oo'),
	(8, 3, 5, 'Lapis'),
	(9, 3, 6, 'Matcha'),
	(10, 4, 4, 'Hindi'),
	(11, 4, 5, 'Papel'),
	(12, 4, 6, 'Pistachio');

-- Dumping structure for table survey_form.options
CREATE TABLE IF NOT EXISTS `options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question_id` int DEFAULT NULL,
  `option_text` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table survey_form.options: ~0 rows (approximately)
REPLACE INTO `options` (`id`, `question_id`, `option_text`) VALUES
	(9, 5, 'Papel'),
	(10, 5, 'Lapis'),
	(11, 6, 'Matcha'),
	(12, 6, 'Pistachio');

-- Dumping structure for table survey_form.questions
CREATE TABLE IF NOT EXISTS `questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `survey_id` int DEFAULT NULL,
  `question` text,
  `type` varchar(50) DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table survey_form.questions: ~0 rows (approximately)
REPLACE INTO `questions` (`id`, `survey_id`, `question`, `type`, `required`) VALUES
	(4, 2, 'Masaya ka ba?', 'text', 1),
	(5, 2, 'Magbigay ng bagay na ginagamit sa eskwelahan', 'radio', 1),
	(6, 2, 'Magbigay ng pagkain na kulay berde', 'checkbox', 1);

-- Dumping structure for table survey_form.responses
CREATE TABLE IF NOT EXISTS `responses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `survey_id` int DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table survey_form.responses: ~0 rows (approximately)
REPLACE INTO `responses` (`id`, `survey_id`, `submitted_at`) VALUES
	(3, 2, '2026-03-08 09:58:36'),
	(4, 2, '2026-03-08 09:58:50');

-- Dumping structure for table survey_form.surveys
CREATE TABLE IF NOT EXISTS `surveys` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `token` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table survey_form.surveys: ~0 rows (approximately)
REPLACE INTO `surveys` (`id`, `title`, `description`, `token`, `password`, `created_at`) VALUES
	(2, 'FAMILY FEUD', 'entertainment', '0d32965467', '$2y$10$Qa8kkUSAtMckJ/KSgbQSQ.Jer9yy2X1oJ4CGd/hZ3cySo3YgE7FV.', '2026-03-08 09:57:12');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
