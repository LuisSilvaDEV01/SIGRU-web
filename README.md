# SIGRU

Versão 1.0 do sistema web integrado "SIGRU", Sistema Integrado de Gerenciamento do Restaurante Universítario, desenvolvido para gerenciamento completo do restaurante.

## Sobre o projeto

O SIGRU é uma aplicação web desenvolvida com foco em organização, gerenciamento e execução das funcionalidades propostas pelo projeto, utilizando arquitetura baseada em PHP e persistência de dados em MySQL.

O sistema foi estruturado para funcionamento em ambiente local utilizando servidor Apache e banco de dados relacional.

---

## Tecnologias utilizadas

* PHP
* MySQL
* HTML5
* CSS3
* JavaScript
* XAMPP (ambiente de execução)

---

## Estrutura do projeto

```bash
sigru_app/
├── assets/
├── config/
├── database/
├── pages/
├── index.php
├── sigru_seed.sql
└── README.md
```

> Observação: a estrutura pode variar conforme atualizações do projeto.

---

## Configuração do ambiente

### 1. Clonar o repositório

```bash
git clone <https://github.com/LuisSilvaDEV01/SIGRU-web.git>
```

### 2. Mover para o diretório do servidor

Exemplo utilizando XAMPP:

```bash
C:\xampp\htdocs\
```

### 3. Iniciar serviços

Iniciar:

* Apache
* MySQL

### 4. Criar banco de dados

Criar um banco chamado:

```txt
sigru
```

### 5. Importar banco

Importar o arquivo:

```txt
sigru_create.sql
```

```txt
sigru_seed.sql
```

---

## Executando o sistema

Acessar:

```txt
http://localhost/sigru_app
```

---

## Funcionalidades

* Gerenciamento de dados
* Persistência em banco MySQL
* Interface web
* Operações CRUD

---

## Autores

Desenvolvido como projeto acadêmico.

Autores:

* Luís Otávio de Souza e Silva
* Matheus Gonçalves Dias

---

## Licença

Projeto desenvolvido para fins acadêmicos. 

Curso de Sistemas de Informação - Universidade Estadual de Montes Claros
