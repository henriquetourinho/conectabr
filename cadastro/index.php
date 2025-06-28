<?php
require_once '../php/conexao.php';

$errors = [];
$nome_exibicao = '';
$username = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome_exibicao = trim($_POST['nome_exibicao']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($nome_exibicao)) $errors[] = "O nome de exibição é obrigatório.";
    if (empty($username)) $errors[] = "O nome de usuário é obrigatório.";
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) $errors[] = "Usuário inválido (apenas letras, números e _, de 3 a 20 caracteres).";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Formato de e-mail inválido.";
    if ($password !== $password_confirm) $errors[] = "As senhas não coincidem.";
    if (strlen($password) < 8) $errors[] = "A senha deve ter no mínimo 8 caracteres.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) $errors[] = "Este e-mail já está em uso.";

        $stmt = $pdo->prepare("SELECT id FROM perfis WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) $errors[] = "Este nome de usuário já está em uso.";
    }

    if (empty($errors)) {
        $senha_hash = password_hash($password, PASSWORD_ARGON2ID);
        try {
            $pdo->beginTransaction();
            $stmt_user = $pdo->prepare("INSERT INTO usuarios (email, senha_hash) VALUES (?, ?)");
            $stmt_user->execute([$email, $senha_hash]);
            $stmt_get_id = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt_get_id->execute([$email]);
            $usuario_id = $stmt_get_id->fetchColumn();
            $stmt_profile = $pdo->prepare("INSERT INTO perfis (usuario_id, username, nome_exibicao) VALUES (?, ?, ?)");
            $stmt_profile->execute([$usuario_id, $username, $nome_exibicao]);
            $pdo->commit();
            header("Location: /login?status=cadastrado");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Erro ao criar a conta. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - ConectaBR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --background-color: #0D0D0F; --card-background: rgba(22, 22, 25, 0.5); --border-color: rgba(255, 255, 255, 0.1); --text-color: #E4E4E7; --text-muted: #A1A1AA; --accent-1: #8b5cf6; --accent-2: #ec4899; --danger-color: #ef4444; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: var(--background-color); color: var(--text-color); font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 2rem; position: relative; overflow-x: hidden; }
        body::before, body::after { content: ''; position: fixed; width: 600px; height: 600px; border-radius: 50%; filter: blur(150px); z-index: -1; opacity: 0.4; }
        body::before { background-color: var(--accent-1); top: -20%; left: -20%; animation: move-aurora-1 20s infinite alternate; }
        body::after { background-color: var(--accent-2); bottom: -20%; right: -20%; animation: move-aurora-2 25s infinite alternate; }
        @keyframes move-aurora-1 { from { transform: translate(0, 0) rotate(0deg); } to { transform: translate(200px, 100px) rotate(360deg); } }
        @keyframes move-aurora-2 { from { transform: translate(0, 0) rotate(0deg); } to { transform: translate(-200px, -100px) rotate(-360deg); } }
        .auth-card { width: 100%; max-width: 450px; background: var(--card-background); border: 1px solid var(--border-color); border-radius: 24px; padding: 40px; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); animation: fade-in 1s ease-out; text-align: center; }
        @keyframes fade-in { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .auth-card h2 { font-weight: 700; font-size: 2rem; margin-bottom: 1rem; }
        .auth-card p { color: var(--text-muted); margin-bottom: 2rem; }
        .form-group { text-align: left; margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem; }
        .form-control { width: 100%; background-color: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 16px; color: var(--text-color); font-size: 1rem; transition: border-color 0.3s, box-shadow 0.3s; }
        .form-control:focus { outline: none; border-color: var(--accent-1); box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.3); }
        .form-control:-webkit-autofill { -webkit-box-shadow: 0 0 0 30px var(--background-color) inset !important; -webkit-text-fill-color: var(--text-color) !important;}
        .btn-submit { width: 100%; padding: 12px; font-size: 1rem; font-weight: 700; color: white; background-image: linear-gradient(to right, var(--accent-1) 0%, var(--accent-2) 51%, var(--accent-1) 100%); background-size: 200% auto; border: none; border-radius: 8px; cursor: pointer; transition: 0.5s; margin-top: 1rem;}
        .btn-submit:hover { background-position: right center; }
        .auth-link { color: var(--text-muted); text-decoration: none; transition: color 0.3s; font-weight: 500;}
        .auth-link:hover { color: var(--text-color); }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid transparent; text-align: left; font-size: 0.9rem;}
        .alert-danger { background-color: rgba(239, 68, 68, 0.1); border-color: var(--danger-color); color: var(--danger-color); }
        .alert-danger p { margin-bottom: 0.5rem; }
        .alert-danger p:last-child { margin-bottom: 0; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2>Junte-se ao ConectaBR</h2>
        <p>Crie seu perfil e centralize todos os seus links em um só lugar.</p>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="/cadastro/" method="post">
            <div class="form-group">
                <label for="nome_exibicao">Nome de Exibição</label>
                <input type="text" id="nome_exibicao" class="form-control" name="nome_exibicao" value="<?= htmlspecialchars($nome_exibicao) ?>" required>
            </div>
            <div class="form-group">
                <label for="username">Nome de Usuário (para URL)</label>
                <input type="text" id="username" class="form-control" name="username" value="<?= htmlspecialchars($username) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" class="form-control" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Senha (mínimo 8 caracteres)</label>
                <input type="password" id="password" class="form-control" name="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirmar Senha</label>
                <input type="password" id="password_confirm" class="form-control" name="password_confirm" required>
            </div>
            <button type="submit" class="btn-submit">Criar Conta</button>
        </form>

        <p style="margin-top: 2rem; font-size: 0.9rem;">
            Já tem uma conta? <a href="/login/" class="auth-link">Faça login</a>
        </p>
    </div>
</body>
</html>