-- Fix category name encoding
UPDATE categories SET name = 'Pré Mirin' WHERE id = 6;
SELECT id, name, min_birth_year, max_birth_year FROM categories ORDER BY min_birth_year DESC;
