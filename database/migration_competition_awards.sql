CREATE TABLE IF NOT EXISTS competition_awards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competition_event_id INT NOT NULL,
    modality_id INT NOT NULL,
    category_id INT NOT NULL,
    gender ENUM('M', 'F') NOT NULL,
    award_type ENUM('BEST_PLAYER', 'BEST_GK') NOT NULL,
    winner_name VARCHAR(255),
    school_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_award (competition_event_id, modality_id, category_id, gender, award_type),
    FOREIGN KEY (competition_event_id) REFERENCES competition_events(id) ON DELETE CASCADE,
    FOREIGN KEY (modality_id) REFERENCES modalities(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
