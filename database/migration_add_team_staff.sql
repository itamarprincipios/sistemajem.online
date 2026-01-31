-- Migration: Add Team Staff Fields
-- Adiciona campos para Técnico, Auxiliar Técnico e Chefe de Delegação nas equipes
-- Data: 2026-01-31

ALTER TABLE registrations 
ADD COLUMN tecnico_nome VARCHAR(255) NULL AFTER status,
ADD COLUMN tecnico_celular VARCHAR(20) NULL AFTER tecnico_nome,
ADD COLUMN auxiliar_tecnico_nome VARCHAR(255) NULL AFTER tecnico_celular,
ADD COLUMN auxiliar_tecnico_celular VARCHAR(20) NULL AFTER auxiliar_tecnico_nome,
ADD COLUMN chefe_delegacao_nome VARCHAR(255) NULL AFTER auxiliar_tecnico_celular,
ADD COLUMN chefe_delegacao_celular VARCHAR(20) NULL AFTER chefe_delegacao_nome;
