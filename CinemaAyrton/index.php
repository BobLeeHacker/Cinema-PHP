<?php
// Set page title
$pageTitle = 'Início';

// Include header
require_once 'includes/header.php';

// Fetch the latest movies (limit to 6)
$stmt = $pdo->prepare("SELECT id, title, director, release_year, genre, duration, rating, poster, synopsis 
                     FROM movies 
                     WHERE is_active = TRUE 
                     ORDER BY created_at DESC 
                     LIMIT 6");
$stmt->execute();
$latestMovies = $stmt->fetchAll();

// Fetch top-rated movies (limit to 3)
$stmt = $pdo->prepare("SELECT id, title, director, release_year, genre, duration, rating, poster, synopsis 
                     FROM movies 
                     WHERE is_active = TRUE 
                     ORDER BY rating DESC 
                     LIMIT 3");
$stmt->execute();
$topRatedMovies = $stmt->fetchAll();
?>

<!-- Hero Section -->
<section class="py-5 text-center container">
    <div class="row py-lg-5">
        <div class="col-lg-8 col-md-10 mx-auto">
            <h1 class="fw-bold">Cinema DB</h1>
            <p class="lead">Sua plataforma completa para gerenciar sua coleção de filmes favoritos</p>
            <p class="mb-4">Cadastre, pesquise e organize seus filmes de maneira simples e eficiente.</p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <a href="movies.php" class="btn btn-primary btn-lg px-4 gap-3">
                    <i class="fas fa-film me-2"></i>Ver Filmes
                </a>
                <?php if (!isLoggedIn()): ?>
                <a href="register.php" class="btn btn-outline-secondary btn-lg px-4">
                    <i class="fas fa-user-plus me-2"></i>Cadastre-se
                </a>
                <?php else: ?>
                <a href="dashboard.php" class="btn btn-outline-info btn-lg px-4">
                    <i class="fas fa-tachometer-alt me-2"></i>Meu Painel
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Featured Section -->
<section class="py-4">
    <div class="container">
        <h2 class="mb-4">Filmes em Destaque</h2>
        <div class="row">
            <?php if (count($topRatedMovies) > 0): ?>
                <?php foreach ($topRatedMovies as $movie): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php if (!empty($movie['poster'])): ?>
                                <img src="<?php echo htmlspecialchars($movie['poster']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-dark d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-film fa-4x text-light opacity-50"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($movie['title']); ?></h5>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="fas fa-user-alt me-1"></i> <?php echo htmlspecialchars($movie['director'] ?: 'Desconhecido'); ?> | 
                                        <i class="fas fa-calendar-alt me-1"></i> <?php echo htmlspecialchars($movie['release_year'] ?: 'N/A'); ?>
                                    </small>
                                </p>
                                <div class="mb-2">
                                    <?php 
                                    // Display rating as stars (out of 5)
                                    $rating = round($movie['rating'] / 2);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '<i class="fas fa-star text-warning"></i>';
                                        } else {
                                            echo '<i class="far fa-star text-warning"></i>';
                                        }
                                    }
                                    ?> 
                                    <small class="text-muted">(<?php echo number_format($movie['rating'], 1); ?>/10)</small>
                                </div>
                                <p class="card-text">
                                    <?php 
                                    if (!empty($movie['synopsis'])) {
                                        echo htmlspecialchars(substr($movie['synopsis'], 0, 100)) . '...';
                                    } else {
                                        echo 'Sem sinopse disponível.';
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="card-footer">
                                <a href="movie_view.php?id=<?php echo $movie['id']; ?>" class="btn btn-info w-100">
                                    <i class="fas fa-eye me-1"></i> Ver Detalhes
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Nenhum filme cadastrado ainda.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Latest Movies Section -->
<section class="py-4 bg-dark">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Adicionados Recentemente</h2>
            <a href="movies.php" class="btn btn-outline-light">
                <i class="fas fa-list me-1"></i> Ver Todos
            </a>
        </div>
        <div class="row">
            <?php if (count($latestMovies) > 0): ?>
                <?php foreach ($latestMovies as $movie): ?>
                    <div class="col-md-4 col-lg-2 mb-4">
                        <div class="card h-100">
                            <?php if (!empty($movie['poster'])): ?>
                                <img src="<?php echo htmlspecialchars($movie['poster']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-dark d-flex align-items-center justify-content-center" style="height: 150px;">
                                    <i class="fas fa-film fa-3x text-light opacity-50"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body p-2">
                                <h6 class="card-title"><?php echo htmlspecialchars($movie['title']); ?></h6>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i> <?php echo htmlspecialchars($movie['release_year'] ?: 'N/A'); ?>
                                    </small>
                                </p>
                            </div>
                            <div class="card-footer p-2">
                                <a href="movie_view.php?id=<?php echo $movie['id']; ?>" class="btn btn-sm btn-outline-info w-100">
                                    <i class="fas fa-eye me-1"></i> Ver
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        Nenhum filme cadastrado ainda.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Recursos do Cinema DB</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-film fa-3x text-info"></i>
                        </div>
                        <h4>Gerenciamento de Filmes</h4>
                        <p>Cadastre e organize sua coleção de filmes com detalhes como diretor, ano, gênero e classificação.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-search fa-3x text-info"></i>
                        </div>
                        <h4>Busca Avançada</h4>
                        <p>Encontre rapidamente qualquer filme em sua coleção com nossa poderosa ferramenta de busca.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-user-shield fa-3x text-info"></i>
                        </div>
                        <h4>Área Pessoal</h4>
                        <p>Acesse sua área privada para gerenciar seus filmes e personalizar seu perfil de usuário.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 bg-info text-dark">
    <div class="container text-center">
        <h2 class="mb-3">Comece a organizar sua coleção hoje mesmo</h2>
        <p class="lead mb-4">Junte-se a milhares de cinéfilos que já estão utilizando o Cinema DB</p>
        <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn btn-lg btn-dark">
                <i class="fas fa-user-plus me-2"></i>Criar uma Conta
            </a>
        <?php else: ?>
            <a href="movie_add.php" class="btn btn-lg btn-dark">
                <i class="fas fa-plus me-2"></i>Adicionar Novo Filme
            </a>
        <?php endif; ?>
    </div>
</section>

<?php
// Include footer
require_once 'includes/footer.php';
?>
