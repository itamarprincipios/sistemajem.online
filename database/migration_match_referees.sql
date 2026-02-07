-- Migration: Add Match Referee Fields
-- Adiciona campos para Árbitro Principal, Assistente e Quarto Árbitro na tabela de partidas
-- Data: 2026-02-07

ALTER TABLE matches 
ADD COLUMN referee_primary VARCHAR(255) NULL AFTER team_b_assistant,
ADD COLUMN referee_assistant VARCHAR(255) NULL AFTER referee_primary,
ADD COLUMN referee_fourth VARCHAR(255) NULL AFTER referee_assistant;
