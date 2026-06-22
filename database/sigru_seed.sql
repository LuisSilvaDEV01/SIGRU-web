-- =====================================================================
-- SIGRU - Script de povoamento COMPLETO (dados fictícios)
-- Inclui cardápios para HOJE e para AMANHÃ (data da apresentação)
-- =====================================================================
USE sigru;

-- Desliga checagem de FK temporariamente para permitir TRUNCATE em qualquer ordem
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE recarga_historico;
TRUNCATE TABLE refeicao;
TRUNCATE TABLE itens_fila;
TRUNCATE TABLE itens_receita_produto;
TRUNCATE TABLE itens_cardapio_receita;
TRUNCATE TABLE conta_receber;
TRUNCATE TABLE receita;
TRUNCATE TABLE movimento_estoque;
TRUNCATE TABLE estoque;
TRUNCATE TABLE carteira_digital;
TRUNCATE TABLE itens_cardapio_tipo_categorias_valores;
TRUNCATE TABLE itens_cardapio;
TRUNCATE TABLE produto;
TRUNCATE TABLE usuario;
TRUNCATE TABLE conta_pagar;
TRUNCATE TABLE fornecedores;
TRUNCATE TABLE funcionario;
TRUNCATE TABLE fila;
TRUNCATE TABLE cardapio;
TRUNCATE TABLE itens_cardapio_tipo;
TRUNCATE TABLE tipo_produto;
TRUNCATE TABLE categoria_usuario;
TRUNCATE TABLE bairro;
TRUNCATE TABLE cidade;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- CIDADE
-- =====================================================================
INSERT INTO cidade (descricao, uf, codigo_ibge) VALUES
('Montes Claros', 'MG', '3143302'),
('Bocaiúva',       'MG', '3107307'),
('Janaúba',        'MG', '3136702'),
('Salinas',        'MG', '3157039'),
('Pirapora',       'MG', '3151008');

-- =====================================================================
-- BAIRRO
-- =====================================================================
INSERT INTO bairro (id_cidade, descricao) VALUES
(1, 'Centro'),
(1, 'Ibituruna'),
(1, 'Major Prates'),
(1, 'Todos os Santos'),
(1, 'Vila Atlântida'),
(1, 'Cândida Câmara'),
(1, 'São José'),
(2, 'Centro'),
(3, 'Centro'),
(4, 'Centro'),
(5, 'Centro');

-- =====================================================================
-- CATEGORIA_USUARIO
-- =====================================================================
INSERT INTO categoria_usuario (descricao) VALUES
('Aluno'),
('Bolsista'),
('Servidor'),
('Visitante');

-- =====================================================================
-- TIPO_PRODUTO
-- =====================================================================
INSERT INTO tipo_produto (descricao_tipo) VALUES
('Carnes'),
('Laticínios'),
('Hortifruti'),
('Limpeza'),
('Cereais e Grãos'),
('Temperos e Condimentos'),
('Bebidas'),
('Descartáveis');

-- =====================================================================
-- ITENS_CARDAPIO_TIPO
-- =====================================================================
INSERT INTO itens_cardapio_tipo (descricao) VALUES
('Prato Livre'),
('Marmitex'),
('Marmitex Fit'),
('Marmitex Vegetariano'),
('Sobremesa');

-- =====================================================================
-- CARDAPIO (HOJE e AMANHÃ — data da apresentação)
-- =====================================================================
INSERT INTO cardapio (data_servico, turno) VALUES
(CURDATE(), 'Almoço'),                                  -- id 1
(CURDATE(), 'Jantar'),                                   -- id 2
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Almoço'),         -- id 3 (apresentação)
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Jantar'),         -- id 4 (apresentação)
(DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Almoço');         -- id 5

-- =====================================================================
-- FILA
-- =====================================================================
INSERT INTO fila (tipo_fila) VALUES
('Preferencial'),
('Prato'),
('Marmitex');

-- =====================================================================
-- FUNCIONARIO (PK = CPF)
-- =====================================================================
INSERT INTO funcionario (cpf, nome_funcionario, cargo, privilegios, id_bairro, endereco) VALUES
('11122233344', 'Carlos Eduardo Reis',     'Cozinheiro Chefe', 'ESTOQUE,CARDAPIO',     1, 'Rua das Flores, 100'),
('22233344455', 'Fernanda Lima Souza',     'Atendente',         'CAIXA,FILA',           2, 'Av. Brasil, 250'),
('33344455566', 'Roberto Almeida Costa',   'Administrador',     'TOTAL',                3, 'Rua Goiás, 45'),
('44455566677', 'Juliana Pereira Santos',  'Cozinheira',        'ESTOQUE,CARDAPIO',     4, 'Rua Minas Gerais, 12'),
('55566677788', 'Marcos Vinícius Oliveira','Atendente',         'CAIXA,FILA',           5, 'Rua Bahia, 88'),
('66677788899', 'Patrícia Gomes Ribeiro',  'Nutricionista',     'CARDAPIO,RELATORIOS',  6, 'Rua Pará, 33'),
('77788899900', 'Eduardo Henrique Lima',   'Auxiliar de Cozinha','ESTOQUE',             7, 'Rua Ceará, 21');

-- =====================================================================
-- FORNECEDORES (PK = CNPJ)
-- =====================================================================
INSERT INTO fornecedores (cnpj, razao_social, telefone, id_bairro, endereco) VALUES
('12345678000190', 'Distribuidora Alimentos MG Ltda',  '38999991111', 8,  'Av. Industrial, 500'),
('98765432000110', 'Hortifruti Vale do Verde Ltda',     '38988882222', 9,  'Rua das Hortas, 12'),
('45612378000155', 'Laticínios Serra Azul S/A',         '38977773333', 1,  'Rua dos Laticínios, 78'),
('32165498000122', 'Frigorífico Boi Forte Ltda',        '38966664444', 10, 'Rod. MG-122, Km 5'),
('15975346000188', 'Distribuidora de Limpeza Brilho',   '38955556666', 11, 'Av. Sanitária, 200'),
('75395146000133', 'Padaria e Doceria Pão Quente',      '38944447777', 2,  'Rua do Pão, 33'),
('85274196000177', 'Embalagens Descartáveis MC',        '38933338888', 3,  'Rua Industrial, 150');

-- =====================================================================
-- USUARIO
-- =====================================================================
INSERT INTO usuario (matricula, nome_completo, id_categoria, id_bairro, endereco) VALUES
(202310001, 'Luís Otávio de Souza e Silva', 1, 1, 'Rua A, 10'),
(202310002, 'Matheus Gonçalves Dias',       1, 2, 'Rua B, 20'),
(202310003, 'André Lucas Gomes Lima',       2, 3, 'Rua C, 30'),
(20251001,  'Leandro Clementino de Almeida',3, 1, 'Rua D, 40'),
(202410010, 'Ana Beatriz Pereira',          4, 2, 'Rua E, 50'),
(202410011, 'Carlos Eduardo Martins',       1, 3, 'Rua F, 60'),
(202410012, 'Beatriz Souza Carvalho',       2, 4, 'Rua G, 70'),
(202410013, 'Rafael Augusto Silva',         1, 5, 'Rua H, 80'),
(202410014, 'Camila Fernandes Rocha',       1, 6, 'Rua I, 90'),
(202410015, 'Gustavo Henrique Pinto',       2, 7, 'Rua J, 100'),
(20251002,  'Maria das Graças Nunes',       3, 1, 'Rua K, 110'),
(20251003,  'José Antônio Ferreira',        3, 2, 'Rua L, 120'),
(202410016, 'Larissa Mendes Castro',        1, 3, 'Rua M, 130'),
(202410017, 'Pedro Henrique Tavares',       1, 1, 'Rua N, 140'),
(202410018, 'Juliana Costa Ribeiro',        2, 2, 'Rua O, 150'),
(202410019, 'Felipe Augusto Ramos',         1, 4, 'Rua P, 160'),
(202410020, 'Vanessa Lima Souza',           4, 5, 'Rua Q, 170'),
(20251004,  'Antônio Carlos Brandão',       3, 6, 'Rua R, 180');

-- =====================================================================
-- PRODUTO
-- =====================================================================
INSERT INTO produto (nome_produto, unidade_medida, id_tipo_produto) VALUES
('Arroz Agulhinha 5KG',     'KG', 5),
('Feijão Carioca 1KG',      'KG', 5),
('Peito de Frango',         'KG', 1),
('Carne Bovina Patinho',    'KG', 1),
('Linguiça Toscana',        'KG', 1),
('Leite Integral',          'LT', 2),
('Queijo Mussarela',        'KG', 2),
('Manteiga',                'KG', 2),
('Alface Crespa',           'UN', 3),
('Tomate',                  'KG', 3),
('Cebola',                  'KG', 3),
('Cenoura',                 'KG', 3),
('Batata Inglesa',          'KG', 3),
('Detergente Neutro',       'LT', 4),
('Sabão em Pó',             'KG', 4),
('Álcool 70%',              'LT', 4),
('Sal Refinado',            'KG', 6),
('Óleo de Soja',            'LT', 6),
('Alho',                    'KG', 6),
('Suco de Laranja 1L',      'LT', 7),
('Refrigerante Lata',       'UN', 7),
('Copo Descartável 200ml',  'UN', 8),
('Embalagem Marmitex',      'UN', 8),
('Guardanapo',              'UN', 8);

-- =====================================================================
-- ITENS_CARDAPIO
-- =====================================================================
INSERT INTO itens_cardapio (id_cardapio, id_tipo, descricao) VALUES
-- Hoje - Almoço (id_cardapio 1)
(1, 1, 'Arroz, feijão, frango grelhado, salada e farofa'),
(1, 2, 'Marmitex tradicional: arroz, feijão, frango e salada'),
(1, 5, 'Pudim de leite'),
-- Hoje - Jantar (id_cardapio 2)
(2, 1, 'Arroz, feijão, carne moída e legumes refogados'),
(2, 3, 'Marmitex fit: arroz integral, frango grelhado e legumes no vapor'),
-- Amanhã - Almoço (id_cardapio 3) — DIA DA APRESENTAÇÃO
(3, 1, 'Arroz, feijão tropeiro, linguiça acebolada e couve'),
(3, 2, 'Marmitex tradicional: arroz, feijão, linguiça e salada'),
(3, 4, 'Marmitex vegetariano: arroz, feijão, legumes grelhados e tofu'),
(3, 5, 'Gelatina colorida'),
-- Amanhã - Jantar (id_cardapio 4)
(4, 1, 'Arroz, feijão, carne de panela e purê de batata'),
(4, 3, 'Marmitex fit: arroz integral, peito de frango e salada verde'),
-- Depois de amanhã - Almoço (id_cardapio 5)
(5, 1, 'Arroz, feijão, frango à parmegiana e salada'),
(5, 2, 'Marmitex tradicional: arroz, feijão, frango à parmegiana');

-- =====================================================================
-- ITENS_CARDAPIO_TIPO_CATEGORIAS_VALORES
-- =====================================================================
INSERT INTO itens_cardapio_tipo_categorias_valores (id_tipo, id_categoria, valor) VALUES
(1, 1, 8.50),   -- Prato Livre / Aluno
(1, 2, 2.00),   -- Prato Livre / Bolsista
(1, 3, 13.00),  -- Prato Livre / Servidor
(1, 4, 17.00),  -- Prato Livre / Visitante
(2, 1, 12.00),  -- Marmitex / Aluno
(2, 2, 6.00),   -- Marmitex / Bolsista
(2, 3, 16.00),  -- Marmitex / Servidor
(2, 4, 20.00),  -- Marmitex / Visitante
(3, 1, 14.00),  -- Marmitex Fit / Aluno
(3, 3, 18.00),  -- Marmitex Fit / Servidor
(3, 4, 22.00),  -- Marmitex Fit / Visitante
(4, 1, 13.00),  -- Marmitex Vegetariano / Aluno
(4, 3, 17.00),  -- Marmitex Vegetariano / Servidor
(5, 1, 3.00),   -- Sobremesa / Aluno
(5, 2, 1.00),   -- Sobremesa / Bolsista
(5, 3, 4.00),   -- Sobremesa / Servidor
(5, 4, 5.00);   -- Sobremesa / Visitante

-- =====================================================================
-- CARTEIRA_DIGITAL
-- =====================================================================
INSERT INTO carteira_digital (id_usuario, saldo) VALUES
(1, 50.00), (2, 20.00), (3, 35.50), (4, 100.00), (5, 10.00),
(6, 42.00), (7, 28.00), (8, 60.00), (9, 15.50), (10, 33.00),
(11, 80.00), (12, 95.00), (13, 22.00), (14, 18.00), (15, 47.00),
(16, 5.00),  (17, 25.00), (18, 60.00);

-- =====================================================================
-- ESTOQUE
-- =====================================================================
INSERT INTO estoque (quantidade, data_atualizacao, id_produto) VALUES
(200, NOW(), 1),  (150, NOW(), 2),  (80, NOW(), 3),  (60, NOW(), 4),
(45, NOW(), 5),   (120, NOW(), 6),  (35, NOW(), 7),  (18, NOW(), 8),
(60, NOW(), 9),   (90, NOW(), 10),  (70, NOW(), 11), (55, NOW(), 12),
(110, NOW(), 13), (40, NOW(), 14), (30, NOW(), 15), (25, NOW(), 16),
(50, NOW(), 17),  (65, NOW(), 18), (20, NOW(), 19), (40, NOW(), 20),
(85, NOW(), 21),  (300, NOW(), 22),(500, NOW(), 23),(400, NOW(), 24);

-- =====================================================================
-- MOVIMENTO_ESTOQUE
-- =====================================================================
INSERT INTO movimento_estoque (tipo_movimento, quantidade_mov, data_movimento, id_produto, cnpj_fornecedor) VALUES
('ENTRADA', 200, DATE_SUB(NOW(), INTERVAL 10 DAY), 1,  '12345678000190'),
('ENTRADA', 150, DATE_SUB(NOW(), INTERVAL 10 DAY), 2,  '12345678000190'),
('ENTRADA', 100, DATE_SUB(NOW(), INTERVAL 9 DAY),  3,  '32165498000122'),
('ENTRADA', 70,  DATE_SUB(NOW(), INTERVAL 9 DAY),  4,  '32165498000122'),
('ENTRADA', 50,  DATE_SUB(NOW(), INTERVAL 9 DAY),  5,  '32165498000122'),
('ENTRADA', 150, DATE_SUB(NOW(), INTERVAL 8 DAY),  6,  '45612378000155'),
('ENTRADA', 40,  DATE_SUB(NOW(), INTERVAL 8 DAY),  7,  '45612378000155'),
('ENTRADA', 20,  DATE_SUB(NOW(), INTERVAL 8 DAY),  8,  '45612378000155'),
('ENTRADA', 70,  DATE_SUB(NOW(), INTERVAL 7 DAY),  9,  '98765432000110'),
('ENTRADA', 100, DATE_SUB(NOW(), INTERVAL 7 DAY),  10, '98765432000110'),
('ENTRADA', 80,  DATE_SUB(NOW(), INTERVAL 7 DAY),  11, '98765432000110'),
('ENTRADA', 60,  DATE_SUB(NOW(), INTERVAL 7 DAY),  12, '98765432000110'),
('ENTRADA', 120, DATE_SUB(NOW(), INTERVAL 7 DAY),  13, '98765432000110'),
('ENTRADA', 50,  DATE_SUB(NOW(), INTERVAL 6 DAY),  14, '15975346000188'),
('ENTRADA', 35,  DATE_SUB(NOW(), INTERVAL 6 DAY),  15, '15975346000188'),
('ENTRADA', 30,  DATE_SUB(NOW(), INTERVAL 6 DAY),  16, '15975346000188'),
('ENTRADA', 60,  DATE_SUB(NOW(), INTERVAL 5 DAY),  17, '12345678000190'),
('ENTRADA', 80,  DATE_SUB(NOW(), INTERVAL 5 DAY),  18, '12345678000190'),
('ENTRADA', 25,  DATE_SUB(NOW(), INTERVAL 5 DAY),  19, '98765432000110'),
('ENTRADA', 50,  DATE_SUB(NOW(), INTERVAL 4 DAY),  20, '75395146000133'),
('ENTRADA', 100, DATE_SUB(NOW(), INTERVAL 4 DAY),  21, '75395146000133'),
('ENTRADA', 300, DATE_SUB(NOW(), INTERVAL 3 DAY),  22, '85274196000177'),
('ENTRADA', 500, DATE_SUB(NOW(), INTERVAL 3 DAY),  23, '85274196000177'),
('ENTRADA', 400, DATE_SUB(NOW(), INTERVAL 3 DAY),  24, '85274196000177'),
('SAIDA',   20,  DATE_SUB(NOW(), INTERVAL 2 DAY),  1,  NULL),
('SAIDA',   15,  DATE_SUB(NOW(), INTERVAL 2 DAY),  2,  NULL),
('SAIDA',   10,  DATE_SUB(NOW(), INTERVAL 1 DAY),  3,  NULL),
('SAIDA',   8,   DATE_SUB(NOW(), INTERVAL 1 DAY),  9,  NULL),
('SAIDA',   5,   NOW(),                            6,  NULL);

-- =====================================================================
-- RECEITA
-- =====================================================================
INSERT INTO receita (nome, rendimento, preparo) VALUES
('Arroz Branco',          50.00, 'Refogar o arroz com alho e cebola, adicionar água e cozinhar até secar.'),
('Feijão Tropeiro',       40.00, 'Cozinhar o feijão, refogar com bacon, linguiça, couve e farinha de mandioca.'),
('Frango Grelhado',       30.00, 'Temperar o peito de frango e grelhar em fogo médio até dourar.'),
('Linguiça Acebolada',    25.00, 'Grelhar a linguiça em rodelas e refogar com cebola até dourar.'),
('Salada de Alface',      50.00, 'Higienizar e cortar a alface, temperar com sal e azeite.'),
('Carne de Panela',       35.00, 'Cozinhar a carne em fogo baixo com temperos até ficar macia.'),
('Purê de Batata',        40.00, 'Cozinhar e amassar as batatas, adicionar leite e manteiga.'),
('Legumes Grelhados',     30.00, 'Grelhar cenoura, abobrinha e pimentão em fatias.');

-- =====================================================================
-- ITENS_RECEITA_PRODUTO (ficha técnica)
-- =====================================================================
INSERT INTO itens_receita_produto (qtd_produto, id_receita, id_produto) VALUES
(5.00,  1, 1),   -- Arroz Branco usa Arroz
(8.00,  2, 2),   -- Feijão Tropeiro usa Feijão
(15.00, 3, 3),   -- Frango Grelhado usa Peito de Frango
(10.00, 4, 5),   -- Linguiça Acebolada usa Linguiça
(2.00,  4, 11),  -- Linguiça Acebolada usa Cebola
(10.00, 5, 9),   -- Salada usa Alface
(2.00,  5, 10),  -- Salada usa Tomate
(12.00, 6, 4),   -- Carne de Panela usa Carne Bovina
(10.00, 7, 13),  -- Purê de Batata usa Batata
(2.00,  7, 6),   -- Purê de Batata usa Leite
(5.00,  8, 12),  -- Legumes Grelhados usa Cenoura
(3.00,  8, 10);  -- Legumes Grelhados usa Tomate

-- =====================================================================
-- ITENS_CARDAPIO_RECEITA
-- =====================================================================
INSERT INTO itens_cardapio_receita (qtd_necessaria, id_itens_cardapio, id_receita) VALUES
(1.00, 1, 1),   -- Hoje Almoço Prato Livre: Arroz
(1.00, 1, 3),   -- Hoje Almoço Prato Livre: Frango Grelhado
(1.00, 1, 5),   -- Hoje Almoço Prato Livre: Salada
(1.00, 2, 1),   -- Hoje Almoço Marmitex: Arroz
(1.00, 2, 3),   -- Hoje Almoço Marmitex: Frango Grelhado
(1.00, 6, 1),   -- Amanhã Almoço Prato Livre: Arroz
(1.00, 6, 2),   -- Amanhã Almoço Prato Livre: Feijão Tropeiro
(1.00, 6, 4),   -- Amanhã Almoço Prato Livre: Linguiça Acebolada
(1.00, 7, 1),   -- Amanhã Almoço Marmitex: Arroz
(1.00, 7, 4);   -- Amanhã Almoço Marmitex: Linguiça Acebolada

-- =====================================================================
-- ITENS_FILA
-- =====================================================================
INSERT INTO itens_fila (id_usuario, id_fila, horario_inscricao, situacao) VALUES
(1,  2, CONCAT(CURDATE(), ' 11:30:00'), 'finalizado'),
(3,  1, CONCAT(CURDATE(), ' 11:45:00'), 'finalizado'),
(4,  1, CONCAT(CURDATE(), ' 11:50:00'), 'finalizado'),
(2,  2, NOW(), 'em espera'),
(5,  3, NOW(), 'em espera'),
(6,  2, NOW(), 'em espera'),
(7,  1, NOW(), 'em espera');

-- =====================================================================
-- REFEICAO (histórico dos últimos dias + hoje)
-- =====================================================================
INSERT INTO refeicao (id_usuario, id_itens_cardapio, id_categoria_valores, valor, horario_entrada, horario_saida) VALUES
(1,  1, 1, 8.50,  CONCAT(CURDATE(), ' 11:30:00'), CONCAT(CURDATE(), ' 12:05:00')),
(3,  1, 2, 2.00,  CONCAT(CURDATE(), ' 11:45:00'), CONCAT(CURDATE(), ' 12:10:00')),
(4,  2, 7, 16.00, CONCAT(CURDATE(), ' 11:50:00'), NULL),
(6,  1, 1, 8.50,  DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 30 MINUTE),
(7,  1, 2, 2.00,  DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 25 MINUTE),
(8,  1, 1, 8.50,  DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 30 MINUTE),
(9,  1, 1, 8.50,  DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 28 MINUTE),
(10, 1, 2, 2.00,  DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 35 MINUTE),
(11, 1, 3, 13.00, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 30 MINUTE),
(12, 1, 3, 13.00, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 32 MINUTE),
(13, 1, 1, 8.50,  DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 27 MINUTE);

-- =====================================================================
-- RECARGA_HISTORICO
-- =====================================================================
INSERT INTO recarga_historico (id_carteira, valor, data) VALUES
(1, 30.00,  DATE_SUB(NOW(), INTERVAL 9 DAY)),
(1, 20.00,  DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 100.00, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(6, 42.00,  DATE_SUB(NOW(), INTERVAL 6 DAY)),
(7, 28.00,  DATE_SUB(NOW(), INTERVAL 5 DAY)),
(8, 60.00,  DATE_SUB(NOW(), INTERVAL 5 DAY)),
(11, 80.00, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(12, 95.00, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 20.00,  DATE_SUB(NOW(), INTERVAL 3 DAY)),
(15, 47.00, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- =====================================================================
-- CONTA_PAGAR
-- =====================================================================
INSERT INTO conta_pagar (valor, data_vencimento, status, origem, cnpj_fornecedor) VALUES
(1500.00, DATE_ADD(CURDATE(), INTERVAL 5 DAY),  'PENDENTE', 'FORNECEDOR', '12345678000190'),
(450.00,  DATE_ADD(CURDATE(), INTERVAL 8 DAY),  'PENDENTE', 'FORNECEDOR', '98765432000110'),
(890.00,  DATE_ADD(CURDATE(), INTERVAL 3 DAY),  'PENDENTE', 'FORNECEDOR', '32165498000122'),
(320.00,  DATE_SUB(CURDATE(), INTERVAL 2 DAY),  'PAGO',     'CEMIG', NULL),
(180.00,  DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'PAGO',     'COPASA', NULL),
(610.00,  DATE_ADD(CURDATE(), INTERVAL 1 DAY),  'PENDENTE', 'FORNECEDOR', '45612378000155'),
(250.00,  DATE_SUB(CURDATE(), INTERVAL 5 DAY),  'PAGO',     'FORNECEDOR', '15975346000188'),
(95.00,   DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'PENDENTE', 'MANUTENCAO', NULL);

-- =====================================================================
-- CONTA_RECEBER
-- =====================================================================
INSERT INTO conta_receber (valor, data_prevista, origem) VALUES
(15000.00, DATE_ADD(CURDATE(), INTERVAL 9 DAY), 'Subsídio Universidade'),
(850.00,   CURDATE(), 'Recargas Consolidadas'),
(620.00,   DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Recargas Consolidadas'),
(12000.00, DATE_ADD(CURDATE(), INTERVAL 30 DAY),'Subsídio Universidade');
