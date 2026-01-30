-- Migration: Convert Categories from Age-Based to Birth Year-Based
-- Sistema JEM - 2025
-- Execute este script para atualizar o banco de dados existente

-- Passo 1: Adicionar novos campos
ALTER TABLE categories 
  ADD COLUMN min_birth_year INT AFTER name,
  ADD COLUMN max_birth_year INT AFTER min_birth_year;

-- Passo 2: Atualizar categorias existentes para 2025
-- Estas são as categorias padrão para o ano de 2025
UPDATE categories SET min_birth_year = 2017, max_birth_year = 2018 WHERE name = 'Fraldinha';
UPDATE categories SET min_birth_year = 2014, max_birth_year = 2016 WHERE name = 'Pré Mirin';
UPDATE categories SET min_birth_year = 2012, max_birth_year = 2013 WHERE name = 'Mirin';
UPDATE categories SET min_birth_year = 2010, max_birth_year = 2011 WHERE name = 'Mirin 2';

-- Passo 3: Limpar categorias antigas (se existirem)
DELETE FROM categories WHERE name IN ('Sub-12', 'Sub-14', 'Sub-16', 'Sub-18');

-- Passo 4: Inserir categorias 2025 se não existirem
INSERT INTO categories (name, min_birth_year, max_birth_year) 
SELECT 'Fraldinha', 2017, 2018
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Fraldinha');

INSERT INTO categories (name, min_birth_year, max_birth_year) 
SELECT 'Pré Mirin', 2014, 2016
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Pré Mirin');

INSERT INTO categories (name, min_birth_year, max_birth_year) 
SELECT 'Mirin', 2012, 2013
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Mirin');

INSERT INTO categories (name, min_birth_year, max_birth_year) 
SELECT 'Mirin 2', 2010, 2011
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Mirin 2');

-- Passo 5: Remover coluna antiga (OPCIONAL - execute apenas após confirmar que tudo funciona)
-- ALTER TABLE categories DROP COLUMN max_age;

-- Verificar resultado
SELECT id, name, min_birth_year, max_birth_year FROM categories ORDER BY min_birth_year DESC;
