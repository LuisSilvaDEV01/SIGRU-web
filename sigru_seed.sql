-- =====================================================================
-- SIGRU - Script de povoamento (dados fictícios)
-- =====================================================================
USE sigru;

-- CIDADE
INSERT INTO cidade (descricao, uf, codigo_ibge) VALUES
('Montes Claros', 'MG', '3143302'),
('Bocaiúva', 'MG', '3107307'),
('Janaúba', 'MG', '3136702');

-- BAIRRO
INSERT INTO bairro (id_cidade, descricao) VALUES
(1, 'Centro'),
(1, 'Ibituruna'),
(1, 'Major Prates'),
(2, 'Centro'),
(3, 'Centro');

-- CATEGORIA_USUARIO
INSERT INTO categoria_usuario (descricao) VALUES
('Aluno'),
('Bolsista'),
('Servidor'),
('Visitante');

-- TIPO_PRODUTO
INSERT INTO tipo_produto (descricao_tipo) VALUES
('Carnes'),
('Laticínios'),
('Hortifruti'),
('Limpeza'),
('Cereais e Grãos');

-- ITENS_CARDAPIO_TIPO
INSERT INTO itens_cardapio_tipo (descricao) VALUES
('Prato Livre'),
('Marmitex'),
('Marmitex Fit');

-- CARDAPIO (datas relativas ao dia atual para funcionar sempre)
INSERT INTO cardapio (data_servico, turno) VALUES
(CURDATE(), 'Almoço'),
(CURDATE(), 'Jantar'),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Almoço');

-- FILA
INSERT INTO fila (tipo_fila) VALUES
('Preferencial'),
('Prato'),
('Marmitex');

-- FUNCIONARIO (PK = CPF)
INSERT INTO funcionario (cpf, nome_funcionario, cargo, privilegios, id_bairro, endereco) VALUES
('11122233344', 'Carlos Eduardo Reis', 'Cozinheiro Chefe', 'ESTOQUE,CARDAPIO', 1, 'Rua das Flores, 100'),
('22233344455', 'Fernanda Lima Souza', 'Atendente', 'CAIXA,FILA', 2, 'Av. Brasil, 250'),
('33344455566', 'Roberto Almeida Costa', 'Administrador', 'TOTAL', 3, 'Rua Goiás, 45');

-- FORNECEDORES (PK = CNPJ)
INSERT INTO fornecedores (cnpj, razao_social, telefone, id_bairro, endereco) VALUES
('12345678000190', 'Distribuidora Alimentos MG Ltda', '38999991111', 4, 'Av. Industrial, 500'),
('98765432000110', 'Hortifruti Vale do Verde Ltda', '38988882222', 5, 'Rua das Hortas, 12'),
('45612378000155', 'Laticínios Serra Azul S/A', '38977773333', 1, 'Rua dos Laticínios, 78');

-- USUARIO
INSERT INTO usuario (matricula, nome_completo, id_categoria, id_bairro, endereco) VALUES
(202310001, 'Luís Otávio de Souza e Silva', 1, 1, 'Rua A, 10'),
(202310002, 'Matheus Gonçalves Dias', 1, 2, 'Rua B, 20'),
(202310003, 'André Lucas Gomes Lima', 2, 3, 'Rua C, 30'),
(20251001, 'Leandro Clementino de Almeida', 3, 1, 'Rua D, 40'),
(202410010, 'Ana Beatriz Pereira', 4, 2, 'Rua E, 50');

-- PRODUTO
INSERT INTO produto (nome_produto, unidade_medida, id_tipo_produto) VALUES
('Arroz Agulhinha 5KG', 'KG', 5),
('Feijão Carioca 1KG', 'KG', 5),
('Peito de Frango', 'KG', 1),
('Leite Integral', 'LT', 2),
('Alface Crespa', 'UN', 3),
('Detergente Neutro', 'LT', 4);

-- ITENS_CARDAPIO
INSERT INTO itens_cardapio (id_cardapio, id_tipo, descricao) VALUES
(1, 1, NULL),
(1, 2, 'Marmitex: arroz, feijão, frango grelhado e salada'),
(2, 1, NULL),
(3, 1, NULL),
(3, 3, 'Marmitex Fit: arroz integral, frango e legumes');

-- ITENS_CARDAPIO_TIPO_CATEGORIAS_VALORES
INSERT INTO itens_cardapio_tipo_categorias_valores (id_tipo, id_categoria, valor) VALUES
(1, 1, 8.50),   -- Prato Livre / Aluno
(1, 2, 2.00),   -- Prato Livre / Bolsista
(1, 3, 13.00),  -- Prato Livre / Servidor
(1, 4, 17.00),  -- Prato Livre / Visitante
(2, 1, 12.00),  -- Marmitex / Aluno
(2, 4, 20.00),  -- Marmitex / Visitante
(3, 1, 14.00);  -- Marmitex Fit / Aluno

-- CARTEIRA_DIGITAL
INSERT INTO carteira_digital (id_usuario, saldo) VALUES
(1, 50.00),
(2, 20.00),
(3, 35.50),
(4, 100.00),
(5, 10.00);

-- ESTOQUE
INSERT INTO estoque (quantidade, data_atualizacao, id_produto) VALUES
(200, '2026-06-14 08:00:00', 1),
(150, '2026-06-14 08:00:00', 2),
(80,  '2026-06-14 08:00:00', 3),
(120, '2026-06-14 08:00:00', 4),
(60,  '2026-06-14 08:00:00', 5),
(40,  '2026-06-14 08:00:00', 6);

-- MOVIMENTO_ESTOQUE
INSERT INTO movimento_estoque (tipo_movimento, quantidade_mov, data_movimento, id_produto, cnpj_fornecedor) VALUES
('ENTRADA', 200, '2026-06-10 09:00:00', 1, '12345678000190'),
('ENTRADA', 80,  '2026-06-10 09:10:00', 3, '12345678000190'),
('ENTRADA', 60,  '2026-06-11 10:00:00', 5, '98765432000110'),
('ENTRADA', 120, '2026-06-11 11:00:00', 4, '45612378000155'),
('SAIDA',   20,  '2026-06-14 12:00:00', 1, NULL);

-- RECEITA
INSERT INTO receita (nome, rendimento, preparo) VALUES
('Arroz Branco', 50.00, 'Refogar o arroz com alho e cebola, adicionar água e cozinhar até secar.'),
('Frango Grelhado', 30.00, 'Temperar o peito de frango e grelhar em fogo médio até dourar.'),
('Salada de Alface', 50.00, 'Higienizar e cortar a alface, temperar com sal e azeite.');

-- ITENS_RECEITA_PRODUTO (ficha técnica)
INSERT INTO itens_receita_produto (qtd_produto, id_receita, id_produto) VALUES
(5.00, 1, 1),   -- Arroz Branco usa Arroz
(15.00, 2, 3),  -- Frango Grelhado usa Peito de Frango
(10.00, 3, 5);  -- Salada usa Alface

-- ITENS_CARDAPIO_RECEITA
INSERT INTO itens_cardapio_receita (qtd_necessaria, id_itens_cardapio, id_receita) VALUES
(1.00, 1, 1),  -- Prato Livre (almoço) leva Arroz Branco
(1.00, 1, 2),  -- Prato Livre (almoço) leva Frango Grelhado
(1.00, 1, 3),  -- Prato Livre (almoço) leva Salada
(1.00, 2, 1),  -- Marmitex leva Arroz Branco
(1.00, 2, 2);  -- Marmitex leva Frango Grelhado

-- ITENS_FILA
INSERT INTO itens_fila (id_usuario, id_fila, horario_inscricao, situacao) VALUES
(1, 2, CONCAT(CURDATE(), ' 11:30:00'), 'finalizado'),
(2, 2, CONCAT(CURDATE(), ' 11:32:00'), 'em espera'),
(3, 1, CONCAT(CURDATE(), ' 11:35:00'), 'em espera'),
(5, 3, CONCAT(CURDATE(), ' 11:40:00'), 'em espera');

-- REFEICAO
INSERT INTO refeicao (id_usuario, id_itens_cardapio, id_categoria_valores, valor, horario_entrada, horario_saida) VALUES
(1, 1, 1, 8.50,  CONCAT(CURDATE(), ' 11:30:00'), CONCAT(CURDATE(), ' 12:05:00')),
(3, 1, 3, 13.00, CONCAT(CURDATE(), ' 11:45:00'), NULL),
(4, 2, 5, 12.00, CONCAT(CURDATE(), ' 11:50:00'), NULL);

-- RECARGA_HISTORICO
INSERT INTO recarga_historico (id_carteira, valor, data) VALUES
(1, 30.00, '2026-06-10 09:00:00'),
(1, 20.00, '2026-06-13 18:30:00'),
(4, 100.00, '2026-06-12 10:00:00');

-- CONTA_PAGAR
INSERT INTO conta_pagar (valor, data_vencimento, status, origem, cnpj_fornecedor) VALUES
(1500.00, '2026-06-20', 'PENDENTE', 'FORNECEDOR', '12345678000190'),
(450.00, '2026-06-25', 'PENDENTE', 'FORNECEDOR', '98765432000110'),
(320.00, '2026-06-18', 'PAGO', 'CEMIG', NULL);

-- CONTA_RECEBER
INSERT INTO conta_receber (valor, data_prevista, origem) VALUES
(15000.00, '2026-06-30', 'Subsídio Universidade'),
(850.00, '2026-06-16', 'Recargas Consolidadas');
