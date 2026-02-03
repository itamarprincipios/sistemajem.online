-- Add support for Round of 16 and Third Place phases
ALTER TABLE matches MODIFY COLUMN phase ENUM(
    'group_stage',
    'round_of_16',
    'quarter_final',
    'semi_final',
    'third_place',
    'final'
) NOT NULL DEFAULT 'group_stage';

-- Update winner_team_id after each match finishes
-- This trigger will automatically set the winner based on the score
DELIMITER $$

CREATE TRIGGER set_match_winner BEFORE UPDATE ON matches
FOR EACH ROW
BEGIN
    IF NEW.status = 'finished' AND OLD.status != 'finished' THEN
        IF NEW.score_team_a > NEW.score_team_b THEN
            SET NEW.winner_team_id = NEW.team_a_id;
        ELSEIF NEW.score_team_b > NEW.score_team_a THEN
            SET NEW.winner_team_id = NEW.team_b_id;
        ELSE
            SET NEW.winner_team_id = NULL; -- Draw (will need penalty shootout logic later)
        END IF;
    END IF;
END$$

DELIMITER ;
