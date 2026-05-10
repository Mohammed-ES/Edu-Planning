-- ============================================================
-- Edu-Planning Database Schema
-- Character set: utf8mb4 (full Unicode support)
-- ============================================================

DROP DATABASE IF EXISTS edu_planning;
CREATE DATABASE edu_planning CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE edu_planning;

-- ============================================================
-- USERS
-- ============================================================
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MODULES
-- ============================================================
CREATE TABLE modules (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT          NOT NULL,
    module_name         VARCHAR(150) NOT NULL,
    teacher             VARCHAR(100),
    difficulty          ENUM('EASY','MEDIUM','HARD')  NOT NULL,
    career_importance   ENUM('LOW','MEDIUM','HIGH')   NOT NULL,
    progress            INT          DEFAULT 0 CHECK (progress BETWEEN 0 AND 100),
    understanding_level ENUM('LOW','MEDIUM','HIGH')   NOT NULL,
    exam_date           DATE         NOT NULL,
    created_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index: common query pattern — all modules for a user, sorted by exam_date
CREATE INDEX idx_modules_user_exam ON modules (user_id, exam_date ASC);

-- ============================================================
-- STUDY PLANS
-- ============================================================
CREATE TABLE study_plans (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT  NOT NULL,
    generated_plan TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index: recent plans by user (most common query pattern)
CREATE INDEX idx_study_plans_user_date ON study_plans (user_id, created_at DESC);

-- ============================================================
-- SEED DATA (for development / demo only)
-- Remove these INSERT statements before production deploy.
-- Password: "password123" (bcrypt, cost 10)
-- ============================================================
INSERT INTO users (id, name, email, password)
VALUES
(1, 'Mohammed', 'mohammed@exaplanner.com', '$2y$10$DEtn8wX8X3JlSoWS/D9gi.DufLOP6UYy06sKrGW3ELaNl2mBUPQ4C');

INSERT INTO modules (user_id, module_name, teacher, difficulty, career_importance, progress, understanding_level, exam_date)
VALUES
(1, 'WEB TECHNIQUES', 'PROF. HOURRI', 'HARD',   'HIGH',   40, 'MEDIUM', '2026-06-10'),
(1, 'ALGORITHMS',     'AHMED',        'MEDIUM',  'HIGH',   55, 'MEDIUM', '2026-06-15'),
(1, 'DATABASE',       'HAMZANE',      'MEDIUM',  'MEDIUM', 60, 'MEDIUM', '2026-06-18');
