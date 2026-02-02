-- Script de Geração de Dados de Teste (Mass Seed - FULL COVERAGE)
-- Objetivo: Criar equipe para TODAS as Escolas, em TODAS as Modalidades e TODAS as Categorias.
-- Restrição: Apenas gênero 'M' (Masculino).

DELIMITER //

DROP PROCEDURE IF EXISTS GenerateMassTestData //

CREATE PROCEDURE GenerateMassTestData()
BEGIN
    -- Variáveis de controle
    DECLARE done_school INT DEFAULT FALSE;
    DECLARE v_school_id INT;
    DECLARE v_user_id INT;
    
    -- Cursor para Escolas
    DECLARE cur_schools CURSOR FOR SELECT id FROM schools;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done_school = TRUE;

    -- Pega o primeiro usuário admin disponível para ser o "criador"
    SELECT id INTO v_user_id FROM users ORDER BY id ASC LIMIT 1;

    IF v_user_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro: É necessário ter pelo menos 1 Usuário cadastrado.';
    END IF;

    -- Loop Escolas
    OPEN cur_schools;
    
    school_loop: LOOP
        FETCH cur_schools INTO v_school_id;
        IF done_school THEN
            LEAVE school_loop;
        END IF;

        -- Bloco Modalidades
        block_mods: BEGIN
            DECLARE done_mod INT DEFAULT FALSE;
            DECLARE v_modality_id INT;
            DECLARE cur_mods CURSOR FOR SELECT id FROM modalities;
            DECLARE CONTINUE HANDLER FOR NOT FOUND SET done_mod = TRUE;

            OPEN cur_mods;
            mod_loop: LOOP
                FETCH cur_mods INTO v_modality_id;
                IF done_mod THEN
                    LEAVE mod_loop;
                END IF;

                -- Bloco Categorias (Novo Loop Aninhado)
                block_cats: BEGIN
                    DECLARE done_cat INT DEFAULT FALSE;
                    DECLARE v_category_id INT;
                    DECLARE v_reg_id INT;
                    DECLARE v_student_id INT;
                    DECLARE i INT;
                    -- Usando nome diferente para o handler ou lógica de reset manual
                    DECLARE cur_cats CURSOR FOR SELECT id FROM categories;
                    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done_cat = TRUE;

                    OPEN cur_cats;
                    cat_loop: LOOP
                        FETCH cur_cats INTO v_category_id;
                        IF done_cat THEN
                            LEAVE cat_loop;
                        END IF;

                        -- Verifica duplicidade e INSERE
                        IF NOT EXISTS (
                            SELECT 1 FROM registrations 
                            WHERE school_id = v_school_id 
                            AND modality_id = v_modality_id 
                            AND category_id = v_category_id
                            AND gender = 'M'
                        ) THEN
                            -- Criar Equipe
                            INSERT INTO registrations (school_id, modality_id, category_id, gender, status, created_by_user_id)
                            VALUES (v_school_id, v_modality_id, v_category_id, 'M', 'approved', v_user_id);
                            
                            SET v_reg_id = LAST_INSERT_ID();
                            
                            -- Criar 5 Atletas Fictícios
                            SET i = 1;
                            WHILE i <= 5 DO
                                INSERT INTO students (name, document_number, birth_date, gender, school_id)
                                VALUES (
                                    CONCAT('Atleta Teste ', v_reg_id, '-', i),
                                    CONCAT('DOC-', v_reg_id, '-', i),
                                    DATE_SUB(CURDATE(), INTERVAL 12 YEAR),
                                    'M',
                                    v_school_id
                                );
                                
                                SET v_student_id = LAST_INSERT_ID();
                                
                                INSERT INTO enrollments (student_id, registration_id)
                                VALUES (v_student_id, v_reg_id);
                                
                                SET i = i + 1;
                            END WHILE;
                        END IF;

                    END LOOP;
                    CLOSE cur_cats;
                END block_cats;

            END LOOP;
            CLOSE cur_mods;
        END block_mods;

    END LOOP;
    
    CLOSE cur_schools;
    
    SELECT 'Dados de teste (FULL COVERAGE) gerados com sucesso!' AS Message;
END //

DELIMITER ;

-- Executar
CALL GenerateMassTestData();
