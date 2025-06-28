<?php
require_once '../php/conexao.php';

$message = '';
$message_type = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Por favor, insira um endereço de e-mail válido.";
        $message_type = 'danger';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND is_active = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Gera um token seguro
            $token = bin2hex(random_bytes(32));
            // Define o tempo de expiração (ex: 1 hora)
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Armazena o HASH do token no banco de dados, nunca o token original
            $token_hash = hash('sha256', $token);

            try {
                $stmt_insert = $pdo->prepare("INSERT INTO redefinicoes_senha (usuario_id, token_hash, expira_em) VALUES (?, ?, ?)");
                $stmt_insert->execute([$user['id'], $token_hash, $expires_at]);
                
                // Monta o link de redefinição
                $reset_link = "http://perfis.local/redefinir-senha/?token=" . $token;

                $message = "Link de redefinição gerado com sucesso.";
                $message_type = 'success';

            } catch (Exception $e) {
                $message = "Ocorreu um erro ao gerar o link. Tente novamente.";
                $message_type = 'danger';
            }
        } else {
            // Mostra uma mensagem genérica para não revelar se um e-mail existe ou não no sistema
            $message = "Se um e-mail correspondente for encontrado, um link de redefinição será gerado.";
            $message_type = 'info';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center vh-100">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Recuperar Senha</h2>
                        <p class="text-center text-muted mb-4">Digite seu e-mail e nós geraremos um link para você criar uma nova senha.</p>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
                        <?php endif; ?>

                        <?php if ($reset_link): ?>
                            <div class="alert alert-warning">
                                <p><strong>SIMULAÇÃO DE ENVIO DE E-MAIL:</strong></p>
                                <p>Em um site real, o link abaixo seria enviado para o seu e-mail. Por favor, copie e cole em uma nova aba para continuar:</p>
                                <strong class="text-break"><?= htmlspecialchars($reset_link) ?></strong>
                            </div>
                        <?php endif; ?>

                        <form action="/esqueci-senha/" method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Seu e-mail</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Gerar Link de Redefinição</button>
                            </div>
                        </form>
                        <p class="text-center mt-3"><a href="/login/">Voltar para o Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>