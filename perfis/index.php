<?php
require_once '../php/conexao.php';

// A lógica PHP para busca e filtro continua a mesma
$search_query = $_GET['q'] ?? '';
$search_state = $_GET['uf'] ?? '';

$sql = "SELECT nome_exibicao, username, bio, foto_perfil_url, cidade, estado 
        FROM perfis 
        WHERE is_publico = TRUE";
$params = [];

if (!empty($search_query)) {
    $sql .= " AND (nome_exibicao LIKE ? OR username LIKE ?)";
    $params[] = "%" . $search_query . "%";
    $params[] = "%" . $search_query . "%";
}
if (!empty($search_state)) {
    $sql .= " AND estado = ?";
    $params[] = $search_state;
}
$sql .= " ORDER BY nome_exibicao ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$perfis = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConectaBR - Ache Criadores Brasileiros</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root { --background-color: #111113; --card-background: rgba(30, 30, 33, 0.5); --border-color: rgba(255, 255, 255, 0.1); --text-color: #E4E4E7; --text-muted: #A1A1AA; --spotlight-color: rgba(100, 100, 150, 0.05); }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: var(--background-color); color: var(--text-color); font-family: 'Inter', sans-serif; min-height: 100vh; background-image: radial-gradient( circle at var(--mouse-x) var(--mouse-y), var(--spotlight-color), transparent 20% ); }
        .main-header { text-align: center; padding: 80px 20px 60px 20px; }
        .main-header h1 { font-size: 3rem; font-weight: 700; background: linear-gradient(90deg, #a78bfa, #f472b6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem; }
        .main-header p { font-size: 1.125rem; color: var(--text-muted); max-width: 600px; margin: auto; }
        #profiles-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 2rem; padding: 2rem; max-width: 1200px; margin: auto; }
        .profile-card { background: var(--card-background); border: 1px solid var(--border-color); border-radius: 16px; padding: 24px; text-align: center; text-decoration: none; color: var(--text-color); position: relative; overflow: hidden; transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), background 0.4s ease, border-color 0.4s ease; backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
        .profile-card:hover { border-color: rgba(255, 255, 255, 0.25); transform: scale(1.05); background: rgba(30, 30, 33, 0.8); }
        .profile-card-image { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem; border: 3px solid transparent; transition: border-color 0.3s ease; }
        .profile-card:hover .profile-card-image { border-color: #a78bfa; }
        .profile-card-name { font-size: 1.25rem; font-weight: 500; margin-bottom: 0.25rem; }
        .profile-card-username { font-size: 0.9rem; color: var(--text-muted); }
        .profile-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient( circle at var(--mouse-x-card) var(--mouse-y-card), rgba(255, 255, 255, 0.1), transparent 40% ); opacity: 0; transition: opacity 0.5s; }
        .profile-card:hover::before { opacity: 1; }
        .search-form { background-color: rgba(30, 30, 33, 0.7); padding: 2rem; border-radius: 16px; border: 1px solid var(--border-color); backdrop-filter: blur(10px); max-width: 800px; margin: -2rem auto 4rem auto; position: relative; z-index: 10; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">ConectaBR</a>
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="/painel/">Meu Painel</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/login/">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <header class="main-header">
        <h1>Nossa Constelação de Criadores</h1>
        <p>Explore a galáxia de talentos em nossa comunidade. Cada perfil é uma estrela com sua própria história para contar.</p>
    </header>

    <main>
        <div class="container">
            <form action="/perfis/" method="get" class="search-form">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6"><label for="q" class="form-label text-muted">Achar por nome ou usuário</label><input type="search" class="form-control form-control-lg bg-dark text-white border-secondary" id="q" name="q" value="<?= htmlspecialchars($search_query) ?>"></div>
                    <div class="col-md-4"><label for="uf" class="form-label text-muted">...em qual estado?</label><select class="form-select form-select-lg bg-dark text-white border-secondary" id="uf" name="uf"><option value="">Todos os Estados</option><option value="AC" <?= $search_state == 'AC' ? 'selected' : '' ?>>Acre</option><option value="AL" <?= $search_state == 'AL' ? 'selected' : '' ?>>Alagoas</option><option value="AP" <?= $search_state == 'AP' ? 'selected' : '' ?>>Amapá</option><option value="AM" <?= $search_state == 'AM' ? 'selected' : '' ?>>Amazonas</option><option value="BA" <?= $search_state == 'BA' ? 'selected' : '' ?>>Bahia</option><option value="CE" <?= $search_state == 'CE' ? 'selected' : '' ?>>Ceará</option><option value="DF" <?= $search_state == 'DF' ? 'selected' : '' ?>>Distrito Federal</option><option value="ES" <?= $search_state == 'ES' ? 'selected' : '' ?>>Espírito Santo</option><option value="GO" <?= $search_state == 'GO' ? 'selected' : '' ?>>Goiás</option><option value="MA" <?= $search_state == 'MA' ? 'selected' : '' ?>>Maranhão</option><option value="MT" <?= $search_state == 'MT' ? 'selected' : '' ?>>Mato Grosso</option><option value="MS" <?= $search_state == 'MS' ? 'selected' : '' ?>>Mato Grosso do Sul</option><option value="MG" <?= $search_state == 'MG' ? 'selected' : '' ?>>Minas Gerais</option><option value="PA" <?= $search_state == 'PA' ? 'selected' : '' ?>>Pará</option><option value="PB" <?= $search_state == 'PB' ? 'selected' : '' ?>>Paraíba</option><option value="PR" <?= $search_state == 'PR' ? 'selected' : '' ?>>Paraná</option><option value="PE" <?= $search_state == 'PE' ? 'selected' : '' ?>>Pernambuco</option><option value="PI" <?= $search_state == 'PI' ? 'selected' : '' ?>>Piauí</option><option value="RJ" <?= $search_state == 'RJ' ? 'selected' : '' ?>>Rio de Janeiro</option><option value="RN" <?= $search_state == 'RN' ? 'selected' : '' ?>>Rio Grande do Norte</option><option value="RS" <?= $search_state == 'RS' ? 'selected' : '' ?>>Rio Grande do Sul</option><option value="RO" <?= $search_state == 'RO' ? 'selected' : '' ?>>Rondônia</option><option value="RR" <?= $search_state == 'RR' ? 'selected' : '' ?>>Roraima</option><option value="SC" <?= $search_state == 'SC' ? 'selected' : '' ?>>Santa Catarina</option><option value="SP" <?= $search_state == 'SP' ? 'selected' : '' ?>>São Paulo</option><option value="SE" <?= $search_state == 'SE' ? 'selected' : '' ?>>Sergipe</option><option value="TO" <?= $search_state == 'TO' ? 'selected' : '' ?>>Tocantins</option></select></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary btn-lg w-100">Buscar</button></div>
                </div>
            </form>
        </div>
        <div id="profiles-container">
            <?php if (empty($perfis)): ?>
                <p style="text-align: center; grid-column: 1 / -1; color: var(--text-muted);">Nenhum perfil encontrado com esses critérios. Tente uma nova busca!</p>
            <?php else: ?>
                <?php foreach ($perfis as $perfil): ?>
                    <a href="/<?= htmlspecialchars($perfil['username']) ?>" class="profile-card">
                        <img src="<?= htmlspecialchars($perfil['foto_perfil_url'] ?: 'https://i.pravatar.cc/150?u=' . $perfil['username']) ?>" alt="Foto de <?= htmlspecialchars($perfil['nome_exibicao']) ?>" class="profile-card-image">
                        <h3 class="profile-card-name"><?= htmlspecialchars($perfil['nome_exibicao']) ?></h3>
                        <p class="profile-card-username">@<?= htmlspecialchars($perfil['username']) ?></p>
                        <?php if(!empty($perfil['cidade'])): ?>
                            <p class="text-muted small mt-2"><i class="fas fa-map-marker-alt fa-xs"></i> <?= htmlspecialchars($perfil['cidade']) ?>, <?= htmlspecialchars($perfil['estado']) ?></p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <script>
        const container = document.body;
        container.addEventListener('mousemove', e => { container.style.setProperty('--mouse-x', e.clientX + 'px'); container.style.setProperty('--mouse-y', e.clientY + 'px'); });
        const cards = document.querySelectorAll('.profile-card');
        cards.forEach(card => { card.addEventListener('mousemove', e => { const rect = card.getBoundingClientRect(); const x = e.clientX - rect.left; const y = e.clientY - rect.top; card.style.setProperty('--mouse-x-card', x + 'px'); card.style.setProperty('--mouse-y-card', y + 'px'); }); });
    </script>
</body>
</html>