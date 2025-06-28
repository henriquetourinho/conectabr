<?php
require_once 'php/conexao.php';

$request_uri = $_SERVER['REQUEST_URI'];
$username = trim($request_uri, '/');

if (empty($username)) {
    header("Location: /perfis/");
    exit();
}

$sql = "SELECT p.nome_exibicao, p.bio, p.foto_perfil_url, p.cidade, p.estado, GROUP_CONCAT(CONCAT_WS('::', ps.nome, ls.url_ou_usuario, ps.url_base, ps.icone_svg) SEPARATOR '||') as redes_sociais FROM perfis p LEFT JOIN links_sociais ls ON p.id = ls.perfil_id LEFT JOIN plataformas_sociais ps ON ls.plataforma_id = ps.id WHERE p.username = ? AND p.is_publico = TRUE GROUP BY p.id";
$stmt = $pdo->prepare($sql);
$stmt->execute([$username]);
$perfil = $stmt->fetch();

if (!$perfil) {
    header("HTTP/1.0 404 Not Found");
    echo "<!DOCTYPE html><html lang='pt-br'><head><title>404 - Perfil Não Encontrado</title><link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'></head><body class='bg-light'><div class='container text-center vh-100 d-flex justify-content-center align-items-center'><div class='card p-5 shadow-sm'><h1>404</h1><p class='lead'>O perfil <strong>@" . htmlspecialchars($username) . "</strong> não foi encontrado.</p><a href='/perfis/' class='btn btn-primary mt-3'>Ver todos os perfis</a></div></div></body></html>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($perfil['nome_exibicao']) ?> - ConectaBR</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root { --background-color: #0D0D0F; --card-background: rgba(22, 22, 25, 0.5); --border-color: rgba(255, 255, 255, 0.1); --text-color: #E4E4E7; --text-muted: #A1A1AA; --accent-1: #8b5cf6; --accent-2: #ec4899; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: var(--background-color); color: var(--text-color); font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 2rem; position: relative; overflow: hidden; }
        body::before, body::after { content: ''; position: fixed; width: 600px; height: 600px; border-radius: 50%; filter: blur(150px); z-index: -1; opacity: 0.4; }
        body::before { background-color: var(--accent-1); top: -20%; left: -20%; animation: move-aurora-1 20s infinite alternate; }
        body::after { background-color: var(--accent-2); bottom: -20%; right: -20%; animation: move-aurora-2 25s infinite alternate; }
        @keyframes move-aurora-1 { from { transform: translate(0, 0) rotate(0deg); } to { transform: translate(200px, 100px) rotate(360deg); } }
        @keyframes move-aurora-2 { from { transform: translate(0, 0) rotate(0deg); } to { transform: translate(-200px, -100px) rotate(-360deg); } }
        
        /* --- NOVO LINK FIXO PARA A HOME --- */
        .home-link {
            position: fixed;
            top: 25px;
            left: 25px;
            z-index: 100;
            background: var(--card-background);
            color: var(--text-color);
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            border: 1px solid var(--border-color);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .home-link:hover {
            background: var(--text-color);
            color: var(--background-color);
        }

        .main-container { width: 100%; max-width: 600px; }
        .profile-card-main { background: var(--card-background); border: 1px solid var(--border-color); border-radius: 24px; padding: 40px; text-align: center; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); animation: fade-in 1s ease-out; }
        @keyframes fade-in { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .profile-pic { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 1.5rem; border: 4px solid var(--border-color); box-shadow: 0 0 20px rgba(0,0,0,0.5); }
        .profile-name { font-size: 2.25rem; font-weight: 700; margin-bottom: 0.5rem; background: linear-gradient(90deg, var(--accent-1), var(--accent-2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .profile-location { margin-top: -0.5rem; margin-bottom: 1rem; }
        .profile-location a { color: var(--text-muted); font-size: 1rem; text-decoration: none; transition: color 0.3s ease; }
        .profile-location a:hover { color: var(--text-color); }
        .profile-location .fa-map-marker-alt { margin-right: 0.5rem; color: var(--accent-2); }
        .profile-bio { color: var(--text-muted); font-size: 1rem; line-height: 1.6; margin-top: 1rem; }
        .divider { border: none; height: 1px; background: var(--border-color); margin: 2rem 0; }
        .social-links a { font-size: 1.75rem; margin: 0 12px; color: var(--text-muted); text-decoration: none; transition: color 0.3s ease, transform 0.3s ease; }
        .social-links a:hover { color: var(--text-color); transform: scale(1.1); }
        .back-button { display: inline-block; margin-top: 2rem; padding: 10px 20px; background: var(--card-background); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-muted); text-decoration: none; transition: all 0.3s ease; }
        .back-button:hover { background: var(--border-color); color: var(--text-color); }
    </style>
</head>
<body>
    <a href="/" class="home-link">ConectaBR</a>

    <div class="main-container">
        <div class="profile-card-main">
            <img src="<?= htmlspecialchars($perfil['foto_perfil_url'] ?: 'https://i.pravatar.cc/150?u=' . $perfil['username']) ?>" alt="Foto de Perfil" class="profile-pic">
            <h1 class="profile-name"><?= htmlspecialchars($perfil['nome_exibicao']) ?></h1>
            <?php if (!empty($perfil['cidade']) && !empty($perfil['estado'])): ?>
                <p class="profile-location">
                    <a href="/perfis/?uf=<?= htmlspecialchars($perfil['estado']) ?>" title="Ver mais perfis de <?= htmlspecialchars($perfil['estado']) ?>">
                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($perfil['cidade']) ?>, <?= htmlspecialchars($perfil['estado']) ?>
                    </a>
                </p>
            <?php endif; ?>
            <?php if (!empty($perfil['bio'])): ?><p class="profile-bio"><?= nl2br(htmlspecialchars($perfil['bio'])) ?></p><?php endif; ?>
            <?php if ($perfil['redes_sociais']): ?>
                <hr class="divider">
                <div class="social-links">
                    <?php
                    $redes = explode('||', $perfil['redes_sociais']);
                    foreach ($redes as $rede) {
                        list($nome, $user_ou_url, $url_base, $icone) = explode('::', $rede);
                        $link_completo = !empty($url_base) ? $url_base . $user_ou_url : $user_ou_url;
                        echo "<a href='" . htmlspecialchars($link_completo) . "' target='_blank' title='" . htmlspecialchars($nome) . "'>" . ($icone ?: "<i class='fas fa-link'></i>") . "</a>";
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center">
            <a href="/perfis/" class="back-button">← Voltar para Todos os Perfis</a>
        </div>
    </div>
</body>
</html>