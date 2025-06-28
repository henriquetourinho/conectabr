<?php
require_once '../php/conexao.php'; // Apenas para chamar o session_start()

// Limpa todas as variáveis da sessão
session_unset();

// Destrói a sessão
session_destroy();

// Redireciona para a página de login
header("Location: /login?status=logout");
exit();