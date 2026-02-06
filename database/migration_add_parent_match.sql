-- Add parent_match_id for knockout bracket tracking
-- This field links a match to its "parent" matches (the matches whose winners advance to this match)

ALTER TABLE matches ADD COLUMN parent_match_id INT NULL AFTER winner_team_id;
ALTER TABLE matches ADD FOREIGN KEY (parent_match_id) REFERENCES matches(id) ON DELETE SET NULL;

-- Add index for performance
ALTER TABLE matches ADD INDEX idx_parent_match (parent_match_id);
