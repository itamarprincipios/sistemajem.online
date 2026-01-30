# 🐊 Guia de Deploy - HostGator (cPanel)

Este guia foi feito especificamente para o painel da HostGator. Siga os passos abaixo com atenção.

## 1. Preparar os Arquivos (No seu Computador)

1.  Abra a pasta do projeto `sistama JEM`.
2.  Selecione **todos os arquivos e pastas**, EXCETO:
    *   `.git` (pasta oculta)
    *   `.vscode` (se houver)
    *   `node_modules` (se houver)
    *   `tests` (se houver)
3.  Clique com o botão direito -> **Enviar para** -> **Pasta compactada (zip)**.
4.  Nomeie o arquivo como `sistema_jem.zip`.

## 2. Criar Banco de Dados (Na HostGator)

1.  Acesse o **cPanel** da HostGator.
2.  Vá na seção **Banco de Dados** e clique em **Bancos de Dados MySQL**.
3.  **Criar Novo Banco de Dados:**
    *   Nome: `jem` (o nome final será algo como `seuusuario_jem`).
    *   Clique em "Criar Banco de Dados".
    *   Anote o nome completo do banco (ex: `usuario_jem`).
4.  **Criar Novo Usuário:**
    *   Role para baixo até "Usuários do MySQL".
    *   Nome de usuário: `admin` (final: `usuario_admin`).
    *   Senha: Crie uma senha forte e **ANOTE-A**.
    *   Clique em "Criar Usuário".
5.  **Adicionar Usuário ao Banco:**
    *   Role até "Adicionar Usuário ao Banco de Dados".
    *   Selecione o usuário e o banco que você acabou de criar.
    *   Clique em "Adicionar".
    *   Marque a opção **TODOS OS PRIVILÉGIOS**.
    *   Clique em "Fazer Alterações".

## 3. Importar o Banco de Dados

1.  Volte ao cPanel e clique em **phpMyAdmin**.
2.  Na lateral esquerda, clique no banco de dados que você criou (`usuario_jem`).
3.  No menu superior, clique em **Importar**.
4.  Clique em "Escolher arquivo" e selecione o arquivo `database/schema.sql` do seu projeto.
5.  Role até o final e clique em **Executar**.
    *   *Sucesso:* Você verá uma mensagem verde confirmando a criação das tabelas.

## 4. Enviar os Arquivos

1.  No cPanel, vá em **Arquivos** -> **Gerenciador de Arquivos**.
2.  Navegue até a pasta `public_html`.
    *   Se for o site principal, fique na `public_html`.
    *   Se for um subdomínio ou pasta, entre nela (ex: `public_html/sistema`).
3.  Clique em **Carregar** (Upload) no topo.
4.  Arraste o arquivo `sistema_jem.zip` que você criou.
5.  Após completar, volte ao Gerenciador de Arquivos.
6.  Clique com o botão direito no `sistema_jem.zip` e escolha **Extract** (Extrair).
7.  Pode apagar o arquivo `.zip` depois.

## 5. Configurar o Sistema

1.  No Gerenciador de Arquivos, entre na pasta `config`.
2.  Procure o arquivo `config.production.php`.
3.  Renomeie-o para `config.php` (se já existir um `config.php`, apague-o antes).
4.  Clique com o botão direito em `config.php` e escolha **Edit**.
5.  Altere as linhas com os dados do passo 2:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'usuario_admin'); // Nome do usuário criado no cPanel
define('DB_PASS', 'SuaSenhaForte'); // Senha criada no cPanel
define('DB_NAME', 'usuario_jem');   // Nome do banco criado no cPanel

define('SITE_URL', 'https://seudominio.com'); // Seu domínio real
```

6.  Clique em **Salvar Alterações**.

## 6. Finalização

1.  Acesse seu site: `https://seudominio.com`
2.  Faça login com:
    *   **Email:** `admin@jem.com`
    *   **Senha:** `Admin@123`
3.  **MUITO IMPORTANTE:** Vá em Perfil e altere sua senha imediatamente!

---
**Problemas Comuns:**
*   **Erro 500:** Verifique se as permissões das pastas estão `755` e arquivos `644`.
*   **Erro de Conexão:** Verifique se o usuário do banco tem senha correta e foi adicionado ao banco com "Todos os Privilégios".
