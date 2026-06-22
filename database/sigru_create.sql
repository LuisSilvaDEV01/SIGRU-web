-- =====================================================================
-- SIGRU - Sistema Integrado de Gestão do Restaurante Universitário
-- Script de Criação do Banco de Dados (MySQL 8.0+)
-- =====================================================================

DROP DATABASE IF EXISTS sigru;
CREATE DATABASE sigru CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigru;

-- =====================================================================
-- TABELAS BASE (sem FK)
-- =====================================================================

-- ---------------------------------------------------------------
-- CIDADE
-- PK alterada de Id_cidade para Codigo_IBGE (chave natural, única)
-- ---------------------------------------------------------------
CREATE TABLE cidade (
    codigo_ibge   CHAR(7)      NOT NULL,
    descricao     VARCHAR(100) NOT NULL,
    uf            CHAR(2)      NOT NULL,
    PRIMARY KEY (codigo_ibge)
);

-- ---------------------------------------------------------------
-- BAIRRO
-- ---------------------------------------------------------------
CREATE TABLE bairro (
    id_bairro   INT UNSIGNED AUTO_INCREMENT,
    codigo_ibge CHAR(7)     NOT NULL,
    descricao   VARCHAR(30) NOT NULL,
    PRIMARY KEY (id_bairro),
    FOREIGN KEY (codigo_ibge) REFERENCES cidade(codigo_ibge)
);

-- ---------------------------------------------------------------
-- CATEGORIA_USUARIO
-- ---------------------------------------------------------------
CREATE TABLE categoria_usuario (
    id_categoria INT UNSIGNED AUTO_INCREMENT,
    descricao    VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_categoria)
);

-- ---------------------------------------------------------------
-- TIPO_PRODUTO
-- ---------------------------------------------------------------
CREATE TABLE tipo_produto (
    id_tipo       INT UNSIGNED AUTO_INCREMENT,
    descricao_tipo VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_tipo)
);

-- ---------------------------------------------------------------
-- CARDAPIO
-- ---------------------------------------------------------------
CREATE TABLE cardapio (
    id_cardapio  INT UNSIGNED AUTO_INCREMENT,
    data_servico DATE        NOT NULL,
    turno        VARCHAR(20) NOT NULL,
    PRIMARY KEY (id_cardapio)
);

-- ---------------------------------------------------------------
-- FILA
-- ---------------------------------------------------------------
CREATE TABLE fila (
    id_fila  INT UNSIGNED AUTO_INCREMENT,
    tipo_fila VARCHAR(20) NOT NULL,
    PRIMARY KEY (id_fila)
);

-- ---------------------------------------------------------------
-- CONTA_RECEBER
-- ---------------------------------------------------------------
CREATE TABLE conta_receber (
    id_conta_receber INT UNSIGNED AUTO_INCREMENT,
    valor            DECIMAL(8,2) NOT NULL,
    data_prevista    DATE         NOT NULL,
    origem           VARCHAR(50)  NOT NULL,
    PRIMARY KEY (id_conta_receber)
);

-- =====================================================================
-- TABELAS COM FK DE 1º NÍVEL
-- =====================================================================

-- ---------------------------------------------------------------
-- FORNECEDORES
-- PK alterada de Id_fornecedor para CNPJ (chave natural, única)
-- ---------------------------------------------------------------
CREATE TABLE fornecedores (
    cnpj         CHAR(14)     NOT NULL,
    razao_social VARCHAR(100) NOT NULL,
    telefone     VARCHAR(15)  NULL,
    id_bairro    INT UNSIGNED NOT NULL,
    endereco     VARCHAR(100) NOT NULL,
    PRIMARY KEY (cnpj),
    FOREIGN KEY (id_bairro) REFERENCES bairro(id_bairro)
);

-- ---------------------------------------------------------------
-- FUNCIONARIO
-- PK alterada de Id_funcionario para CPF (chave natural, única)
-- ---------------------------------------------------------------
CREATE TABLE funcionario (
    cpf         CHAR(11)     NOT NULL,
    nome_func   VARCHAR(100) NOT NULL,
    cargo       VARCHAR(50)  NOT NULL,
    privilegios VARCHAR(200) NOT NULL,
    id_bairro   INT UNSIGNED NOT NULL,
    endereco    VARCHAR(100) NOT NULL,
    PRIMARY KEY (cpf),
    FOREIGN KEY (id_bairro) REFERENCES bairro(id_bairro)
);

-- ---------------------------------------------------------------
-- USUARIO
-- PK alterada de Id_usuario para matricula (chave natural, única)
-- ---------------------------------------------------------------
CREATE TABLE usuario (
    matricula     BIGINT UNSIGNED NOT NULL,
    nome_completo VARCHAR(100)    NOT NULL,
    id_categoria  INT UNSIGNED    NOT NULL,
    id_bairro     INT UNSIGNED    NOT NULL,
    endereco      VARCHAR(100)    NOT NULL,
    saldo         DECIMAL(8,2)    NOT NULL DEFAULT 0.00, -- saldo virtual p/ pagamento
    PRIMARY KEY (matricula),
    FOREIGN KEY (id_categoria) REFERENCES categoria_usuario(id_categoria),
    FOREIGN KEY (id_bairro)    REFERENCES bairro(id_bairro)
);

-- ---------------------------------------------------------------
-- ITENS_CATEGORIA_VALORES
-- ---------------------------------------------------------------
CREATE TABLE itens_categoria_valores (
    id_categoria   INT UNSIGNED NOT NULL,
    tipo_refeicao  VARCHAR(10)  NOT NULL,
    valor_refeicao DECIMAL(5,2) NOT NULL,
    PRIMARY KEY (id_categoria, tipo_refeicao),
    FOREIGN KEY (id_categoria) REFERENCES categoria_usuario(id_categoria)
);

-- ---------------------------------------------------------------
-- PRODUTO
-- ---------------------------------------------------------------
CREATE TABLE produto (
    id_produto      INT UNSIGNED AUTO_INCREMENT,
    nome_produto    VARCHAR(100) NOT NULL,
    unidade_medida  VARCHAR(2)   NOT NULL,
    id_tipo_produto INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_produto),
    FOREIGN KEY (id_tipo_produto) REFERENCES tipo_produto(id_tipo)
);

-- ---------------------------------------------------------------
-- ITENS_CARDAPIO
-- ---------------------------------------------------------------
CREATE TABLE itens_cardapio (
    id_itens_cardapio INT UNSIGNED AUTO_INCREMENT,
    id_cardapio       INT UNSIGNED NOT NULL,
    descricao         VARCHAR(200) NULL,
    tipo              VARCHAR(20)  NOT NULL,
    PRIMARY KEY (id_itens_cardapio),
    FOREIGN KEY (id_cardapio) REFERENCES cardapio(id_cardapio)
);

-- =====================================================================
-- TABELAS COM FK DE 2º NÍVEL
-- =====================================================================

-- ---------------------------------------------------------------
-- ESTOQUE
-- ---------------------------------------------------------------
CREATE TABLE estoque (
    id_estoque       INT UNSIGNED AUTO_INCREMENT,
    quantidade       INT UNSIGNED NOT NULL,
    data_atualizacao DATETIME     NOT NULL,
    id_produto       INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_estoque),
    FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
);

-- ---------------------------------------------------------------
-- MOVIMENTO_ESTOQUE
-- ---------------------------------------------------------------
CREATE TABLE movimento_estoque (
    id_movimento    INT UNSIGNED AUTO_INCREMENT,
    tipo_movimento  VARCHAR(10)  NOT NULL, -- 'Entrada' ou 'Saida'
    quantidade_mov  INT UNSIGNED NOT NULL,
    data_movimento  DATETIME     NOT NULL,
    id_produto      INT UNSIGNED NOT NULL,
    cnpj_fornecedor CHAR(14)     NULL,
    PRIMARY KEY (id_movimento),
    FOREIGN KEY (id_produto)      REFERENCES produto(id_produto),
    FOREIGN KEY (cnpj_fornecedor) REFERENCES fornecedores(cnpj)
);

-- ---------------------------------------------------------------
-- RECEITA
-- ---------------------------------------------------------------
CREATE TABLE receita (
    id_receita INT UNSIGNED AUTO_INCREMENT,
    nome       VARCHAR(50)  NOT NULL,
    rendimento DECIMAL(8,2) NOT NULL,
    preparo    VARCHAR(2000) NOT NULL,
    PRIMARY KEY (id_receita)
);

-- ---------------------------------------------------------------
-- ITENS_CARDAPIO_RECEITA
-- ---------------------------------------------------------------
CREATE TABLE itens_cardapio_receita (
    id_itens_cardapio_receita INT UNSIGNED AUTO_INCREMENT,
    qtd_necessaria            DECIMAL(8,2) NOT NULL,
    id_cardapio               INT UNSIGNED NOT NULL,
    id_receita                INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_itens_cardapio_receita),
    FOREIGN KEY (id_cardapio) REFERENCES cardapio(id_cardapio),
    FOREIGN KEY (id_receita)  REFERENCES receita(id_receita)
);

-- ---------------------------------------------------------------
-- ITENS_RECEITA_PRODUTO
-- ---------------------------------------------------------------
CREATE TABLE itens_receita_produto (
    id_receita_produto INT UNSIGNED AUTO_INCREMENT,
    qtd_produto         DECIMAL(8,2) NOT NULL,
    id_receita          INT UNSIGNED NOT NULL,
    id_produto          INT UNSIGNED NOT NULL,
    PRIMARY KEY (id_receita_produto),
    FOREIGN KEY (id_receita) REFERENCES receita(id_receita),
    FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
);

-- ---------------------------------------------------------------
-- ITENS_FILA
-- ---------------------------------------------------------------
CREATE TABLE itens_fila (
    id_itens_fila    INT UNSIGNED AUTO_INCREMENT,
    matricula        BIGINT UNSIGNED NOT NULL,
    id_fila          INT UNSIGNED    NOT NULL,
    horario_inscricao DATETIME       NOT NULL,
    situacao         VARCHAR(20)     NOT NULL, -- 'EM ESPERA' ou 'FINALIZADO'
    PRIMARY KEY (id_itens_fila),
    FOREIGN KEY (matricula) REFERENCES usuario(matricula),
    FOREIGN KEY (id_fila)   REFERENCES fila(id_fila)
);

-- ---------------------------------------------------------------
-- REFEICAO
-- ---------------------------------------------------------------
CREATE TABLE refeicao (
    id_refeicao       INT UNSIGNED AUTO_INCREMENT,
    matricula         BIGINT UNSIGNED NOT NULL,
    id_itens_cardapio INT UNSIGNED    NOT NULL,
    valor             DECIMAL(8,2)    NOT NULL,
    horario_entrada   DATETIME        NOT NULL,
    horario_saida     DATETIME        NULL,
    PRIMARY KEY (id_refeicao),
    FOREIGN KEY (matricula)         REFERENCES usuario(matricula),
    FOREIGN KEY (id_itens_cardapio) REFERENCES itens_cardapio(id_itens_cardapio)
);

-- ---------------------------------------------------------------
-- CONTA_PAGAR
-- origem: 'FORNECEDOR' (id_origem = cnpj numerico nao se aplica, ver obs)
-- Como cnpj é alfanumerico, id_origem foi mantido generico (varchar)
-- ---------------------------------------------------------------
CREATE TABLE conta_pagar (
    id_conta_pagar  INT UNSIGNED AUTO_INCREMENT,
    valor           DECIMAL(10,2) NOT NULL,
    data_vencimento DATE          NOT NULL,
    status          VARCHAR(20)   NOT NULL DEFAULT 'Pendente',
    origem          VARCHAR(20)   NOT NULL, -- ex: 'CEMIG', 'FORNECEDOR'
    id_origem       VARCHAR(20)   NULL,     -- referencia generica (cnpj, codigo, etc)
    PRIMARY KEY (id_conta_pagar)
);

-- =====================================================================
-- FIM DA CRIAÇÃO DAS TABELAS
-- =====================================================================
