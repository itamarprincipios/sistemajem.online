-- Script para Zerar Todos os Cadastros de Times e Competições
-- ATENÇÃO: Este script apaga TODOS os dados de competição!

-- 1. Limpar eventos de partidas (gols, cartões, etc.)
DELETE FROM match_events;

-- 2. Limpar todas as partidas
DELETE FROM matches;

-- 3. Limpar atletas dos times de competição
DELETE FROM competition_team_athletes;

-- 4. Limpar times de competição
DELETE FROM competition_teams;

-- 5. Limpar eventos de competição
DELETE FROM competition_events;

-- 6. Limpar operadores de competição (opcional - descomente se quiser resetar)
-- DELETE FROM competition_operators;

-- 7. Resetar auto-increment (opcional)
ALTER TABLE match_events AUTO_INCREMENT = 1;
ALTER TABLE matches AUTO_INCREMENT = 1;
ALTER TABLE competition_team_athletes AUTO_INCREMENT = 1;
ALTER TABLE competition_teams AUTO_INCREMENT = 1;
ALTER TABLE competition_events AUTO_INCREMENT = 1;

-- Verificação: Contar registros restantes
SELECT 'match_events' as tabela, COUNT(*) as total FROM match_events
UNION ALL
SELECT 'matches', COUNT(*) FROM matches
UNION ALL
SELECT 'competition_team_athletes', COUNT(*) FROM competition_team_athletes
UNION ALL
SELECT 'competition_teams', COUNT(*) FROM competition_teams
UNION ALL
SELECT 'competition_events', COUNT(*) FROM competition_events;
