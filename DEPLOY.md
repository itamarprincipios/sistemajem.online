# 🚀 Guia de Deploy - Sistema JEM

## 📋 Pré-requisitos

Antes de fazer o deploy, certifique-se de ter:

- ✅ Servidor web com PHP 8.0+ e MySQL 8.0+
- ✅ Acesso SSH ou FTP ao servidor
- ✅ Credenciais do banco de dados de produção
- ✅ Domínio configurado e apontando para o servidor

## 🔧 Passo a Passo do Deploy

### 1. Preparar o Banco de Dados

```bash
# Conectar ao MySQL no servidor de produção
mysql -u seu_usuario -p

# Criar o banco de dados
CREATE DATABASE jem_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Sair do MySQL
exit;

# Importar o schema
mysql -u seu_usuario -p jem_database < database/schema.sql
```

### 2. Configurar o Arquivo de Configuração

```bash
# No servidor de produção, copie o arquivo de configuração
cp config/config.production.php config/config.php

# Edite o arquivo com as credenciais corretas
nano config/config.php  # ou vim, ou outro editor
```

**Configurações que você DEVE alterar em `config/config.php`:**

```php
// Banco de dados
define('DB_HOST', 'localhost');  // ou IP do servidor MySQL
define('DB_USER', 'seu_usuario_mysql');  // ALTERAR

# Criar diretório de logs
mkdir -p logs
chmod 755 logs

# Permissões dos arquivos
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
```

### 4. Configurar Servidor Web

#### Para Apache:

O arquivo `.htaccess` já está configurado. Certifique-se de que o módulo `mod_rewrite` está ativado:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Para Nginx:

Adicione ao seu arquivo de configuração do site:

```nginx
server {
    listen 80;
    server_name seu-dominio.com;
    root /caminho/para/sistama-JEM;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    client_max_body_size 5M;
}
```

### 5. Configurar SSL/HTTPS (Recomendado)

```bash
# Instalar Certbot (Let's Encrypt)
sudo apt install certbot python3-certbot-apache  # Para Apache
# ou
sudo apt install certbot python3-certbot-nginx   # Para Nginx

# Obter certificado SSL
sudo certbot --apache -d seu-dominio.com  # Para Apache
# ou
sudo certbot --nginx -d seu-dominio.com   # Para Nginx
```

Após configurar SSL, descomente as linhas de redirecionamento HTTPS no `.htaccess`:

```apache
# Remova o comentário destas linhas:
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 6. Primeiro Acesso e Configuração

1. Acesse: `https://seu-dominio.com`
2. Faça login com as credenciais padrão:
   - **Email:** `admin@jem.com`
   - **Senha:** `Admin@123`

3. **⚠️ IMPORTANTE:** Altere imediatamente:
   - Senha do administrador
   - Email do administrador

### 7. Configurar Backup Automático

Crie um script de backup:

```bash
# Criar arquivo backup.sh
nano /home/seu_usuario/backup.sh
```

Conteúdo do `backup.sh`:

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/seu_usuario/backups"
DB_NAME="jem_database"
DB_USER="seu_usuario"
DB_PASS="sua_senha"

# Criar diretório de backup se não existir
mkdir -p $BACKUP_DIR

# Backup do banco de dados
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/jem_backup_$DATE.sql

# Manter apenas os últimos 7 backups
ls -t $BACKUP_DIR/jem_backup_*.sql | tail -n +8 | xargs rm -f

echo "Backup concluído: jem_backup_$DATE.sql"
```

Tornar executável e adicionar ao cron:

```bash
chmod +x /home/seu_usuario/backup.sh

# Adicionar ao crontab (backup diário às 2h da manhã)
crontab -e
# Adicione a linha:
0 2 * * * /home/seu_usuario/backup.sh
```

## ✅ Checklist Final

Após o deploy, verifique:

- [ ] Site está acessível via HTTPS
- [ ] Login funciona corretamente
- [ ] Upload de fotos funciona
- [ ] CSS e JavaScript estão carregando
- [ ] Rodapé da N Circuits Technologies está visível
- [ ] Senha do admin foi alterada
- [ ] Backup automático está configurado
- [ ] Logs de erro estão sendo gerados em `logs/php_errors.log`

## 🔒 Segurança Pós-Deploy

### Verificações de Segurança:

1. **Teste se arquivos sensíveis estão protegidos:**
   ```bash
   # Estes devem retornar 403 Forbidden:
   curl https://seu-dominio.com/config/config.php
   curl https://seu-dominio.com/database/schema.sql
   ```

2. **Verifique se display_errors está desabilitado:**
   - Tente acessar uma página que não existe
   - Você NÃO deve ver detalhes de erro do PHP

3. **Verifique os logs:**
   ```bash
   tail -f logs/php_errors.log
   ```

## 🐛 Troubleshooting

### Erro: "Erro de conexão com o banco de dados"

**Solução:**
1. Verifique credenciais em `config/config.php`
2. Verifique se o MySQL está rodando: `sudo systemctl status mysql`
3. Teste a conexão: `mysql -u seu_usuario -p jem_database`

### Erro: "Permission denied" ao fazer upload

**Solução:**
```bash
chmod 755 uploads
chmod 755 uploads/students
chown -R www-data:www-data uploads  # Para Apache/Nginx
```

### CSS/JS não carregam

**Solução:**
1. Verifique se `SITE_URL` está correto em `config/config.php`
2. Limpe o cache do navegador
3. Verifique permissões da pasta `assets/`

### Sessão expira muito rápido

**Solução:**
Ajuste em `config/config.php`:
```php
define('SESSION_LIFETIME', 3600 * 8); // 8 horas
```

## 📊 Monitoramento

### Verificar logs de acesso:
```bash
# Apache
tail -f /var/log/apache2/access.log

# Nginx
tail -f /var/log/nginx/access.log
```

### Verificar logs de erro:
```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log

# PHP (do sistema)
tail -f logs/php_errors.log
```

## 📞 Suporte

Sistema desenvolvido por **N Circuits Technologies**

- WhatsApp: +55 95 99124-8941
- Especialistas em desenvolvimento web personalizado

---

**✅ Deploy concluído com sucesso!**

O Sistema JEM está pronto para gerenciar os Jogos Escolares Municipais.
