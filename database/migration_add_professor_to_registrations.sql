-- Migration: Add created_by_user_id to registrations table
-- This allows tracking which professor created each team registration

ALTER TABLE registrations 
ADD COLUMN created_by_user_id INT AFTER gender,
ADD INDEX idx_created_by (created_by_user_id),
ADD FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Update existing registrations to set created_by_user_id
-- This will set the first professor of each school as the creator for existing registrations
UPDATE registrations r
LEFT JOIN users u ON u.school_id = r.school_id AND u.role = 'professor'
SET r.created_by_user_id = (
    SELECT id FROM users 
    WHERE school_id = r.school_id AND role = 'professor' 
    LIMIT 1
)
WHERE r.created_by_user_id IS NULL;
