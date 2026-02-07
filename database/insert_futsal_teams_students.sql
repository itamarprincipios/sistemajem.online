-- ============================================
-- GERAÇÃO DE EQUIPES E ALUNOS DE FUTSAL
-- 32 escolas × 3 categorias × 2 gêneros = 192 equipes
-- 192 equipes × 7 alunos = 1.344 alunos
-- ============================================

-- Obter IDs necessários (usar IDs diretos das categorias)
SET @futsal_id = (SELECT id FROM modalities WHERE name = 'Futsal' LIMIT 1);
SET @fraldinhas_id = 5;  -- Fraldinhas
SET @pre_mirim_id = 6;   -- Pré-Mirim
SET @mirim_id = 7;       -- Mirim

-- ============================================
-- CRIAR EQUIPES (REGISTRATIONS)
-- ============================================

-- Para cada escola, criar 6 equipes (3 categorias × 2 gêneros)
INSERT INTO registrations (school_id, modality_id, category_id, gender, status, created_at)
SELECT 
    s.id as school_id,
    @futsal_id as modality_id,
    c.category_id,
    c.gender,
    'approved' as status,
    NOW() as created_at
FROM schools s
CROSS JOIN (
    SELECT @fraldinhas_id as category_id, 'M' as gender
    UNION ALL SELECT @fraldinhas_id, 'F'
    UNION ALL SELECT @pre_mirim_id, 'M'
    UNION ALL SELECT @pre_mirim_id, 'F'
    UNION ALL SELECT @mirim_id, 'M'
    UNION ALL SELECT @mirim_id, 'F'
) c
WHERE s.municipality = 'Rorainópolis'
ORDER BY s.id, c.category_id, c.gender;

-- ============================================
-- CRIAR ALUNOS E VINCULAR ÀS EQUIPES
-- ============================================

-- Procedimento para gerar alunos
DELIMITER $$

DROP PROCEDURE IF EXISTS generate_futsal_students$$

CREATE PROCEDURE generate_futsal_students()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE reg_id INT;
    DECLARE sch_id INT;
    DECLARE cat_id INT;
    DECLARE gen CHAR(1);
    DECLARE student_count INT DEFAULT 0;
    DECLARE i INT;
    
    -- Arrays de nomes
    DECLARE first_names_m TEXT DEFAULT 'Gabriel,Lucas,Miguel,Arthur,Pedro,Enzo,Davi,Bernardo,Rafael,Matheus,Felipe,João,Gustavo,Nicolas,Lorenzo,Henrique,Vitor,Samuel,Daniel,Eduardo,Bruno,Thiago,Rodrigo,Leonardo,Diego,Caio,André,Marcelo,Fernando,Ricardo,Carlos,Paulo';
    DECLARE first_names_f TEXT DEFAULT 'Maria,Ana,Beatriz,Sofia,Julia,Laura,Isabela,Manuela,Valentina,Alice,Helena,Luiza,Giovanna,Mariana,Lara,Melissa,Cecília,Fernanda,Camila,Amanda,Juliana,Letícia,Gabriela,Larissa,Bianca,Natália,Carolina,Renata,Vanessa,Patrícia,Aline,Bruna';
    DECLARE last_names TEXT DEFAULT 'Silva,Santos,Oliveira,Souza,Costa,Ferreira,Rodrigues,Almeida,Nascimento,Lima,Araújo,Fernandes,Carvalho,Gomes,Martins,Rocha,Ribeiro,Alves,Pereira,Cardoso,Dias,Monteiro,Barbosa,Freitas,Mendes,Castro,Moreira,Cavalcanti,Campos,Ramos,Correia,Vieira';
    
    DECLARE cur CURSOR FOR 
        SELECT r.id, r.school_id, r.category_id, r.gender 
        FROM registrations r 
        WHERE r.modality_id = @futsal_id 
        AND r.status = 'approved'
        ORDER BY r.id;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO reg_id, sch_id, cat_id, gen;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Gerar 7 alunos para cada equipe
        SET i = 1;
        WHILE i <= 7 DO
            -- Gerar nome baseado no gênero
            SET @first_name = IF(gen = 'M',
                SUBSTRING_INDEX(SUBSTRING_INDEX(first_names_m, ',', FLOOR(1 + RAND() * 32)), ',', -1),
                SUBSTRING_INDEX(SUBSTRING_INDEX(first_names_f, ',', FLOOR(1 + RAND() * 32)), ',', -1)
            );
            
            SET @last_name = SUBSTRING_INDEX(SUBSTRING_INDEX(last_names, ',', FLOOR(1 + RAND() * 32)), ',', -1);
            SET @full_name = CONCAT(@first_name, ' ', @last_name);
            
            -- Gerar CPF único (formato fictício)
            SET @cpf = CONCAT(
                LPAD(FLOOR(RAND() * 1000), 3, '0'), '.',
                LPAD(FLOOR(RAND() * 1000), 3, '0'), '.',
                LPAD(FLOOR(RAND() * 1000), 3, '0'), '-',
                LPAD(FLOOR(RAND() * 100), 2, '0')
            );
            
            -- Gerar data de nascimento baseada na categoria
            SET @birth_date = CASE cat_id
                WHEN @fraldinhas_id THEN DATE_SUB(CURDATE(), INTERVAL (6 + FLOOR(RAND() * 2)) YEAR)  -- 6-7 anos
                WHEN @pre_mirim_id THEN DATE_SUB(CURDATE(), INTERVAL (8 + FLOOR(RAND() * 2)) YEAR)   -- 8-9 anos
                WHEN @mirim_id THEN DATE_SUB(CURDATE(), INTERVAL (10 + FLOOR(RAND() * 2)) YEAR)      -- 10-11 anos
                ELSE DATE_SUB(CURDATE(), INTERVAL 8 YEAR)
            END;
            
            SET @age = YEAR(CURDATE()) - YEAR(@birth_date);
            
            -- Inserir aluno
            INSERT INTO students (
                name, 
                document_number, 
                birth_date, 
                gender, 
                age, 
                school_id,
                created_at
            ) VALUES (
                @full_name,
                @cpf,
                @birth_date,
                gen,
                @age,
                sch_id,
                NOW()
            );
            
            SET @student_id = LAST_INSERT_ID();
            
            -- Vincular aluno à equipe
            INSERT INTO enrollments (student_id, registration_id, created_at)
            VALUES (@student_id, reg_id, NOW());
            
            SET student_count = student_count + 1;
            SET i = i + 1;
        END WHILE;
        
    END LOOP;
    
    CLOSE cur;
    
    SELECT CONCAT('✅ Gerados ', student_count, ' alunos de futsal!') as resultado;
END$$

DELIMITER ;

-- Executar o procedimento
CALL generate_futsal_students();

-- Limpar procedimento
DROP PROCEDURE IF EXISTS generate_futsal_students;

-- ============================================
-- VERIFICAÇÃO
-- ============================================

-- Contar equipes criadas
SELECT 
    'Equipes de Futsal' as tipo,
    COUNT(*) as total
FROM registrations 
WHERE modality_id = @futsal_id;

-- Contar alunos por categoria e gênero
SELECT 
    c.name as categoria,
    r.gender as genero,
    COUNT(DISTINCT e.student_id) as total_alunos,
    COUNT(DISTINCT r.id) as total_equipes
FROM registrations r
JOIN categories c ON r.category_id = c.id
LEFT JOIN enrollments e ON e.registration_id = r.id
WHERE r.modality_id = @futsal_id
GROUP BY c.name, r.gender
ORDER BY c.name, r.gender;

-- Verificar distribuição por escola
SELECT 
    s.name as escola,
    COUNT(DISTINCT r.id) as equipes,
    COUNT(DISTINCT e.student_id) as alunos
FROM schools s
LEFT JOIN registrations r ON r.school_id = s.id AND r.modality_id = @futsal_id
LEFT JOIN enrollments e ON e.registration_id = r.id
WHERE s.municipality = 'Rorainópolis'
GROUP BY s.id, s.name
ORDER BY s.name
LIMIT 10;
