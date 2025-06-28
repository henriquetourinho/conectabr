<?php
// --- LÓGICA PHP COMPLETA ---

// 1. Conexão e Segurança da Página
require_once '../php/conexao.php';
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /login");
    exit();
}

// 2. Inicialização de Variáveis
$usuario_id = $_SESSION['usuario_id'];
$success_message = '';
$error_message = '';

// 3. Processamento dos Formulários (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- SE O FORMULÁRIO DE 'SALVAR PERFIL' FOI ENVIADO ---
    if (isset($_POST['salvar_perfil'])) {
        $nome_exibicao = trim($_POST['nome_exibicao']);
        $bio = trim($_POST['bio']);
        $cidade = trim($_POST['cidade']);
        $estado = trim($_POST['estado']);
        $new_photo_path = null;

        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['foto_perfil'];
            if ($file['size'] > 2 * 1024 * 1024) { $error_message = "Erro: O arquivo é muito grande (limite 2MB)."; }
            else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($file['tmp_name']);
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($mime_type, $allowed_types)) { $error_message = "Erro: Tipo de arquivo inválido."; }
                else {
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $unique_filename = bin2hex(random_bytes(16)) . '.' . $extension;
                    $upload_dir = __DIR__ . '/../uploads/avatars/';
                    $destination = $upload_dir . $unique_filename;
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $new_photo_path = '/uploads/avatars/' . $unique_filename;
                    } else { $error_message = "Erro ao mover o arquivo."; }
                }
            }
        } elseif (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error_message = "Ocorreu um erro no upload.";
        }

        if (empty($error_message)) {
            if (empty($nome_exibicao)) { $error_message = "O nome de exibição não pode ficar vazio."; }
            else {
                try {
                    $pdo->beginTransaction();
                    if ($new_photo_path) {
                        $stmt_old_photo = $pdo->prepare("SELECT foto_perfil_url FROM perfis WHERE usuario_id = ?");
                        $stmt_old_photo->execute([$usuario_id]);
                        $old_photo_path = $stmt_old_photo->fetchColumn();
                    }
                    $sql_update_perfil = "UPDATE perfis SET nome_exibicao = ?, bio = ?, cidade = ?, estado = ?" . ($new_photo_path ? ", foto_perfil_url = ?" : "") . " WHERE usuario_id = ?";
                    $params = [$nome_exibicao, $bio, $cidade, $estado];
                    if ($new_photo_path) { $params[] = $new_photo_path; }
                    $params[] = $usuario_id;
                    $stmt_perfil = $pdo->prepare($sql_update_perfil);
                    $stmt_perfil->execute($params);
                    if ($new_photo_path && !empty($old_photo_path) && file_exists(__DIR__ . '/..' . $old_photo_path)) {
                        unlink(__DIR__ . '/..' . $old_photo_path);
                    }
                    $stmt_get_perfil_id = $pdo->prepare("SELECT id FROM perfis WHERE usuario_id = ?");
                    $stmt_get_perfil_id->execute([$usuario_id]);
                    $perfil_id = $stmt_get_perfil_id->fetchColumn();
                    $stmt_delete_links = $pdo->prepare("DELETE FROM links_sociais WHERE perfil_id = ?");
                    $stmt_delete_links->execute([$perfil_id]);
                    if (isset($_POST['links_sociais']) && is_array($_POST['links_sociais'])) {
                        $stmt_insert_link = $pdo->prepare("INSERT INTO links_sociais (perfil_id, plataforma_id, url_ou_usuario) VALUES (?, ?, ?)");
                        foreach ($_POST['links_sociais'] as $plataforma_id => $url_ou_usuario) {
                            $url_ou_usuario = trim($url_ou_usuario);
                            if (!empty($url_ou_usuario)) { $stmt_insert_link->execute([$perfil_id, $plataforma_id, $url_ou_usuario]); }
                        }
                    }
                    $pdo->commit();
                    $success_message = "Perfil atualizado com sucesso!";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error_message = "Erro ao atualizar o perfil.";
                }
            }
        }
    }

    // --- SE O FORMULÁRIO DE 'ALTERAR SENHA' FOI ENVIADO ---
    if (isset($_POST['alterar_senha'])) {
        $senha_atual = $_POST['senha_atual'];
        $nova_senha = $_POST['nova_senha'];
        $confirmar_nova_senha = $_POST['confirmar_nova_senha'];

        if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_nova_senha)) {
            $error_message = "Todos os campos de senha são obrigatórios.";
        } elseif ($nova_senha !== $confirmar_nova_senha) {
            $error_message = "A nova senha e a confirmação não coincidem.";
        } elseif (strlen($nova_senha) < 8) {
            $error_message = "A nova senha deve ter no mínimo 8 caracteres.";
        } else {
            $stmt = $pdo->prepare("SELECT senha_hash FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha_atual, $user['senha_hash'])) {
                $new_password_hash = password_hash($nova_senha, PASSWORD_ARGON2ID);
                $stmt_update = $pdo->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?");
                if ($stmt_update->execute([$new_password_hash, $usuario_id])) {
                    $success_message = "Senha alterada com sucesso!";
                } else {
                    $error_message = "Ocorreu um erro ao atualizar a senha.";
                }
            } else {
                $error_message = "A senha atual está incorreta.";
            }
        }
    }
}

// 4. Busca de Dados para Exibir no Formulário (GET)
$stmt = $pdo->prepare("SELECT * FROM perfis WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$perfil = $stmt->fetch();
if (!$perfil) { die("Erro crítico: Perfil não encontrado."); }

$stmt_plataformas = $pdo->query("SELECT * FROM plataformas_sociais ORDER BY id");
$plataformas_disponiveis = $stmt_plataformas->fetchAll();

$stmt_links = $pdo->prepare("SELECT plataforma_id, url_ou_usuario FROM links_sociais WHERE perfil_id = ?");
$stmt_links->execute([$perfil['id']]);
$links_salvos_raw = $stmt_links->fetchAll();
$links_salvos = [];
foreach ($links_salvos_raw as $link) { $links_salvos[$link['plataforma_id']] = $link['url_ou_usuario']; }

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle - ConectaBR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --background-color: #0D0D0F; --card-background: #161619; --border-color: rgba(255, 255, 255, 0.1); --text-color: #E4E4E7; --text-muted: #A1A1AA; --accent-1: #8b5cf6; --accent-2: #ec4899; --danger-color: #ef4444; --success-color: #10b981;}
        body { background-color: var(--background-color); color: var(--text-color); font-family: 'Inter', sans-serif; }
        
        /* --- CSS CORRIGIDO E MELHORADO PARA O CABEÇALHO --- */
        .navbar-custom { background: rgba(22, 22, 25, 0.8); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border-bottom: 1px solid var(--border-color); }
        .navbar-custom .navbar-brand { color: var(--text-color); font-weight: 700; }
        .navbar-custom .navbar-brand:hover { color: white; }
        .navbar-custom .nav-link { color: var(--text-muted); transition: color 0.3s ease; font-weight: 500;}
        .navbar-custom .nav-link:hover { color: var(--text-color); }

        .main-container { max-width: 800px; margin: 40px auto; padding: 2rem; }
        .form-card { background: var(--card-background); border: 1px solid var(--border-color); border-radius: 16px; padding: 2.5rem; }
        .form-label { font-weight: 500; color: var(--text-muted); }
        .form-control, .form-select { background-color: rgba(0,0,0,0.2); border: 1px solid var(--border-color); color: var(--text-color); border-radius: 8px; padding: 10px 14px;}
        .form-control:focus, .form-select:focus { color: var(--text-color); background-color: rgba(0,0,0,0.3); border-color: var(--accent-1); box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.3); }
        .form-control:-webkit-autofill { -webkit-box-shadow: 0 0 0 30px var(--background-color) inset !important; -webkit-text-fill-color: var(--text-color) !important;}
        .btn-primary { padding: 12px; font-weight: 700; color: white; background-image: linear-gradient(to right, var(--accent-1) 0%, var(--accent-2) 51%, var(--accent-1) 100%); background-size: 200% auto; border: none; transition: 0.5s; }
        .btn-primary:hover { background-position: right center; }
        .btn-danger { background-color: var(--danger-color); border-color: var(--danger-color); font-weight: 700; padding: 12px;}
        .input-group-text { background-color: rgba(0,0,0,0.3); border: 1px solid var(--border-color); color: var(--text-muted);}
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid transparent; font-weight: 500; }
        .alert-danger { background-color: rgba(239, 68, 68, 0.1); border-color: var(--danger-color); color: var(--danger-color); }
        .alert-success { background-color: rgba(16, 185, 129, 0.1); border-color: var(--success-color); color: var(--success-color); }
        select { -webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23a1a1aa' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 16px 12px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/painel/">Painel ConectaBR</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="/<?= htmlspecialchars($perfil['username']) ?>" target="_blank">Ver Perfil</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <?php if ($success_message): ?><div class="alert alert-success" role="alert"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
        <?php if ($error_message): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>

        <div class="form-card">
            <form action="/painel/" method="post" enctype="multipart/form-data">
                <h1 class="mb-4 fw-bold">Gerenciar Perfil</h1>
                <h5 class="mb-3 text-muted">Informações Públicas</h5>
                <div class="mb-3"><label for="nome_exibicao" class="form-label">Nome de Exibição</label><input type="text" class="form-control" name="nome_exibicao" value="<?= htmlspecialchars($perfil['nome_exibicao']) ?>"></div>
                <div class="mb-3"><label for="bio" class="form-label">Biografia</label><textarea class="form-control" name="bio" rows="4"><?= htmlspecialchars($perfil['bio']) ?></textarea></div>
                <hr class="my-4 border-secondary">
                <h5 class="mb-3 text-muted">Sua Localização</h5>
                <div class="row"><div class="col-md-8"><div class="mb-3"><label for="cidade" class="form-label">Cidade</label><input type="text" class="form-control" name="cidade" value="<?= htmlspecialchars($perfil['cidade'] ?? '') ?>"></div></div><div class="col-md-4"><div class="mb-3"><label for="estado" class="form-label">Estado</label><select class="form-select" name="estado"><option value="">Selecione...</option><option value="AC" <?= ($perfil['estado'] ?? '') == 'AC' ? 'selected' : '' ?>>Acre</option><option value="AL" <?= ($perfil['estado'] ?? '') == 'AL' ? 'selected' : '' ?>>Alagoas</option><option value="AP" <?= ($perfil['estado'] ?? '') == 'AP' ? 'selected' : '' ?>>Amapá</option><option value="AM" <?= ($perfil['estado'] ?? '') == 'AM' ? 'selected' : '' ?>>Amazonas</option><option value="BA" <?= ($perfil['estado'] ?? '') == 'BA' ? 'selected' : '' ?>>Bahia</option><option value="CE" <?= ($perfil['estado'] ?? '') == 'CE' ? 'selected' : '' ?>>Ceará</option><option value="DF" <?= ($perfil['estado'] ?? '') == 'DF' ? 'selected' : '' ?>>Distrito Federal</option><option value="ES" <?= ($perfil['estado'] ?? '') == 'ES' ? 'selected' : '' ?>>Espírito Santo</option><option value="GO" <?= ($perfil['estado'] ?? '') == 'GO' ? 'selected' : '' ?>>Goiás</option><option value="MA" <?= ($perfil['estado'] ?? '') == 'MA' ? 'selected' : '' ?>>Maranhão</option><option value="MT" <?= ($perfil['estado'] ?? '') == 'MT' ? 'selected' : '' ?>>Mato Grosso</option><option value="MS" <?= ($perfil['estado'] ?? '') == 'MS' ? 'selected' : '' ?>>Mato Grosso do Sul</option><option value="MG" <?= ($perfil['estado'] ?? '') == 'MG' ? 'selected' : '' ?>>Minas Gerais</option><option value="PA" <?= ($perfil['estado'] ?? '') == 'PA' ? 'selected' : '' ?>>Pará</option><option value="PB" <?= ($perfil['estado'] ?? '') == 'PB' ? 'selected' : '' ?>>Paraíba</option><option value="PR" <?= ($perfil['estado'] ?? '') == 'PR' ? 'selected' : '' ?>>Paraná</option><option value="PE" <?= ($perfil['estado'] ?? '') == 'PE' ? 'selected' : '' ?>>Pernambuco</option><option value="PI" <?= ($perfil['estado'] ?? '') == 'PI' ? 'selected' : '' ?>>Piauí</option><option value="RJ" <?= ($perfil['estado'] ?? '') == 'RJ' ? 'selected' : '' ?>>Rio de Janeiro</option><option value="RN" <?= ($perfil['estado'] ?? '') == 'RN' ? 'selected' : '' ?>>Rio Grande do Norte</option><option value="RS" <?= ($perfil['estado'] ?? '') == 'RS' ? 'selected' : '' ?>>Rio Grande do Sul</option><option value="RO" <?= ($perfil['estado'] ?? '') == 'RO' ? 'selected' : '' ?>>Rondônia</option><option value="RR" <?= ($perfil['estado'] ?? '') == 'RR' ? 'selected' : '' ?>>Roraima</option><option value="SC" <?= ($perfil['estado'] ?? '') == 'SC' ? 'selected' : '' ?>>Santa Catarina</option><option value="SP" <?= ($perfil['estado'] ?? '') == 'SP' ? 'selected' : '' ?>>São Paulo</option><option value="SE" <?= ($perfil['estado'] ?? '') == 'SE' ? 'selected' : '' ?>>Sergipe</option><option value="TO" <?= ($perfil['estado'] ?? '') == 'TO' ? 'selected' : '' ?>>Tocantins</option></select></div></div></div>
                <hr class="my-4 border-secondary">
                <h5 class="mb-3 text-muted">Foto de Perfil</h5>
                <div class="mb-3"><label class="form-label">Foto Atual</label><div><img src="<?= htmlspecialchars($perfil['foto_perfil_url'] ?: 'https://i.pravatar.cc/150?u=' . $perfil['username']) ?>" alt="Foto de Perfil" class="rounded-circle" width="100" height="100" style="object-fit: cover;"></div></div>
                <div class="mb-3"><label for="foto_perfil" class="form-label">Subir Nova Foto</label><input type="file" class="form-control" name="foto_perfil" accept="image/png, image/jpeg, image/gif, image/webp"><div class="form-text" style="color: var(--text-muted);">Deixe em branco para manter a foto atual. Limite de 2MB.</div></div>
                <hr class="my-4 border-secondary">
                <h5 class="mb-3 text-muted">Links e Redes Sociais</h5>
                <?php foreach ($plataformas_disponiveis as $plataforma): ?>
                    <div class="mb-3"><label class="form-label"><?= htmlspecialchars($plataforma['nome']) ?></label><div class="input-group"><span class="input-group-text" style="width: 45px; justify-content: center;"><?= $plataforma['icone_svg'] ?></span><input type="text" class="form-control" name="links_sociais[<?= $plataforma['id'] ?>]" placeholder="<?= $plataforma['nome'] === 'Website' ? 'https://seu-site-completo.com' : 'seu_usuario_na_rede' ?>" value="<?= htmlspecialchars($links_salvos[$plataforma['id']] ?? '') ?>"></div></div>
                <?php endforeach; ?>
                <hr class="my-4 border-secondary">
                <div class="d-grid"><button type="submit" name="salvar_perfil" class="btn btn-primary btn-lg">Salvar Alterações do Perfil</button></div>
            </form>

            <hr class="my-5 border-secondary">

            <form action="/painel/" method="post">
                <h2 class="mb-4 fw-bold">Segurança</h2>
                <h5 class="mb-3 text-muted">Alterar Senha</h5>
                <div class="mb-3"><label for="senha_atual" class="form-label">Senha Atual</label><input type="password" class="form-control" name="senha_atual" required></div>
                <div class="mb-3"><label for="nova_senha" class="form-label">Nova Senha</label><input type="password" class="form-control" name="nova_senha" required></div>
                <div class="mb-3"><label for="confirmar_nova_senha" class="form-label">Confirmar Nova Senha</label><input type="password" class="form-control" name="confirmar_nova_senha" required></div>
                <div class="d-grid mt-4"><button type="submit" name="alterar_senha" class="btn btn-danger">Alterar Minha Senha</button></div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>