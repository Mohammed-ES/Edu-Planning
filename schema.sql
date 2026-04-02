-- =====================================================
-- EDU-PLANNING: Complete Database Schema v2.0
-- Date: March 29, 2026
-- 
-- IMPORTANT: This script DROPS the existing database
-- Make sure you have backups if needed!
-- =====================================================

-- STEP 1: DROP existing database to start fresh
DROP DATABASE IF EXISTS edu_planning;

-- STEP 2: CREATE fresh database with UTF-8
CREATE DATABASE edu_planning CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE edu_planning;

-- =====================================================
-- TABLE 1: USERS (Authentication & Accounts)
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student','admin') DEFAULT 'student',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User accounts. Google OAuth removed - using local sessions only.';

-- =====================================================
-- TABLE 2: MODULES (Academic Subjects/Courses)
-- =====================================================
CREATE TABLE modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_name VARCHAR(150) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Academic modules/subjects owned by students';

-- =====================================================
-- TABLE 3: NOTES (Student Study Notes)
-- =====================================================
-- IMPORTANT: note_value is TEXT (student observations)
-- NOT DECIMAL (grade) - this was key bug in old schema
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    note_value TEXT NOT NULL COMMENT 'Student notes/observations about module difficulty',
    description VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    INDEX idx_module_id (module_id),
    INDEX idx_module_created (module_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Student notes per module. Analyzed by Gemini AI to create revision plans.';

-- =====================================================
-- TABLE 4: REVISION_PLANS (AI-Generated 7-Day Schedules)
-- =====================================================
-- KEY STRUCTURE:
-- plan_data JSON contains:
-- {
--   "planning": [
--     {
--       "jour": 1,
--       "date": "2026-03-30",
--       "sessions": [
--         {
--           "order": 1,
--           "module": "Mathematics",
--           "time_start": "09:00",
--           "duration_minutes": 150,
--           "priorite": "haute|moyenne|basse",
--           "topics": ["topic1", "topic2"],
--           "description": "..."
--         }
--       ]
--     }
--   ]
-- }
CREATE TABLE revision_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_data JSON NOT NULL COMMENT 'Full 7-day revision plan from Gemini AI',
    start_date DATE COMMENT 'Plan start date',
    end_date DATE COMMENT 'Plan end date (usually +7 days)',
    used_note_ids JSON NULL COMMENT 'Array of note IDs referenced: [1,3,5,...]',
    selected_modules JSON NULL COMMENT 'Array of module names: [Math, Physics,...]',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_user_date (user_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Generated revision plans. One per student per generation.';

-- =====================================================
-- TABLE 5: AI_RECOMMENDATIONS (Gemini Responses)
-- =====================================================
CREATE TABLE ai_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recommendation TEXT NOT NULL COMMENT 'JSON recommendation from Gemini API',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Raw Gemini API responses for audit trail';

-- =====================================================
-- TABLE 6: LOGS (Activity Audit Trail)
-- =====================================================
-- action values:
-- - note_added, note_edited, note_deleted
-- - module_added, module_deleted
-- - plan_generated
-- - login, logout
-- - etc.
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL COMMENT 'Action: note_added, plan_generated, etc.',
    details VARCHAR(500) COMMENT 'Optional details about the action',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Activity log for audit trail and monitoring';

-- =====================================================
-- INDEXES SUMMARY
-- =====================================================
-- User lookups: idx_username, idx_email
-- Module queries: idx_user_id (on modules)
-- Note filtering: idx_module_id, idx_module_created
-- Plan queries: idx_user_date
-- Log queries: idx_user_id, idx_action, idx_created

-- =====================================================
-- INSERTION DE DONNEES TEST (Optionnel)
-- =====================================================
INSERT INTO users (username, email, password_hash, role) 
VALUES ('testuser', 'test@edu-planning.local', '$2y$10$Y9Q9X8Z7W6V5U4T3S2R1Q0P9O8N7M6L5K4J3I2H1G0F9E8D7C6B5', 'student');

INSERT INTO modules (user_id, module_name) 
SELECT id, 'Mathématiques' FROM users WHERE username = 'testuser'
UNION ALL
SELECT id, 'Physique' FROM users WHERE username = 'testuser'
UNION ALL
SELECT id, 'Chimie' FROM users WHERE username = 'testuser';

INSERT INTO notes (module_id, note_value, description)
SELECT id, 'Difficultés avec les intégrales et équations différentielles', 'Notes en mathématiques' FROM modules WHERE module_name = 'Mathématiques'
UNION ALL
SELECT id, 'Bonne compréhension de la mécanique, besoin de pratique sur la thermodynamique', 'Notes en physique' FROM modules WHERE module_name = 'Physique'
UNION ALL
SELECT id, 'Excellent en chimie organique, lutte avec les réactions inorganiques', 'Notes en chimie' FROM modules WHERE module_name = 'Chimie';

-- =====================================================
-- CHANGES FROM OLD SCHEMA
-- =====================================================
/*
SUPPRIMÉ (Removed):
  ✗ Table: api_tokens (Google OAuth cleanup)
  ✗ Column: notes.note_value as DECIMAL (error - stored grades not notes)
  ✗ Column: revision_plans.module_id (moved to JSON)
  ✗ Column: revision_plans.duration_per_day (moved to JSON)

AJOUTÉ (Added):
  ✓ Column: notes.note_value as TEXT (stores observations)
  ✓ Column: notes.updated_at
  ✓ Column: revision_plans.plan_data (JSON)
  ✓ Column: revision_plans.used_note_ids (JSON - audit)
  ✓ Column: revision_plans.selected_modules (JSON - audit)
  ✓ Column: logs.details
  ✓ Index: idx_module_created on notes
  ✓ Index: idx_user_date on revision_plans
  ✓ Index: idx_action on logs
  ✓ Role column on users
  ✓ Comprehensive comments

STRUCTURE (JSON in plan_data):
  Duration formatting (minutes) → Display as "1h 30min"
  Note selection (multi-select) → Track in used_note_ids
  Module selection → Track in selected_modules
*/
