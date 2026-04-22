<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/config.php';
require_once 'includes/db.php';

echo "--- SINCRONIZADOR DE DADOS: RORAINÓPOLIS ---\n\n";

try {
    // 1. Pegar o ID correto da secretaria
    $ror = queryOne("SELECT id, nome FROM secretarias WHERE slug = 'rorainopolis' OR nome LIKE '%Rorainopolis%'");
    if (!$ror) {
        die("❌ Erro: Secretaria de Rorainópolis não encontrada no banco.");
    }
    $rid = $ror['id'];
    echo "✅ ID da Secretaria: $rid ({$ror['nome']})\n";

    // 2. Corrigir Escolas (Vincular pelo nome do município)
    $sqlSch = "UPDATE schools SET secretaria_id = ? WHERE (municipality LIKE '%Rorainopolis%' OR municipality IS NULL) AND (secretaria_id IS NULL OR secretaria_id = 0 OR secretaria_id != ?)";
    $affectedSch = executeWithCount($sqlSch, [$rid, $rid]);
    echo "📝 Escolas Sincronizadas: $affectedSch\n";

    // 3. Corrigir Usuários (Administradores e Operadores de Rorainópolis)
    $sqlUsers = "UPDATE users SET secretaria_id = ? WHERE (email LIKE '%rorainopolis%') AND (secretaria_id IS NULL OR secretaria_id = 0 OR secretaria_id != ?)";
    $affectedUsers = executeWithCount($sqlUsers, [$rid, $rid]);
    echo "👥 Usuários Sincronizados: $affectedUsers\n";

    // 4. Corrigir Operadores (Vincular através dos usuários vinculados)
    // (Isso já deve ser resolvido pelo passo 3, pois a API de Operadores faz JOIN com users)

    echo "\n🚀 Sincronização concluída! Por favor, atualize sua página de Escolas e Operadores.";

} catch (Exception $e) {
    echo "💥 Erro Crítico: " . $e->getMessage();
}
