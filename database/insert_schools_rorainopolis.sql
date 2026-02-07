-- ============================================
-- INSERÇÃO DE ESCOLAS DE RORAINÓPOLIS
-- ============================================

-- Inserir apenas se não existir (evita duplicação)
INSERT INTO schools (name, municipality, address, phone, director, email)
SELECT * FROM (
    SELECT 'Escola de Ensino Infantil e Fundamental Em Tempo Integral Vó Hilda Klenniving da Silva' as name, 
           'Rorainópolis' as municipality, 
           'Rua Principal, Centro' as address, 
           '(95) 3000-0001' as phone, 
           'Maria Silva Santos' as director, 
           'vohilda@educacao.rr.gov.br' as email
    UNION ALL
    SELECT 'Escola de Ensino Infantil e Fundamental Josefa da Silva Gomes', 'Rorainópolis', 'Av. Central, 100', '(95) 3000-0002', 'João Pedro Oliveira', 'josefa@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola de Ensino Infantil e Fundamental Ordalha Araújo de Lima', 'Rorainópolis', 'Rua das Flores, 200', '(95) 3000-0003', 'Ana Paula Costa', 'ordalha@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Infantil Creche Boneca Emília', 'Rorainópolis', 'Rua Monteiro Lobato, 50', '(95) 3000-0004', 'Carla Mendes Lima', 'bonecaemilia@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Educação Infantil e Ensino Fundamental Professor Eulina Paulina de Oliveira Castelo Branco', 'Rorainópolis', 'Av. Educação, 300', '(95) 3000-0005', 'Roberto Alves Souza', 'eulina@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Educação Infantil e Ensino Fundamental Professora Sueli Caetano de Oliveira', 'Rorainópolis', 'Rua do Saber, 150', '(95) 3000-0006', 'Fernanda Lima Santos', 'sueli@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Fundamental Joselma Lima de Souza', 'Rorainópolis', 'Rua da Escola, 250', '(95) 3000-0007', 'Carlos Eduardo Silva', 'joselma@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Fundamental Professor Hildemar Pereira de Figueiredo', 'Rorainópolis', 'Av. dos Professores, 400', '(95) 3000-0008', 'Mariana Costa Rocha', 'hildemar@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil Creche Professora Andreza da Conceição Silva Rufino', 'Rorainópolis', 'Rua das Crianças, 80', '(95) 3000-0009', 'Paula Regina Dias', 'andreza@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Amélia Batista dos Santos', 'Rorainópolis', 'Rua Amélia, 120', '(95) 3000-0010', 'José Carlos Mendes', 'amelia@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Bernardo Zidório de Oliveira', 'Rorainópolis', 'Av. Bernardo, 180', '(95) 3000-0011', 'Luciana Ferreira', 'bernardo@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Duque de Caxias', 'Rorainópolis', 'Praça Duque de Caxias, s/n', '(95) 3000-0012', 'Ricardo Almeida', 'duquecaxias@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Francisco de Assis', 'Rorainópolis', 'Rua São Francisco, 90', '(95) 3000-0013', 'Beatriz Santos', 'francisco@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental João Maia da Silva', 'Rorainópolis', 'Av. João Maia, 220', '(95) 3000-0014', 'Antônio Pereira', 'joaomaia@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental João Rodrigues de Sousa', 'Rorainópolis', 'Rua João Rodrigues, 160', '(95) 3000-0015', 'Silvia Martins', 'joaorodrigues@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Joaquim Baima Nogueira', 'Rorainópolis', 'Av. Joaquim Baima, 270', '(95) 3000-0016', 'Marcos Vinícius', 'joaquim@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental José Alves Barbosa', 'Rorainópolis', 'Rua José Alves, 130', '(95) 3000-0017', 'Patrícia Gomes', 'josealves@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental José Lírio dos Reis', 'Rorainópolis', 'Av. José Lírio, 190', '(95) 3000-0018', 'Rafael Costa', 'joselirio@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Manoel Ferreira dos Santos', 'Rorainópolis', 'Rua Manoel Ferreira, 240', '(95) 3000-0019', 'Juliana Ribeiro', 'manoel@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Pedro Moleta', 'Rorainópolis', 'Av. Pedro Moleta, 310', '(95) 3000-0020', 'Eduardo Santos', 'pedromoleta@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Profª Maria Correa Guedes', 'Rorainópolis', 'Rua Maria Correa, 140', '(95) 3000-0021', 'Vanessa Lima', 'mariacorrea@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Profª Mariza da Gama Figueiredo', 'Rorainópolis', 'Av. Mariza Gama, 280', '(95) 3000-0022', 'Rodrigo Alves', 'mariza@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Professor Jean de Sousa Oliveira', 'Rorainópolis', 'Rua Jean Oliveira, 170', '(95) 3000-0023', 'Camila Souza', 'jean@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Professora Terezinha de Jesus', 'Rorainópolis', 'Av. Terezinha, 230', '(95) 3000-0024', 'Bruno Cardoso', 'terezinha@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Santa Terezinha', 'Rorainópolis', 'Praça Santa Terezinha, 60', '(95) 3000-0025', 'Adriana Moreira', 'santaterezinha@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Tancredo Neves', 'Rorainópolis', 'Rua Tancredo Neves, 320', '(95) 3000-0026', 'Felipe Nascimento', 'tancredo@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Teodorico Nascimento', 'Rorainópolis', 'Av. Teodorico, 210', '(95) 3000-0027', 'Larissa Freitas', 'teodorico@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Venâncio José de Souza', 'Rorainópolis', 'Rua Venâncio, 150', '(95) 3000-0028', 'Gustavo Rocha', 'venancio@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Vinícius de Morais', 'Rorainópolis', 'Av. Vinícius de Morais, 260', '(95) 3000-0029', 'Isabela Costa', 'vinicius@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Violeta Nakai', 'Rorainópolis', 'Rua Violeta Nakai, 110', '(95) 3000-0030', 'Thiago Mendes', 'violeta@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Vovó Tetinha', 'Rorainópolis', 'Rua da Vovó, 70', '(95) 3000-0031', 'Renata Silva', 'vovotetinha@educacao.rr.gov.br'
    UNION ALL
    SELECT 'Escola Municipal de Ensino Infantil e Fundamental Zildeth Puga Rocha', 'Rorainópolis', 'Av. Zildeth Puga, 290', '(95) 3000-0032', 'Leonardo Dias', 'zildeth@educacao.rr.gov.br'
) AS new_schools
WHERE NOT EXISTS (
    SELECT 1 FROM schools WHERE schools.name = new_schools.name
);

-- Verificação: Listar escolas de Rorainópolis
SELECT id, name, municipality, director, phone 
FROM schools 
WHERE municipality = 'Rorainópolis' 
ORDER BY name;
