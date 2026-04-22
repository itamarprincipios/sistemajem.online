-- Migração Multi-tenancy: Sistema JEM
-- Descrição: Transforma o sistema em Micro-SaaS isolando dados por Secretaria de Educação.

-- 1. Criar tabela de Secretarias
CREATE TABLE IF NOT EXISTS secretarias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE, -- Usado na URL: sistemajem.online/slug/
    email VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Inserir a Secretaria Padrão para os dados atuais
INSERT INTO secretarias (nome, slug) VALUES ('Sistema JEM', 'jem');
SET @default_secretaria_id = LAST_INSERT_ID();

-- 3. Adicionar secretaria_id às tabelas base
-- Users
ALTER TABLE users ADD COLUMN secretaria_id INT DEFAULT NULL AFTER id;
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'professor', 'operator', 'super_admin') NOT NULL;
UPDATE users SET secretaria_id = @default_secretaria_id WHERE role != 'super_admin';
-- Nota: Super admins terão secretaria_id NULL para gerenciar tudo globalmente.

-- Schools
ALTER TABLE schools ADD COLUMN secretaria_id INT DEFAULT NULL AFTER id;
UPDATE schools SET secretaria_id = @default_secretaria_id;
ALTER TABLE schools ADD FOREIGN KEY (secretaria_id) REFERENCES secretarias(id) ON DELETE CASCADE;

-- Categories
ALTER TABLE categories ADD COLUMN secretaria_id INT DEFAULT NULL AFTER id;
UPDATE categories SET secretaria_id = @default_secretaria_id;
ALTER TABLE categories ADD FOREIGN KEY (secretaria_id) REFERENCES secretarias(id) ON DELETE CASCADE;

-- Modalities
ALTER TABLE modalities ADD COLUMN secretaria_id INT DEFAULT NULL AFTER id;
UPDATE modalities SET secretaria_id = @default_secretaria_id;
ALTER TABLE modalities ADD FOREIGN KEY (secretaria_id) REFERENCES secretarias(id) ON DELETE CASCADE;

-- Registration Requests
ALTER TABLE registration_requests ADD COLUMN secretaria_id INT DEFAULT NULL AFTER id;
UPDATE registration_requests SET secretaria_id = @default_secretaria_id;

-- Students
ALTER TABLE students ADD COLUMN secretaria_id INT DEFAULT NULL AFTER id;
UPDATE students SET secretaria_id = @default_secretaria_id;
ALTER TABLE students ADD FOREIGN KEY (secretaria_id) REFERENCES secretarias(id) ON DELETE CASCADE;

-- Registrations
ALTER TABLE registrations ADD COLUMN secretaria_id INT DEFAULT NULL AFTER id;
UPDATE registrations SET secretaria_id = @default_secretaria_id;
ALTER TABLE registrations DROP INDEX IF EXISTS unique_registration;
ALTER TABLE registrations ADD UNIQUE KEY unique_registration_tenant (secretaria_id, school_id, modality_id, category_id, gender);
ALTER TABLE registrations ADD FOREIGN KEY (secretaria_id) REFERENCES secretarias(id) ON DELETE CASCADE;

-- 4. Tabelas do Engine de Competição
-- Competition Events
ALTER TABLE competition_events ADD COLUMN secretaria_id INT DEFAULT NULL AFTER id;
UPDATE competition_events SET secretaria_id = @default_secretaria_id;
ALTER TABLE competition_events ADD FOREIGN KEY (secretaria_id) REFERENCES secretarias(id) ON DELETE CASCADE;

-- Matches
ALTER TABLE matches ADD COLUMN secretaria_id INT DEFAULT NULL AFTER id;
UPDATE matches SET secretaria_id = @default_secretaria_id;
ALTER TABLE matches ADD FOREIGN KEY (secretaria_id) REFERENCES secretarias(id) ON DELETE CASCADE;

-- Audit Logs
ALTER TABLE audit_logs ADD COLUMN secretaria_id INT DEFAULT NULL AFTER id;
UPDATE audit_logs SET secretaria_id = @default_secretaria_id;

-- 5. Finalizar definições de NOT NULL onde obrigatório
ALTER TABLE schools MODIFY COLUMN secretaria_id INT NOT NULL;
ALTER TABLE categories MODIFY COLUMN secretaria_id INT NOT NULL;
ALTER TABLE modalities MODIFY COLUMN secretaria_id INT NOT NULL;
ALTER TABLE students MODIFY COLUMN secretaria_id INT NOT NULL;
ALTER TABLE registrations MODIFY COLUMN secretaria_id INT NOT NULL;
ALTER TABLE competition_events MODIFY COLUMN secretaria_id INT NOT NULL;
ALTER TABLE matches MODIFY COLUMN secretaria_id INT NOT NULL;