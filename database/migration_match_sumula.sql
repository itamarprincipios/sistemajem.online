-- Migration: Add Captains and Observations
-- Permite identificar o capitão de cada equipe e registrar o relato do árbitro.
-- Data: 2026-02-07

ALTER TABLE matches 
ADD COLUMN team_a_captain_id INT NULL AFTER team_a_lineup,
ADD COLUMN team_b_captain_id INT NULL AFTER team_b_lineup,
ADD COLUMN observations TEXT NULL AFTER referee_fourth,
ADD FOREIGN KEY (team_a_captain_id) REFERENCES competition_team_athletes(id) ON DELETE SET NULL,
ADD FOREIGN KEY (team_b_captain_id) REFERENCES competition_team_athletes(id) ON DELETE SET NULL;
