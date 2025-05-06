<?php
// Include necessary files
require_once 'includes/header.php';
require_once 'includes/auth.php';

// Check if movie ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('ID do filme inválido.', 'danger');
    redirect('movies.php');
}

$movieId = (int)$_GET['id'];

// Get movie details with user who added it
$stmt = $pdo->prepare("SELECT m.*, u.username 
                      FROM movies m 
                      LEFT JOIN users u ON m.user_id = u.id 
                      WHERE m.id = ? AND m.is_active = TRUE");
$stmt->execute([$movieId]);
$movie = $stmt->fetch();

// Check if movie exists
if (!$movie) {
    setFlashMessage('Filme não encontrado.', 'danger');
    redirect('movies.php');
}

// Set page title
$pageTitle = $movie['title'];

// Process review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && isset($_POST['submit_review'])) {
    $rating = $_POST['review_rating'] ?? 0;
    $comment = $_POST['review_comment'] ?? '';
    
    // Basic validation
    $errors = [];
    
    if (!is_numeric($rating) || $rating < 0 || $rating > 10) {
        $errors[] = 'Classificação deve ser um número entre 0 e 10.';
    }
    
    // If no errors, add or update review
    if (empty($errors)) {
        try {
            // Check if user already reviewed this movie
            $checkStmt = $pdo->prepare("SELECT id FROM reviews WHERE movie_id = ? AND user_id = ?");
            $checkStmt->execute([$movieId, $_SESSION['user_id']]);
            $existingReview = $checkStmt->fetch();
            
            if ($existingReview) {
                // Update existing review
                $reviewStmt = $pdo->prepare("UPDATE reviews 
                                           SET rating = ?, comment = ?, updated_at = CURRENT_TIMESTAMP 
                                           WHERE id = ?");
                $result = $reviewStmt->execute([$rating, $comment, $existingReview['id']]);
                $message = 'Avaliação atualizada com sucesso!';
            } else {
                // Add new review
                $reviewStmt = $pdo->prepare("INSERT INTO reviews (movie_id, user_id, rating, comment) 
                                           VALUES (?, ?, ?, ?)");
                $result = $reviewStmt->execute([$movieId, $_SESSION['user_id'], $rating, $comment]);
                $message = 'Avaliação adicionada com sucesso!';
            }
            
            if ($result) {
                // Update movie average rating
                $updateRatingStmt = $pdo->prepare("UPDATE movies m
                                                SET rating = (SELECT AVG(rating) FROM reviews WHERE movie_id = ? AND is_active = TRUE)
                                                WHERE m.id = ?");
                $updateRatingStmt->execute([$movieId, $movieId]);
                
                setFlashMessage($message, 'success');
                redirect('movie_view.php?id=' . $movieId);
            } else {
                $errors[] = 'Erro ao salvar avaliação. Por favor, tente novamente.';
            }
        } catch (PDOException $e) {
            error_log("Review error: " . $e->getMessage());
            $errors[] = 'Ocorreu um erro no sistema. Por favor, tente novamente mais tarde.';
        }
    }
}

// Get current user's review if logged in
$userReview = null;
if (isLoggedIn()) {
    $reviewStmt = $pdo->prepare("SELECT * FROM reviews WHERE movie_id = ? AND user_id = ? AND is_active = TRUE");
    $reviewStmt->execute([$movieId, $_SESSION['user_id']]);
    $userReview = $reviewStmt->fetch();
}

// Get other reviews
$reviewsStmt = $pdo->prepare("SELECT r.*, u.username 
                            FROM reviews r 
                            JOIN users u ON r.user_id = u.id 
                            WHERE r.movie_id = ? AND r.is_active = TRUE 
                            AND (r.user_id != ? OR ? IS NULL)
                            ORDER BY r.created_at DESC");
$reviewsStmt->execute([
    $movieId, 
    isLoggedIn() ? $_SESSION['user_id'] : null,
    isLoggedIn() ? $_SESSION['user_id'] : null
]);
$reviews = $reviewsStmt->fetchAll();

// Get similar movies (same genre)
if (!empty($movie['genre'])) {
    $similarStmt = $pdo->prepare("SELECT id, title, poster, release_year, rating 
                                FROM movies 
                                WHERE genre = ? AND id != ? AND is_active = TRUE 
                                ORDER BY rating DESC, title ASC
                                LIMIT 4");
    $similarStmt->execute([$movie['genre'], $movieId]);
    $similarMovies = $similarStmt->fetchAll();
} else {
    $similarMovies = [];
}
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Início</a></li>
        <li class="breadcrumb-item"><a href="movies.php">Filmes</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($movie['title']); ?></li>
    </ol>
</nav>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Movie Details -->
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <?php if (!empty($movie['poster'])): ?>
                            <img src="<?php echo htmlspecialchars($movie['poster']); ?>" class="img-fluid rounded movie-poster" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                        <?php else: ?>
                            <div class="card-img-top bg-dark d-flex align-items-center justify-content-center movie-poster rounded">
                                <i class="fas fa-film fa-5x text-light opacity-50"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                        <h1 class="h3 mb-2"><?php echo htmlspecialchars($movie['title']); ?></h1>
                        
                        <div class="mb-3">
                            <?php 
                            // Display rating as stars (out of 5)
                            $rating = round($movie['rating'] / 2);
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="fas fa-star fa-lg text-warning"></i>';
                                } else {
                                    echo '<i class="far fa-star fa-lg text-warning"></i>';
                                }
                            }
                            ?> 
                            <span class="ms-2 fs-5"><?php echo number_format($movie['rating'], 1); ?>/10</span>
                        </div>
                        
                        <table class="table table-sm">
                            <?php if (!empty($movie['director'])): ?>
                            <tr>
                                <th scope="row" width="150">Diretor:</th>
                                <td><?php echo htmlspecialchars($movie['director']); ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if (!empty($movie['release_year'])): ?>
                            <tr>
                                <th scope="row">Ano:</th>
                                <td><?php echo htmlspecialchars($movie['release_year']); ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if (!empty($movie['genre'])): ?>
                            <tr>
                                <th scope="row">Gênero:</th>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($movie['genre']); ?></span>
                                </td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if (!empty($movie['duration'])): ?>
                            <tr>
                                <th scope="row">Duração:</th>
                                <td><?php echo htmlspecialchars($movie['duration']); ?> minutos</td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr>
                                <th scope="row">Adicionado por:</th>
                                <td><?php echo htmlspecialchars($movie['username'] ?? 'Usuário desconhecido'); ?></td>
                            </tr>
                            
                            <tr>
                                <th scope="row">Adicionado em:</th>
                                <td><?php echo formatDate($movie['created_at']); ?></td>
                            </tr>
                        </table>
                        
                        <?php if (isLoggedIn() && ($movie['user_id'] == $_SESSION['user_id'] || isAdmin($pdo))): ?>
                            <div class="d-flex mt-3">
                                <a href="movie_edit.php?id=<?php echo $movie['id']; ?>" class="btn btn-warning me-2">
                                    <i class="fas fa-edit me-1"></i> Editar
                                </a>
                                <a href="movies.php?delete=<?php echo $movie['id']; ?>" class="btn btn-danger delete-movie">
                                    <i class="fas fa-trash-alt me-1"></i> Excluir
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($movie['synopsis'])): ?>
                <div class="mt-4">
                    <h5>Sinopse</h5>
                    <p><?php echo nl2br(htmlspecialchars($movie['synopsis'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Avaliações</h5>
            </div>
            <div class="card-body">
                <?php if (isLoggedIn()): ?>
                    <!-- User Review Form -->
                    <div class="mb-4">
                        <h6><?php echo $userReview ? 'Sua Avaliação' : 'Adicionar Avaliação'; ?></h6>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $movieId); ?>">
                            <div class="mb-3">
                                <label for="review_rating" class="form-label">Classificação</label>
                                <div class="rating-input">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="rating-star <?php echo ($userReview && $userReview['rating'] / 2 >= $i) ? 'fas' : 'far'; ?> fa-star text-warning" data-value="<?php echo $i; ?>"></i>
                                    <?php endfor; ?>
                                    <input type="hidden" name="review_rating" id="review_rating" value="<?php echo $userReview ? $userReview['rating'] : '0'; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="review_comment" class="form-label">Comentário</label>
                                <textarea class="form-control" id="review_comment" name="review_comment" rows="3"><?php echo $userReview ? htmlspecialchars($userReview['comment']) : ''; ?></textarea>
                            </div>
                            <button type="submit" name="submit_review" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> <?php echo $userReview ? 'Atualizar Avaliação' : 'Enviar Avaliação'; ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <!-- Other Reviews -->
                <?php if (count($reviews) > 0): ?>
                    <hr>
                    <h6 class="mb-3"><?php echo count($reviews); ?> Avaliação(ões) de Outros Usuários</h6>
                    
                    <?php foreach ($reviews as $review): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($review['username']); ?></h6>
                                    <small class="text-muted"><?php echo formatDate($review['created_at']); ?></small>
                                </div>
                                <div class="mb-2">
                                    <?php 
                                    // Display rating as stars (out of 5)
                                    $reviewRating = round($review['rating'] / 2);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $reviewRating) {
                                            echo '<i class="fas fa-star text-warning"></i>';
                                        } else {
                                            echo '<i class="far fa-star text-warning"></i>';
                                        }
                                    }
                                    ?> 
                                    <span class="ms-1"><?php echo number_format($review['rating'], 1); ?>/10</span>
                                </div>
                                <?php if (!empty($review['comment'])): ?>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php elseif (!$userReview): ?>
                    <div class="text-center text-muted my-4">
                        <p>Nenhuma avaliação ainda. Seja o primeiro a avaliar!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Similar Movies -->
        <?php if (count($similarMovies) > 0): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-film me-2"></i>Filmes Semelhantes</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($similarMovies as $similar): ?>
                        <a href="movie_view.php?id=<?php echo $similar['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex">
                                <div class="flex-shrink-0" style="width: 60px; height: 60px; overflow: hidden;">
                                    <?php if (!empty($similar['poster'])): ?>
                                        <img src="<?php echo htmlspecialchars($similar['poster']); ?>" class="img-fluid rounded" alt="">
                                    <?php else: ?>
                                        <div class="bg-dark d-flex align-items-center justify-content-center rounded" style="width: 60px; height: 60px;">
                                            <i class="fas fa-film text-light"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($similar['title']); ?></h6>
                                    <div>
                                        <?php 
                                        // Display rating as stars (out of 5)
                                        $similarRating = round($similar['rating'] / 2);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $similarRating) {
                                                echo '<i class="fas fa-star text-warning fa-xs"></i>';
                                            } else {
                                                echo '<i class="far fa-star text-warning fa-xs"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo !empty($similar['release_year']) ? htmlspecialchars($similar['release_year']) : 'Ano desconhecido'; ?>
                                    </small>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Movie Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Ações</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="movies.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-arrow-left me-2"></i>Voltar para Lista de Filmes
                </a>
                <?php if (isLoggedIn()): ?>
                    <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#shareModal">
                        <i class="fas fa-share-alt me-2"></i>Compartilhar
                    </a>
                <?php endif; ?>
                <?php if (isLoggedIn() && ($movie['user_id'] == $_SESSION['user_id'] || isAdmin($pdo))): ?>
                    <a href="movie_edit.php?id=<?php echo $movie['id']; ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-edit me-2"></i>Editar Filme
                    </a>
                    <a href="movies.php?delete=<?php echo $movie['id']; ?>" class="list-group-item list-group-item-action text-danger delete-movie">
                        <i class="fas fa-trash-alt me-2"></i>Excluir Filme
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Movie Stats -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Estatísticas</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Data de adição
                        <span class="badge bg-primary rounded-pill"><?php echo formatDate($movie['created_at'], 'd/m/Y'); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Classificação
                        <span class="badge bg-warning text-dark rounded-pill"><?php echo number_format($movie['rating'], 1); ?>/10</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Total de avaliações
                        <span class="badge bg-info rounded-pill"><?php echo count($reviews) + ($userReview ? 1 : 0); ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareModalLabel">Compartilhar "<?php echo htmlspecialchars($movie['title']); ?>"</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Compartilhe este filme com seus amigos:</p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="shareUrl" value="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>" readonly>
                    <button class="btn btn-outline-primary" type="button" onclick="copyShareUrl()">Copiar</button>
                </div>
                <div class="d-flex justify-content-center gap-3 mt-3">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" target="_blank" class="btn btn-outline-primary">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>&text=<?php echo urlencode('Confira este filme: ' . $movie['title']); ?>" target="_blank" class="btn btn-outline-info">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://wa.me/?text=<?php echo urlencode('Confira este filme: ' . $movie['title'] . ' ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" target="_blank" class="btn btn-outline-success">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
function copyShareUrl() {
    var copyText = document.getElementById("shareUrl");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    document.execCommand("copy");
    
    // Show copied feedback
    var button = copyText.nextElementSibling;
    var originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check me-1"></i>Copiado!';
    setTimeout(function() {
        button.innerHTML = originalText;
    }, 2000);
}
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>
