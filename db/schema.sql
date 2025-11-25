-- UniNotes Database Schema
-- Mobile-first web app for university note sharing
-- Created: November 15, 2025

-- ============================================
-- CORE ENTITIES
-- ============================================

CREATE TABLE IF NOT EXISTS `person` (
	`id` INT NOT NULL AUTO_INCREMENT UNIQUE,
	`name` VARCHAR(100) NOT NULL,
	`surname` VARCHAR(100) NOT NULL,
	`email` VARCHAR(255) NOT NULL UNIQUE,
	`profile_picture` VARCHAR(255),
	PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user` (
	`person_id` INT NOT NULL UNIQUE,
	`password` VARCHAR(255) NOT NULL,
	`role` ENUM('user', 'admin') DEFAULT 'user',
	`programme` VARCHAR(255) NULL,
	`bio` TEXT NULL,
	`programme_id` INT NULL,
	`last_login` TIMESTAMP NULL,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`deleted_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY(`person_id`),
	FOREIGN KEY(`person_id`) REFERENCES `person`(`id`) ON UPDATE NO ACTION ON DELETE RESTRICT,
	FOREIGN KEY(`programme_id`) REFERENCES `programme`(`id`) ON UPDATE NO ACTION ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `teacher` (
	`person_id` INT NOT NULL UNIQUE,
	`department` VARCHAR(255),
	`unibo_site` VARCHAR(255),
	`phone_number` VARCHAR(20),
	`personal_site` VARCHAR(255),
	PRIMARY KEY(`person_id`),
	FOREIGN KEY(`person_id`) REFERENCES `person`(`id`) ON UPDATE NO ACTION ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `programme` (
	`id` INT NOT NULL AUTO_INCREMENT UNIQUE,
	`name` VARCHAR(255) NOT NULL UNIQUE,
	PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- COURSE MANAGEMENT
-- ============================================

CREATE TABLE IF NOT EXISTS `course` (
	`id` INT NOT NULL AUTO_INCREMENT UNIQUE,
	`name` VARCHAR(255) NOT NULL,
	`description` TEXT,
	`created_by` INT,
	`programme_id` INT NULL,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(`id`),
	UNIQUE KEY `unique_course_name` (`name`),
	FOREIGN KEY(`created_by`) REFERENCES `user`(`person_id`) ON UPDATE NO ACTION ON DELETE SET NULL,
	FOREIGN KEY(`programme_id`) REFERENCES `programme`(`id`) ON UPDATE NO ACTION ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `course_offering` (
	`id` INT NOT NULL AUTO_INCREMENT UNIQUE,
	`year` YEAR NOT NULL,
	`semester` ENUM('1', '2') NOT NULL DEFAULT '1',
	`course_id` INT NOT NULL,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(`id`),
	FOREIGN KEY(`course_id`) REFERENCES `course`(`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	UNIQUE KEY `unique_course_year_semester` (`course_id`, `year`, `semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- RELAZIONE MANY-TO-MANY: course_offering <-> teacher
-- Un'offerta può essere tenuta da più docenti; un docente può tenere più offerte
-- ============================================
CREATE TABLE IF NOT EXISTS `course_offering_teacher` (
    `offering_id` INT NOT NULL,
    `teacher_id` INT NOT NULL,
    PRIMARY KEY (`offering_id`, `teacher_id`),
    FOREIGN KEY (`offering_id`) REFERENCES `course_offering`(`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
    FOREIGN KEY (`teacher_id`) REFERENCES `teacher`(`person_id`) ON UPDATE NO ACTION ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- USER FOLLOWING (course_offering)
-- ============================================

CREATE TABLE IF NOT EXISTS `course_offering_follow` (
    `offering_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    PRIMARY KEY (`offering_id`, `user_id`),
    FOREIGN KEY (`offering_id`) REFERENCES `course_offering`(`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	FOREIGN KEY (`user_id`) REFERENCES `user`(`person_id`) ON UPDATE NO ACTION ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- NOTES STRUCTURE
-- ============================================

CREATE TABLE IF NOT EXISTS `topic` (
	`id` INT NOT NULL AUTO_INCREMENT UNIQUE,
	`offering_id` INT NOT NULL,
	`name` VARCHAR(255) NOT NULL,
	`description` TEXT,
	`order_index` INT DEFAULT 0,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(`id`),
	FOREIGN KEY(`offering_id`) REFERENCES `course_offering`(`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `note` (
	`id` INT NOT NULL AUTO_INCREMENT UNIQUE,
	`owner_id` INT NULL,
	`topic_id` INT NOT NULL,
	`title` VARCHAR(255) NOT NULL,
	`content` LONGTEXT,
	`content_rendered` LONGTEXT,
	`status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
	`published_at` TIMESTAMP NULL DEFAULT NULL,
	`vote_count` INT DEFAULT 0,
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`deleted_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY(`id`),
	FOREIGN KEY(`owner_id`) REFERENCES `user`(`person_id`) ON UPDATE NO ACTION ON DELETE SET NULL,
	FOREIGN KEY(`topic_id`) REFERENCES `topic`(`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- CORREZIONI / SUGGERIMENTI (Error reports / corrections)
-- Permette agli utenti di segnalare un punto errato indicando file/linea/pezzo
-- ============================================

CREATE TABLE IF NOT EXISTS `correction` (
	`id` INT NOT NULL AUTO_INCREMENT UNIQUE,
	`reported_by` INT NULL,
	`note_id` INT NOT NULL COMMENT 'Nota a cui si riferisce la segnalazione',
	`file_index` INT NOT NULL DEFAULT 0 COMMENT 'Indice 1-based del file nella nota (0 = nessun file specificato)',
	`line_number` INT NULL COMMENT 'Numero di linea, se applicabile',
	`snippet` TEXT NULL COMMENT 'Porzione di testo/pezzo incriminato (se utile)',
	`message` TEXT NOT NULL COMMENT 'Descrizione di cosa c\'e\' di sbagliato',
	`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	`resolved` BOOLEAN NOT NULL DEFAULT FALSE,
	`resolved_at` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY(`id`),
	FOREIGN KEY(`reported_by`) REFERENCES `user`(`person_id`) ON UPDATE NO ACTION ON DELETE SET NULL,
	FOREIGN KEY(`note_id`) REFERENCES `note`(`id`) ON UPDATE NO ACTION ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- FILE MANAGEMENT
-- ============================================

CREATE TABLE IF NOT EXISTS `file` (
	`id` INT NOT NULL AUTO_INCREMENT UNIQUE,
	`note_id` INT NOT NULL,
	`filename` VARCHAR(255) NOT NULL,
	`storage_path` VARCHAR(500) NOT NULL,
	`file_type` VARCHAR(50),
	`file_size` BIGINT,
	`mime_type` VARCHAR(100),
	`uploaded_by` INT NULL,
	`uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(`id`),
	FOREIGN KEY(`note_id`) REFERENCES `note`(`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	FOREIGN KEY(`uploaded_by`) REFERENCES `user`(`person_id`) ON UPDATE NO ACTION ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ENGAGEMENT SYSTEM
-- ============================================

CREATE TABLE IF NOT EXISTS `vote` (
	`note_id` INT NOT NULL,
	`user_id` INT NOT NULL,
	`vote` BOOLEAN NOT NULL COMMENT 'TRUE = upvote, FALSE = downvote',
	PRIMARY KEY(`note_id`, `user_id`),
	FOREIGN KEY(`note_id`) REFERENCES `note`(`id`) ON UPDATE NO ACTION ON DELETE CASCADE,
	FOREIGN KEY(`user_id`) REFERENCES `user`(`person_id`) ON UPDATE NO ACTION ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX `idx_note_owner` ON `note`(`owner_id`);
CREATE INDEX `idx_note_topic` ON `note`(`topic_id`);
CREATE INDEX `idx_note_created` ON `note`(`created_at`);
CREATE INDEX `idx_file_note` ON `file`(`note_id`);
CREATE INDEX `idx_vote_user` ON `vote`(`user_id`);
CREATE INDEX `idx_topic_offering` ON `topic`(`offering_id`);
CREATE INDEX `idx_course_offering_year` ON `course_offering`(`year`);
CREATE INDEX `idx_offering_follow_user` ON `course_offering_follow`(`user_id`);
CREATE FULLTEXT INDEX `idx_note_fulltext` ON `note`(`title`, `content`);
CREATE INDEX `idx_note_vote_count` ON `note`(`vote_count`);
CREATE INDEX `idx_file_uploaded_by` ON `file`(`uploaded_by`);
CREATE INDEX `idx_user_programme` ON `user`(`programme_id`);
CREATE INDEX `idx_course_programme` ON `course`(`programme_id`);
