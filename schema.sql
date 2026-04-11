
CREATE DATABASE IF NOT EXISTS `personal_student_tracker_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `personal_student_tracker_db`;


CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expire` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `semesters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `semester_name` varchar(50) NOT NULL,
  `user_id` int NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_semester_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_name` varchar(100) NOT NULL,
  `credits` int DEFAULT '0',
  `score` decimal(5,2) DEFAULT '0.00',
  `semester_id` int NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_course_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `criterions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `criterion_name` varchar(255) NOT NULL,
  `max_score` int DEFAULT '0',
  `type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE `evidences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content` longblob,
  `score_value` decimal(5,2) DEFAULT '0.00',
  `event_date` date DEFAULT NULL,
  `criterion_id` int NOT NULL,
  `user_id` int NOT NULL,
  `semester_id` int NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_evidence_criterion` FOREIGN KEY (`criterion_id`) REFERENCES `criterions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evidence_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evidence_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

