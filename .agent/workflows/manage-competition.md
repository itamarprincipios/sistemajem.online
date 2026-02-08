---
description: Guia completo de gerenciamento de competições no JEM (da criação ao pódio)
---

# Fluxo de Trabalho JEM: Guia de Operação e Lógica do Sistema

Este documento descreve todo o ciclo de vida de uma competição no Sistema JEM, servindo como referência para operadores e assistentes de IA futuros.

## 1. Ciclo de Vida do Evento
- **Criação**: Eventos são criados no painel administrativo. Cada evento possui um `competition_event_id`.
- **Ativação**: Um evento deve estar com `status = 'active'` para aparecer no Painel do Operador. 
- **Múltiplos Eventos**: O sistema suporta múltiplos eventos ativos. O Painel do Operador detecta o evento correto dinamicamente com base nas modalidades/categorias selecionadas.

## 2. Geração de Jogos
### Fase de Grupos
- Gerada automaticamente após o encerramento das inscrições das equipes.
- Os jogos são criados como `scheduled` e agrupados por `group_name` (A, B, C, etc.).

### Mata-Mata (Knockout)
A geração é realizada manualmente pelo operador no Dashboard, seguindo a ordem:
1. **Oitavas de Final**: Cruzamento clássico (1ºA x 2ºB, 1ºB x 2ºA, etc.).
2. **Quartas de Final**: Formadas pelos vencedores das Oitavas.
3. **Semifinais**: Formadas pelos vencedores das Quartas.
4. **Final e 3º Lugar**: Geradas simultaneamente a partir dos resultados das Semifinais.

> [!IMPORTANT]
> **Independência de Gênero**: Todas as fases do mata-mata funcionam de forma independente para Masculino e Feminino. Você pode gerar a final do Feminino mesmo que o Masculino ainda esteja nas Oitavas.

## 3. Painel do Operador (Dashboard)
O fluxo de trabalho diário do operador consiste em:
1. **Seleção**: Escolher a Modalidade -> Categoria -> Gênero.
2. **Lançamento de Resultados**:
   - Inserir placares.
   - Definir o `winner_team_id` (necessário para o mata-mata).
   - Alterar o status para **Finalizado**.
3. **Controle de Súmulas**:
   - Acessar o ícone de súmula para imprimir/salvar o relatório oficial do jogo.
   - Súmulas do Society utilizam o tema azul e etiquetas específicas (ex: "EM CAMPO" em vez de QUADRA).

## 4. Lógica de Segurança e Pré-visualização
- **Bloqueio de Fase**: O sistema só permite avançar para a pré-visualização da próxima fase quando **todos** os jogos da fase atual (daquela categoria/gênero específica) estiverem com o status **Finalizado**.
- **Regra de 0-0**: Jogos agendados com 0-0 não valem como concluídos. O status deve ser explicitamente alterado para finalizar a fase.

## 5. Pódio e Premiações
Ao chegar na fase final:
1. **Prêmios Individuais**: O painel permite salvar o Best Player (Destaque) e Best Goalkeeper (Goleiro(a) Menos Vazado(a)).
2. **Renderização do Pódio**: Ao mudar para a aba "Pódio", o sistema exibe os cartões dos vencedores (1º, 2º e 3º lugares) formatados para compartilhamento.

## Referências Técnicas para a IA
- `docs/database_schema.md`: Estrutura das tabelas `matches`, `competition_teams` e `competition_events`.
- `includes/knockout_generator.php`: Contém as funções `generateRoundOf16`, `generateQuarterfinals`, `generateSemifinals`, `generateFinal` e `generateThirdPlace`.
- `operator/dashboard.php`: Lógica principal do frontend, incluindo `switchPhase` e `getEventId()`.
