-- Migration: Competition Engine V1
-- Data: 02/02/2026
-- Description: Creates tables for competition management, snapshots, matches, and logs.

-- 1. Update Users Enum (Add 'operator' role)
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'professor', 'operator') NOT NULL;

-- 2. Competition Events
CREATE TABLE IF NOT EXISTS competition_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('planning', 'ready', 'live', 'finished') DEFAULT 'planning',
    location_city VARCHAR(100),
    active_flag BOOLEAN DEFAULT FALSE, -- Flag to easy identify the current main event
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Operators (Permissions)
CREATE TABLE IF NOT EXISTS competition_operators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    competition_event_id INT NOT NULL,
    assigned_modality_id INT NULL, -- NULL = All modalities
    assigned_venue VARCHAR(100) NULL, -- NULL = All venues
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (competition_event_id) REFERENCES competition_events(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_modality_id) REFERENCES modalities(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Competition Teams (Snapshot)
CREATE TABLE IF NOT EXISTS competition_teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competition_event_id INT NOT NULL,
    registration_id INT NOT NULL, -- Link to original registration
    school_id INT NOT NULL,
    modality_id INT NOT NULL,
    category_id INT NOT NULL,
    gender ENUM('M', 'F', 'mixed') NOT NULL,
    school_name_snapshot VARCHAR(255) NOT NULL, -- Freeze name
    group_name VARCHAR(10) NULL, -- For Group Stage (A, B, C...)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_event_id) REFERENCES competition_events(id) ON DELETE CASCADE,
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (modality_id) REFERENCES modalities(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Competition Athletes (Snapshot)
CREATE TABLE IF NOT EXISTS competition_team_athletes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competition_team_id INT NOT NULL,
    student_id INT NOT NULL,
    name_snapshot VARCHAR(255) NOT NULL,
    jersey_number INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_team_id) REFERENCES competition_teams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Matches
CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competition_event_id INT NOT NULL,
    modality_id INT NOT NULL,
    category_id INT NOT NULL,
    phase VARCHAR(50) NOT NULL DEFAULT 'group_stage', -- group_stage, quarter_final, semi_final, final
    team_a_id INT NOT NULL,
    team_b_id INT NOT NULL,
    venue VARCHAR(100),
    scheduled_time DATETIME NOT NULL,
    start_time DATETIME NULL,
    end_time DATETIME NULL,
    status ENUM('scheduled', 'ready', 'live', 'finished', 'locked') DEFAULT 'scheduled',
    score_team_a INT DEFAULT 0,
    score_team_b INT DEFAULT 0,
    winner_team_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_event_id) REFERENCES competition_events(id) ON DELETE CASCADE,
    FOREIGN KEY (modality_id) REFERENCES modalities(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (team_a_id) REFERENCES competition_teams(id) ON DELETE CASCADE,
    FOREIGN KEY (team_b_id) REFERENCES competition_teams(id) ON DELETE CASCADE,
    FOREIGN KEY (winner_team_id) REFERENCES competition_teams(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_schedule (scheduled_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Match Events
CREATE TABLE IF NOT EXISTS match_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    team_id INT NOT NULL,
    athlete_id INT NULL, -- Null if generic team event or own goal unknown
    event_type ENUM('GOAL', 'OWN_GOAL', 'YELLOW_CARD', 'RED_CARD', 'FOUL', 'TIMEOUT') NOT NULL,
    event_minute INT NULL,
    observation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES competition_teams(id) ON DELETE CASCADE,
    FOREIGN KEY (athlete_id) REFERENCES competition_team_athletes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Audit Logs
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- Null for system actions
    action VARCHAR(50) NOT NULL, -- e.g., MATCH_START, GOAL_ADD
    entity VARCHAR(50) NOT NULL, -- e.g., match, event
    entity_id INT NOT NULL,
    changes JSON NULL, -- Store before/after if possible
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
