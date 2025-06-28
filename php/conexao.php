<?php
// Inicia a sessão em todas as páginas
session_start();

// --- Suas credenciais do banco de dados ---
define('DB_HOST', 'localhost');      // Geralmente 'localhost'
define('DB_NAME', 'BASE');         // O nome do banco que você criou
define('DB_USER', 'USUARIO');           // Seu usuário do banco
define('DB_PASS', 'SENHA');    // Sua senha do banco
// -----------------------------------------

// Define o DSN (Data Source Name)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

// Opções do PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança exceções em caso de erro
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna arrays associativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa prepared statements nativos
];

try {
    // Cria a instância do PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Em caso de erro na conexão, exibe uma mensagem e encerra o script
    // Em um ambiente de produção, você pode querer logar o erro em vez de exibi-lo
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}