-- Migration: Add Match Staff Fields
-- Adiciona campos para Técnico e Auxiliar Técnico diretamente na tabela de partidas
-- Data: 2026-02-07

ALTER TABLE matches 
ADD COLUMN team_a_coach VARCHAR(255) NULL AFTER winner_team_id,
ADD COLUMN team_a_assistant VARCHAR(255) NULL AFTER team_a_coach,
ADD COLUMN team_b_coach VARCHAR(255) NULL AFTER team_a_assistant,
ADD COLUMN team_b_assistant VARCHAR(255) NULL AFTER team_b_coach;
