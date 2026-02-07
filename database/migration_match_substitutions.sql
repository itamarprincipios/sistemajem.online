-- Migration: Match Substitutions and Lineups
-- Adds support for substitution events and persisting who is on court.
-- Data: 2026-02-07

-- 1. Update Match Events to support SUBSTITUTION
ALTER TABLE match_events 
MODIFY COLUMN event_type ENUM('GOAL', 'OWN_GOAL', 'YELLOW_CARD', 'RED_CARD', 'FOUL', 'TIMEOUT', 'SUBSTITUTION') NOT NULL;

-- 2. Add column for the player entering
ALTER TABLE match_events 
ADD COLUMN athlete_id_in INT NULL AFTER athlete_id,
ADD FOREIGN KEY (athlete_id_in) REFERENCES competition_team_athletes(id) ON DELETE SET NULL;

-- 3. Add lineup persistence to matches
ALTER TABLE matches 
ADD COLUMN team_a_lineup JSON NULL,
ADD COLUMN team_b_lineup JSON NULL;
