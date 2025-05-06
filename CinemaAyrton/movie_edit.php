<?php
// Set page title
$pageTitle = 'Editar Filme';

// Include necessary files
require_once 'includes/header.php';
require_once 'includes/auth.php';

// Require login
requireLogin();

// Check if movie ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('ID do filme inválido.', 'danger');
    redirect('movies.php');
}

$movieId = (int)$_GET['id'];

// Get movie details
$stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ? AND is_active = TRUE");
$stmt->execute([$movieId]);
$movie = $stmt->fetch();

// Check if movie exists
if (!$movie) {
    setFlashMessage('Filme não encontrado.', 'danger');
    redirect('movies.php');
}

// Check if user has permission to edit (owner or admin)
if ($movie['user_id'] != $_SESSION['user_id'] && !isAdmin($pdo)) {
    setFlashMessage('Você não tem permissão para editar este filme.', 'danger');
    redirect('movies.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = $_POST['title'] ?? '';
    $director = $_POST['director'] ?? '';
    $releaseYear = $_POST['release_year'] ?? null;
    $genre = $_POST['genre'] ?? '';
    $duration = $_POST['duration'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $poster = $_POST['poster'] ?? '';
    $synopsis = $_POST['synopsis'] ?? '';
    
    // Basic validation
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'O título do filme é obrigatório.';
    }
    
    if (!empty($releaseYear) && (!is_numeric($releaseYear) || $releaseYear < 1888 || $releaseYear > (date('Y') + 5))) {
        $errors[] = 'Ano de lançamento inválido.';
    }
    
    if (!empty($duration) && (!is_numeric($duration) || $duration <= 0)) {
        $errors[] = 'Duração deve ser um número positivo.';
    }
    
    if (!empty($rating) && (!is_numeric($rating) || $rating < 0 || $rating > 10)) {
        $errors[] = 'Classificação deve ser um número entre 0 e 10.';
    }
    
    // If no errors, update movie
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE movies 
                                  SET title = ?, director = ?, release_year = ?, genre = ?, 
                                      duration = ?, rating = ?, poster = ?, synopsis = ? 
                                  WHERE id = ?");
            
            $result = $stmt->execute([
                $title,
                $director,
                $releaseYear ?: null,
                $genre,
                $duration ?: null,
                $rating ?: null,
                $poster,
                $synopsis,
                $movieId
            ]);
            
            if ($result) {
                setFlashMessage('Filme atualizado com sucesso!', 'success');
                redirect('movie_view.php?id=' . $movieId);
            } else {
                $errors[] = 'Erro ao atualizar filme. Por favor, tente novamente.';
            }
        } catch (PDOException $e) {
            error_log("Movie update error: " . $e->getMessage());
            $errors[] = 'Ocorreu um erro no sistema. Por favor, tente novamente mais tarde.';
        }
    }
} else {
    // Pre-fill form with movie data
    $title = $movie['title'];
    $director = $movie['director'];
    $releaseYear = $movie['release_year'];
    $genre = $movie['genre'];
    $duration = $movie['duration'];
    $rating = $movie['rating'];
    $poster = $movie['poster'];
    $synopsis = $movie['synopsis'];
}

// Get list of genres for suggestions
$genreStmt = $pdo->prepare("SELECT DISTINCT genre FROM movies WHERE genre IS NOT NULL AND genre != '' ORDER BY genre");
$genreStmt->execute();
$genres = $genreStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Início</a></li>
        <li class="breadcrumb-item"><a href="movies.php">Filmes</a></li>
        <li class="breadcrumb-item"><a href="movie_view.php?id=<?php echo $movieId; ?>">
            <?php echo htmlspecialchars($movie['title']); ?>
        </a></li>
        <li class="breadcrumb-item active" aria-current="page">Editar</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Editar Filme</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form id="movie-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $movieId); ?>">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="release_year" class="form-label">Ano de Lançamento</label>
                                <input type="number" class="form-control" id="release_year" name="release_year" min="1888" max="<?php echo date('Y') + 5; ?>" value="<?php echo htmlspecialchars($releaseYear ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="director" class="form-label">Diretor</label>
                                <input type="text" class="form-control" id="director" name="director" value="<?php echo htmlspecialchars($director ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="duration" class="form-label">Duração (minutos)</label>
                                <input type="number" class="form-control" id="duration" name="duration" min="1" value="<?php echo htmlspecialchars($duration ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="genre" class="form-label">Gênero</label>
                                <input type="text" class="form-control" id="genre" name="genre" list="genre-list" value="<?php echo htmlspecialchars($genre ?? ''); ?>">
                                <datalist id="genre-list">
                                    <?php foreach ($genres as $genreOption): ?>
                                        <option value="<?php echo htmlspecialchars($genreOption); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="rating" class="form-label">Classificação (0-10)</label>
                                <div class="input-group">
                                    <div class="rating-input">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="rating-star <?php echo ($rating / 2 >= $i) ? 'fas' : 'far'; ?> fa-star text-warning" data-value="<?php echo $i; ?>"></i>
                                        <?php endfor; ?>
                                        <input type="hidden" name="rating" id="rating" value="<?php echo htmlspecialchars($rating ?? '0'); ?>">
                                    </div>
                                </div>
                                <div class="form-text">Clique nas estrelas para classificar (0-10)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="poster" class="form-label">URL do Poster</label>
                        <input type="url" class="form-control" id="poster" name="poster" placeholder="https://exemplo.com/imagem.jpg" value="<?php echo htmlspecialchars($poster ?? ''); ?>">
                        <div class="form-text">Insira um URL válido para uma imagem online. Deixe em branco para usar o ícone padrão.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="synopsis" class="form-label">Sinopse</label>
                        <textarea class="form-control" id="synopsis" name="synopsis" rows="4"><?php echo htmlspecialchars($synopsis ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="movie_view.php?id=<?php echo $movieId; ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Atualizar Filme
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
