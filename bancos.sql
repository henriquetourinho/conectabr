-- --------------------------------------------------------
-- Projeto: ConectaBR
-- Versão do Servidor: MariaDB / MySQL
-- Data: 28/06/2025
-- --------------------------------------------------------

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

--
-- Estrutura para tabela `usuarios`
-- Guarda dados de autenticação e gerenciamento de conta.
--
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` char(36) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `is_ativo` tinyint(1) NOT NULL DEFAULT 1,
  `is_email_verificado` tinyint(1) NOT NULL DEFAULT 0,
  `ultimo_login_em` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Estrutura para tabela `perfis`
-- Guarda dados públicos do perfil do usuário, incluindo a localização.
--
DROP TABLE IF EXISTS `perfis`;
CREATE TABLE `perfis` (
  `id` char(36) NOT NULL,
  `usuario_id` char(36) NOT NULL,
  `username` varchar(30) NOT NULL,
  `nome_exibicao` varchar(100) NOT NULL,
  `bio` text DEFAULT NULL,
  `foto_perfil_url` varchar(2048) DEFAULT NULL,
  `is_publico` tinyint(1) NOT NULL DEFAULT 1,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  UNIQUE KEY `username` (`username`),
  CONSTRAINT `fk_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Estrutura para tabela `plataformas_sociais`
-- Tabela mestre com as redes sociais suportadas pelo sistema.
--
DROP TABLE IF EXISTS `plataformas_sociais`;
CREATE TABLE `plataformas_sociais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `url_base` varchar(255) DEFAULT NULL,
  `icone_svg` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Inserindo dados iniciais para `plataformas_sociais`
--
INSERT INTO `plataformas_sociais` (`id`, `nome`, `url_base`, `icone_svg`) VALUES
(1, 'Instagram', 'https://instagram.com/', '<i class="fab fa-instagram"></i>'),
(2, 'Twitter (X)', 'https://x.com/', '<i class="fab fa-twitter"></i>'),
(3, 'Facebook', 'https://facebook.com/', '<i class="fab fa-facebook"></i>'),
(4, 'LinkedIn', 'https://linkedin.com/in/', '<i class="fab fa-linkedin"></i>'),
(5, 'GitHub', 'https://github.com/', '<i class="fab fa-github"></i>'),
(6, 'YouTube', 'https://youtube.com/', '<i class="fab fa-youtube"></i>'),
(7, 'TikTok', 'https://tiktok.com/@', '<i class="fab fa-tiktok"></i>'),
(8, 'Website', '', '<i class="fas fa-globe"></i>');


--
-- Estrutura para tabela `links_sociais`
-- Conecta um perfil a uma plataforma com uma URL/usuário específico.
--
DROP TABLE IF EXISTS `links_sociais`;
CREATE TABLE `links_sociais` (
  `id` char(36) NOT NULL,
  `perfil_id` char(36) NOT NULL,
  `plataforma_id` int(11) NOT NULL,
  `url_ou_usuario` varchar(255) NOT NULL,
  `ordem_exibicao` smallint(6) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `perfil_id_plataforma_id` (`perfil_id`,`plataforma_id`),
  KEY `fk_plataforma` (`plataforma_id`),
  CONSTRAINT `fk_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plataforma` FOREIGN KEY (`plataforma_id`) REFERENCES `plataformas_sociais` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Estrutura para tabela `redefinicoes_senha`
-- Armazena tokens seguros para o fluxo de "Esqueci minha Senha".
--
DROP TABLE IF EXISTS `redefinicoes_senha`;
CREATE TABLE `redefinicoes_senha` (
  `id` char(36) NOT NULL,
  `usuario_id` char(36) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expira_em` timestamp NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `fk_usuario_redefinicao` (`usuario_id`),
  CONSTRAINT `fk_usuario_redefinicao` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Estrutura para tabela `verificacoes_email`
-- Armazena tokens para o futuro recurso de verificação de e-mail.
--
DROP TABLE IF EXISTS `verificacoes_email`;
CREATE TABLE `verificacoes_email` (
  `id` char(36) NOT NULL,
  `usuario_id` char(36) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expira_em` timestamp NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `fk_usuario_verificacao` (`usuario_id`),
  CONSTRAINT `fk_usuario_verificacao` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
-- GATILHOS (TRIGGERS) PARA GERAÇÃO DE UUIDs
-- Necessário para o MySQL preencher as chaves primárias automaticamente.
-- --------------------------------------------------------

DROP TRIGGER IF EXISTS `before_insert_usuarios`;
DELIMITER $$
CREATE TRIGGER `before_insert_usuarios` BEFORE INSERT ON `usuarios` FOR EACH ROW IF NEW.id IS NULL THEN SET NEW.id = UUID(); END IF
$$
DELIMITER ;

DROP TRIGGER IF EXISTS `before_insert_perfis`;
DELIMITER $$
CREATE TRIGGER `before_insert_perfis` BEFORE INSERT ON `perfis` FOR EACH ROW IF NEW.id IS NULL THEN SET NEW.id = UUID(); END IF
$$
DELIMITER ;

DROP TRIGGER IF EXISTS `before_insert_links_sociais`;
DELIMITER $$
CREATE TRIGGER `before_insert_links_sociais` BEFORE INSERT ON `links_sociais` FOR EACH ROW IF NEW.id IS NULL THEN SET NEW.id = UUID(); END IF
$$
DELIMITER ;

DROP TRIGGER IF EXISTS `before_insert_redefinicoes_senha`;
DELIMITER $$
CREATE TRIGGER `before_insert_redefinicoes_senha` BEFORE INSERT ON `redefinicoes_senha` FOR EACH ROW IF NEW.id IS NULL THEN SET NEW.id = UUID(); END IF
$$
DELIMITER ;

DROP TRIGGER IF EXISTS `before_insert_verificacoes_email`;
DELIMITER $$
CREATE TRIGGER `before_insert_verificacoes_email` BEFORE INSERT ON `verificacoes_email` FOR EACH ROW IF NEW.id IS NULL THEN SET NEW.id = UUID(); END IF
$$
DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;