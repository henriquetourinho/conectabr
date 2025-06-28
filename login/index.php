<?php
require_once '../php/conexao.php';

$error = '';
$email = '';

if (isset($_SESSION['usuario_id'])) {
    header("Location: /painel");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        $stmt = $pdo->prepare("SELECT id, senha_hash FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['senha_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $user['id'];
            $stmt_update = $pdo->prepare("UPDATE usuarios SET ultimo_login_em = NOW() WHERE id = ?");
            $stmt_update->execute([$user['id']]);
            header("Location: /painel");
            exit();
        } else {
            $error = 'E-mail ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ConectaBR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --background-color: #0D0D0F; --card-background: rgba(22, 22, 25, 0.5); --border-color: rgba(255, 255, 255, 0.1); --text-color: #E4E4E7; --text-muted: #A1A1AA; --accent-1: #8b5cf6; --accent-2: #ec4899; --danger-color: #ef4444; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: var(--background-color); color: var(--text-color); font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 2rem; position: relative; overflow: hidden; }
        body::before, body::after { content: ''; position: fixed; width: 600px; height: 600px; border-radius: 50%; filter: blur(150px); z-index: -1; opacity: 0.4; }
        body::before { background-color: var(--accent-1); top: -20%; left: -20%; animation: move-aurora-1 20s infinite alternate; }
        body::after { background-color: var(--accent-2); bottom: -20%; right: -20%; animation: move-aurora-2 25s infinite alternate; }
        @keyframes move-aurora-1 { from { transform: translate(0, 0) rotate(0deg); } to { transform: translate(200px, 100px) rotate(360deg); } }
        @keyframes move-aurora-2 { from { transform: translate(0, 0) rotate(0deg); } to { transform: translate(-200px, -100px) rotate(-360deg); } }
        .auth-card { width: 100%; max-width: 450px; background: var(--card-background); border: 1px solid var(--border-color); border-radius: 24px; padding: 40px; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); animation: fade-in 1s ease-out; text-align: center; }
        @keyframes fade-in { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .auth-card h2 { font-weight: 700; font-size: 2rem; margin-bottom: 1rem; }
        .auth-card p { color: var(--text-muted); margin-bottom: 2rem; }
        .form-group { text-align: left; margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem; }
        .form-control { width: 100%; background-color: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 16px; color: var(--text-color); font-size: 1rem; transition: border-color 0.3s, box-shadow 0.3s; }
        .form-control:focus { outline: none; border-color: var(--accent-1); box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.3); }
        .btn-submit { width: 100%; padding: 12px; font-size: 1rem; font-weight: 700; color: white; background-image: linear-gradient(to right, var(--accent-1) 0%, var(--accent-2) 51%, var(--accent-1) 100%); background-size: 200% auto; border: none; border-radius: 8px; cursor: pointer; transition: 0.5s; }
        .btn-submit:hover { background-position: right center; }
        .auth-link { color: var(--text-muted); text-decoration: none; transition: color 0.3s; }
        .auth-link:hover { color: var(--text-color); }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid transparent; }
        .alert-danger { background-color: rgba(239, 68, 68, 0.1); border-color: var(--danger-color); color: var(--danger-color); }
        .alert-success { background-color: rgba(16, 185, 129, 0.1); border-color: #10b981; color: #10b981; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2>Bem-vindo de Volta!</h2>
        <p>Faça login para gerenciar seu perfil no ConectaBR.</p>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'cadastrado'): ?>
            <div class="alert alert-success">Cadastro realizado com sucesso! Faça login para continuar.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="/login/" method="post">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-submit">Entrar</button>
        </form>

        <p style="margin-top: 2rem; font-size: 0.9rem;">
            <a href="/esqueci-senha/" class="auth-link">Esqueceu a senha?</a>
        </p>
        <p style="margin-top: 1rem; font-size: 0.9rem;">
            Não tem uma conta? <a href="/cadastro/" class="auth-link">Cadastre-se</a>
        </p>
    </div>
</body>
</html>