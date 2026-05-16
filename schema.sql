-- ============================================================
--  CS382 Smart School - Combined Internal System
--  MySQL schema + seed data (9 tables)
--  Stack: PHP (OOP/PDO) + MySQL. English-only, LTR.
--
--  Run order:
--    1) Import this file (creates DB `cs382project`, tables, seed).
--    2) Open  setup.php  once in the browser to seed the
--       admin/staff users with bcrypt-hashed passwords.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `cs382project`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cs382project`;

-- Idempotent re-import: drop in FK-safe order.
DROP TABLE IF EXISTS `grade_supervisors`;
DROP TABLE IF EXISTS `week_snapshots`;
DROP TABLE IF EXISTS `internal_messages`;
DROP TABLE IF EXISTS `feedback_ratings`;
DROP TABLE IF EXISTS `attendance_monthly`;
DROP TABLE IF EXISTS `stats`;
DROP TABLE IF EXISTS `stars`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `classes`;
DROP TABLE IF EXISTS `users`;

-- ------------------------------------------------------------
-- 1) users
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('admin','supervisor') NOT NULL DEFAULT 'supervisor',
  `display_name`  VARCHAR(100) NOT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Seed users are created by setup.php (bcrypt via password_hash()).

-- ------------------------------------------------------------
-- 2) classes  (unified: discipline + academic grouping)
-- ------------------------------------------------------------
CREATE TABLE `classes` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`              VARCHAR(20)  NOT NULL,
  `grade`             VARCHAR(20)  NOT NULL,
  `section`           TINYINT UNSIGNED NOT NULL,
  `name`              VARCHAR(50)  NOT NULL,
  `order_score`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `cleanliness_score` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `behavior_score`    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `discipline_leader` VARCHAR(100) NOT NULL DEFAULT '',
  `supervisor`        VARCHAR(100) NOT NULL DEFAULT '',
  `motivation_notes`  TEXT         NULL,
  `semester`          VARCHAR(20)  NOT NULL DEFAULT 'Semester 1',
  `badge`             VARCHAR(30)  NOT NULL DEFAULT '',
  `badge_color`       VARCHAR(20)  NOT NULL DEFAULT '#D4A23A',
  `sort_order`        INT          NOT NULL DEFAULT 0,
  `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_classes_code` (`code`),
  CONSTRAINT `chk_order`       CHECK (`order_score`       BETWEEN 0 AND 10),
  CONSTRAINT `chk_cleanliness` CHECK (`cleanliness_score` BETWEEN 0 AND 10),
  CONSTRAINT `chk_behavior`    CHECK (`behavior_score`    BETWEEN 0 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12 classes: 3 grades x 4 sections. Generic placeholder names.
INSERT INTO `classes`
  (`code`,`grade`,`section`,`name`,`supervisor`,`discipline_leader`,`semester`,`badge`,`badge_color`,`sort_order`) VALUES
  ('g1-1','Grade 1',1,'Class 1-A','Supervisor A','Leader 1-A','Semester 1','New','#5DBF94', 1),
  ('g1-2','Grade 1',2,'Class 1-B','Supervisor A','Leader 1-B','Semester 1','',   '#D4A23A', 2),
  ('g1-3','Grade 1',3,'Class 1-C','Supervisor B','Leader 1-C','Semester 1','',   '#D4A23A', 3),
  ('g1-4','Grade 1',4,'Class 1-D','Supervisor B','Leader 1-D','Semester 1','',   '#D4A23A', 4),
  ('g2-1','Grade 2',1,'Class 2-A','Supervisor C','Leader 2-A','Semester 1','',   '#D4A23A', 5),
  ('g2-2','Grade 2',2,'Class 2-B','Supervisor C','Leader 2-B','Semester 1','',   '#D4A23A', 6),
  ('g2-3','Grade 2',3,'Class 2-C','Supervisor C','Leader 2-C','Semester 1','',   '#D4A23A', 7),
  ('g2-4','Grade 2',4,'Class 2-D','Supervisor C','Leader 2-D','Semester 1','',   '#D4A23A', 8),
  ('g3-1','Grade 3',1,'Class 3-A','Supervisor D','Leader 3-A','Semester 2','Top','#2E9E6B', 9),
  ('g3-2','Grade 3',2,'Class 3-B','Supervisor D','Leader 3-B','Semester 2','',   '#D4A23A',10),
  ('g3-3','Grade 3',3,'Class 3-C','Supervisor D','Leader 3-C','Semester 2','',   '#D4A23A',11),
  ('g3-4','Grade 3',4,'Class 3-D','Supervisor D','Leader 3-D','Semester 2','',   '#D4A23A',12);

-- ------------------------------------------------------------
-- 3) subjects  (academic grade-band distribution per class)
-- ------------------------------------------------------------
CREATE TABLE `subjects` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_id`   INT UNSIGNED NOT NULL,
  `name`       VARCHAR(60)  NOT NULL,
  `teacher`    VARCHAR(100) NOT NULL DEFAULT '',
  `excellent`  INT NOT NULL DEFAULT 0,
  `very_good`  INT NOT NULL DEFAULT 0,
  `good`       INT NOT NULL DEFAULT 0,
  `acceptable` INT NOT NULL DEFAULT 0,
  `fail`       INT NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_subjects_class` (`class_id`),
  CONSTRAINT `fk_subjects_class`
    FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample subjects for the first class of each grade so donut charts render.
INSERT INTO `subjects`
  (`class_id`,`name`,`teacher`,`excellent`,`very_good`,`good`,`acceptable`,`fail`,`sort_order`) VALUES
  (1,'Arabic',      'Teacher A',60,21,13, 6, 0,1),
  (1,'Mathematics', 'Teacher B',64,25, 9, 2, 0,2),
  (1,'English',     'Teacher C',52,17,20,10, 1,3),
  (1,'Science',     'Teacher D',44,29,17,10, 0,4),
  (5,'Arabic',      'Teacher A',75,18, 5, 1, 1,1),
  (5,'Mathematics', 'Teacher B',92, 8, 0, 0, 0,2),
  (9,'Arabic',      'Teacher E',58,24,12, 5, 1,1),
  (9,'Science',     'Teacher D',49,26,18, 6, 1,2);

-- ------------------------------------------------------------
-- 4) stars
-- ------------------------------------------------------------
CREATE TABLE `stars` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_id`        INT UNSIGNED NOT NULL,
  `awarded_by`      VARCHAR(30)  NOT NULL,
  `awarded_by_name` VARCHAR(100) NOT NULL,
  `reason`          VARCHAR(255) NULL,
  `awarded_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stars_class` (`class_id`),
  CONSTRAINT `fk_stars_class`
    FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5) stats  (3 dashboard cards + reserved `_`-prefixed meta)
-- ------------------------------------------------------------
CREATE TABLE `stats` (
  `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stat_key` VARCHAR(50)  NOT NULL,
  `value`    VARCHAR(50)  NOT NULL,
  `label`    VARCHAR(120) NOT NULL,
  `sublabel` VARCHAR(160) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stats_key` (`stat_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stats` (`stat_key`,`value`,`label`,`sublabel`) VALUES
  ('positive_behaviors','2329','Recorded Positive Behaviors','Positive behavior rate 100% - Semester 1'),
  ('parent_visits',     '452', 'Guardian Visits via Noor System','Total visits recorded for the academic year'),
  ('attendance_rate',   '87%', 'Disciplined Attendance Rate','Average monthly attendance across the year'),
  -- reserved system meta (hidden from dashboard cards by the `_` prefix)
  ('_last_reset_week',  '0',   'system','last Sunday-based week the scores were auto-reset'),
  ('_school_name',      'School Dashboard', 'system','placeholder school name'),
  ('_principal_name',   'Admin', 'system','placeholder principal name'),
  ('_vice_principal_name','Deputy','system','placeholder vice-principal name');

-- ------------------------------------------------------------
-- 6) attendance_monthly  (12 Hijri months, English transliteration)
-- ------------------------------------------------------------
CREATE TABLE `attendance_monthly` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `month`      VARCHAR(30) NOT NULL,
  `value`      INT NOT NULL DEFAULT 0,   -- 0 = no data / gap
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_month` (`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `attendance_monthly` (`month`,`value`,`sort_order`) VALUES
  ('Muharram',        0, 1),
  ('Safar',           0, 2),
  ('Rabi al-Awwal',  91, 3),
  ('Rabi al-Thani',  88, 4),
  ('Jumada al-Awwal',93, 5),
  ('Jumada al-Thani',76, 6),
  ('Rajab',          82, 7),
  ('Shaban',         89, 8),
  ('Ramadan',        88, 9),
  ('Shawwal',        90,10),
  ('Dhu al-Qadah',    0,11),
  ('Dhu al-Hijjah',   0,12);

-- ------------------------------------------------------------
-- 7) feedback_ratings  (internal staff platform rating 1-5)
-- ------------------------------------------------------------
CREATE TABLE `feedback_ratings` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `score`                TINYINT UNSIGNED NOT NULL,
  `submitted_by_user_id` INT UNSIGNED NULL,
  `created_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fr_user` (`submitted_by_user_id`),
  CONSTRAINT `fk_fr_user`
    FOREIGN KEY (`submitted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_fr_score` CHECK (`score` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8) internal_messages  (staff contact; <= 10 words, allow-lists)
-- ------------------------------------------------------------
CREATE TABLE `internal_messages` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recipient`            VARCHAR(40) NOT NULL,
  `category`             VARCHAR(40) NOT NULL,
  `message`              VARCHAR(255) NOT NULL,
  `submitted_by_user_id` INT UNSIGNED NULL,
  `created_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_im_user` (`submitted_by_user_id`),
  CONSTRAINT `fk_im_user`
    FOREIGN KEY (`submitted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_im_recipient`
    CHECK (`recipient` IN ('Student Affairs Deputy','Student Counselor')),
  CONSTRAINT `chk_im_category`
    CHECK (`category` IN ('Complaint','Inquiry','Status Report','Consultation Request'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9) week_snapshots  (denormalized JSON copy per saved week)
-- ------------------------------------------------------------
CREATE TABLE `week_snapshots` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `week`          INT NOT NULL,
  `snapshot_date` DATE NOT NULL,
  `saved_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `saved_by_role` VARCHAR(20)  NOT NULL DEFAULT '',
  `saved_by_name` VARCHAR(100) NOT NULL DEFAULT '',
  `classes_json`  JSON NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_snap_week` (`week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 10) grade_supervisors  (admin assigns one supervisor per grade)
-- ------------------------------------------------------------
CREATE TABLE `grade_supervisors` (
  `grade`              VARCHAR(20)  NOT NULL,
  `supervisor_user_id` INT UNSIGNED NULL,
  PRIMARY KEY (`grade`),
  KEY `idx_gs_user` (`supervisor_user_id`),
  CONSTRAINT `fk_gs_user`
    FOREIGN KEY (`supervisor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per distinct grade (supervisor assigned later by admin).
INSERT INTO `grade_supervisors` (`grade`) VALUES
  ('Grade 1'), ('Grade 2'), ('Grade 3');
