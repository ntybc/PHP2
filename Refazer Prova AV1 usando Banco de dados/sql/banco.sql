-- Banco de dados da AV1 (Perguntas e Respostas + Usuários)
-- Como usar: importe este arquivo no MySQL/MariaDB (phpMyAdmin > Importar, ou pelo terminal).
-- Ele cria o banco "av1_perguntas", as 3 tabelas e os dados que antes estavam nos .txt.

CREATE DATABASE IF NOT EXISTS av1_perguntas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE av1_perguntas;

-- Apaga as tabelas antigas (respostas primeiro, porque depende de perguntas)
DROP TABLE IF EXISTS respostas;
DROP TABLE IF EXISTS perguntas;
DROP TABLE IF EXISTS usuarios;

-- Antes: usuarios/usuarios.txt
CREATE TABLE usuarios (
    id_usuario      INT AUTO_INCREMENT PRIMARY KEY,
    nome_usuario    VARCHAR(150) NOT NULL,
    data_nascimento DATE NOT NULL,
    cpf             CHAR(11) NOT NULL UNIQUE,
    email_usuario   VARCHAR(150) NOT NULL UNIQUE,
    senha_usuario   VARCHAR(255) NOT NULL  -- só o hash (password_hash), nunca a senha
);

-- Antes: perguntas/perguntas.txt
CREATE TABLE perguntas (
    id_pergunta    INT AUTO_INCREMENT PRIMARY KEY,
    texto_pergunta VARCHAR(500) NOT NULL,
    tipo_pergunta  ENUM('multipla_escolha', 'texto') NOT NULL DEFAULT 'multipla_escolha'
);

-- Antes: respostas/respostas.txt
-- ON DELETE CASCADE: ao excluir a pergunta, o banco exclui as respostas dela sozinho
CREATE TABLE respostas (
    id_resposta       INT AUTO_INCREMENT PRIMARY KEY,
    id_pergunta       INT NOT NULL,
    texto_resposta    VARCHAR(500) NOT NULL,
    is_resposta_certa TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_pergunta) REFERENCES perguntas (id_pergunta) ON DELETE CASCADE
);

-- Dados que estavam nos arquivos .txt
INSERT INTO perguntas (id_pergunta, texto_pergunta, tipo_pergunta) VALUES
(1, 'Em qual ano a faeterj foi fundada?', 'multipla_escolha'),
(2, 'Qual nome do professor que ministra a aula de 3DAW?', 'multipla_escolha'),
(3, 'Descreva sua experiência como aluno da FAETERJ. E cite em que periodo você está.', 'texto');

INSERT INTO respostas (id_resposta, id_pergunta, texto_resposta, is_resposta_certa) VALUES
(1, 1, '2000', 1),
(2, 1, '1990', 0),
(3, 1, '2009', 0),
(4, 1, '1992', 0),
(5, 1, '1999', 0),
(6, 2, 'André Snows', 0),
(7, 2, 'André Barbosa', 0),
(8, 2, 'André Neves', 1),
(9, 2, 'Ricardo Marciano', 0),
(10, 2, 'Vinícius Marquess', 0);

INSERT INTO usuarios (id_usuario, nome_usuario, data_nascimento, cpf, email_usuario, senha_usuario) VALUES
(1, 'Maria Sousa', '1990-05-20', '12345678909', 'maria@empresa.com', '$2y$10$...2'),
(2, 'Nathalia', '2007-03-05', '20522467709', 'nathaliayasmin2007@gmail.com', '$2y$10$64hLkw98rrEi8U8BuufWMOf3T2di/eybwNCvZ5dTGBbDyfjiKebvi');
