-- Migration: Add created_by_user_id to students table
-- This allows tracking which professor created each student

ALTER TABLE students 
ADD COLUMN created_by_user_id INT AFTER school_id,
ADD INDEX idx_student_created_by (created_by_user_id),
ADD FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Update existing students to set created_by_user_id
-- This will set the first professor of each school as the creator for existing students
UPDATE students s
LEFT JOIN users u ON u.school_id = s.school_id AND u.role = 'professor'
SET s.created_by_user_id = (
    SELECT id FROM users 
    WHERE school_id = s.school_id AND role = 'professor' 
    LIMIT 1
)
WHERE s.created_by_user_id IS NULL;
