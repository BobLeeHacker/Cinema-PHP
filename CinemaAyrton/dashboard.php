<?php
// Set page title
$pageTitle = 'Painel de Controle';

// Include necessary files
require_once 'includes/header.php';
require_once 'includes/auth.php';

// Require login
requireLogin();

// Get current user
$user = getCurrentUser($pdo);

// Get user stats
// Count user's movies
$stmt = $pdo->prepare("SELECT COUNT(*) FROM movies WHERE user_id = ? AND is_active = TRUE");
$stmt->execute([$user['id']]);
$movieCount = $stmt->fetchColumn();

// Get user's latest movies (limit to 5)
$stmt = $pdo->prepare("SELECT id, title, director, release_year, genre, rating, created_at 
                      FROM movies 
                      WHERE user_id = ? AND is_active = TRUE 
                      ORDER BY created_at DESC 
                      LIMIT 5");
$stmt->execute([$user['id']]);
$latestMovies = $stmt->fetchAll();

// Get some stats for admin
$isAdmin = isAdmin($pdo);
if ($isAdmin) {
    // Count total users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_active = TRUE");
    $stmt->execute();
    $totalUsers = $stmt->fetchColumn();
    
    // Count total movies
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM movies WHERE is_active = TRUE");
    $stmt->execute();
    $totalMovies = $stmt->fetchColumn();
    
    // Get latest users (limit to 5)
    $stmt = $pdo->prepare("SELECT id, username, email, created_at FROM users WHERE is_active = TRUE ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $latestUsers = $stmt->fetchAll();
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Início</a></li>
        <li class="breadcrumb-item active" aria-current="page">Painel</li>
    </ol>
</nav>

<!-- Dashboard Header -->
<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h1 class="h3">Bem-vindo, <?php echo htmlspecialchars($user['username']); ?>!</h1>
        <p class="text-muted">Gerencie seus filmes e atualize seu perfil.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="movie_add.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Adicionar Filme
        </a>
    </div>
</div>

<!-- Dashboard Stats -->
<div class="row">
    <div class="col-md-4">
        <div class="card dashboard-stat bg-info text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Meus Filmes</h5>
                        <h2 class="mb-0"><?php echo $movieCount; ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-film fa-3x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="movies.php" class="text-white text-decoration-none">Ver Todos</a>
                <div><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    
    <?php if ($isAdmin): ?>
    <div class="col-md-4">
        <div class="card dashboard-stat bg-success text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Usuários Ativos</h5>
                        <h2 class="mb-0"><?php echo $totalUsers; ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-users fa-3x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="#" class="text-white text-decoration-none">Ver Detalhes</a>
                <div><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card dashboard-stat bg-warning text-dark mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Total de Filmes</h5>
                        <h2 class="mb-0"><?php echo $totalMovies; ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-video fa-3x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="#" class="text-dark text-decoration-none">Ver Detalhes</a>
                <div><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="col-md-4">
        <div class="card dashboard-stat bg-primary text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Perfil</h5>
                        <p class="mb-0">Gerencie suas informações</p>
                    </div>
                    <div>
                        <i class="fas fa-user fa-3x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="profile.php" class="text-white text-decoration-none">Editar Perfil</a>
                <div><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card dashboard-stat bg-secondary text-white mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title">Descobrir</h5>
                        <p class="mb-0">Explore todos os filmes</p>
                    </div>
                    <div>
                        <i class="fas fa-search fa-3x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="movies.php" class="text-white text-decoration-none">Explorar</a>
                <div><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Dashboard Content -->
<div class="row">
    <div class="col-lg-8">
        <!-- Latest Movies -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Seus Filmes Recentes</h5>
                <a href="movies.php" class="btn btn-sm btn-outline-primary">Ver Todos</a>
            </div>
            <div class="card-body">
                <?php if (count($latestMovies) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Diretor</th>
                                    <th>Ano</th>
                                    <th>Classificação</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latestMovies as $movie): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($movie['title']); ?></td>
                                        <td><?php echo htmlspecialchars($movie['director'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($movie['release_year'] ?: 'N/A'); ?></td>
                                        <td>
                                            <div class="rating-stars">
                                                <?php 
                                                // Display rating as stars (out of 5)
                                                $rating = round($movie['rating'] / 2);
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $rating) {
                                                        echo '<i class="fas fa-star"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star"></i>';
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="movie_view.php?id=<?php echo $movie['id']; ?>" class="btn btn-info" data-bs-toggle="tooltip" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="movie_edit.php?id=<?php echo $movie['id']; ?>" class="btn btn-warning" data-bs-toggle="tooltip" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="movies.php?delete=<?php echo $movie['id']; ?>" class="btn btn-danger delete-movie" data-bs-toggle="tooltip" title="Excluir">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p class="mb-0">Você ainda não cadastrou nenhum filme. <a href="movie_add.php">Adicionar filme</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Search Movies (AJAX) -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Pesquisar Filmes</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <input type="text" id="dashboard-search" class="form-control" placeholder="Pesquisar por título, diretor, gênero...">
                    </div>
                    <div class="col-md-4">
                        <select id="sort-movies" class="form-select">
                            <option value="title_asc">Título (A-Z)</option>
                            <option value="title_desc">Título (Z-A)</option>
                            <option value="year_desc">Ano (Mais recente)</option>
                            <option value="year_asc">Ano (Mais antigo)</option>
                            <option value="rating_desc">Classificação (Maior)</option>
                            <option value="rating_asc">Classificação (Menor)</option>
                        </select>
                    </div>
                </div>
                <div id="movies-container"></div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- User Profile Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Seu Perfil</h5>
            </div>
            <div class="card-body text-center">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['username']); ?>&background=random&size=100" class="rounded-circle mb-3" alt="Avatar">
                <h5><?php echo htmlspecialchars($user['username']); ?></h5>
                <p class="text-muted">
                    <?php if (!empty($user['first_name']) && !empty($user['last_name'])): ?>
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    <?php else: ?>
                        <i>Perfil incompleto</i>
                    <?php endif; ?>
                </p>
                <p><span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'info'; ?>"><?php echo ucfirst($user['role']); ?></span></p>
                <div class="d-grid gap-2">
                    <a href="profile.php" class="btn btn-primary">
                        <i class="fas fa-user-edit me-2"></i>Editar Perfil
                    </a>
                </div>
            </div>
        </div>
        
        <?php if ($isAdmin): ?>
        <!-- Latest Users (Admin only) -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Usuários Recentes</h5>
            </div>
            <div class="card-body">
                <?php if (count($latestUsers) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($latestUsers as $luser): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($luser['username']); ?></h6>
                                    <small class="text-muted"><?php echo formatDate($luser['created_at'], 'd/m/Y'); ?></small>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars($luser['email']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p class="mb-0">Não há usuários recentes.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- Quick Links -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Links Rápidos</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="movies.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-film me-2"></i>Listar Todos os Filmes</span>
                    <i class="fas fa-chevron-right text-muted"></i>
                </a>
                <a href="movie_add.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-plus me-2"></i>Adicionar Novo Filme</span>
                    <i class="fas fa-chevron-right text-muted"></i>
                </a>
                <a href="profile.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-user-edit me-2"></i>Editar Perfil</span>
                    <i class="fas fa-chevron-right text-muted"></i>
                </a>
                <a href="logout.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-sign-out-alt me-2"></i>Sair</span>
                    <i class="fas fa-chevron-right text-muted"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
