<?php
// Set page title
$pageTitle = 'Adicionar Filme';

// Include necessary files
require_once 'includes/header.php';
require_once 'includes/auth.php';

// Require login
requireLogin();

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
    
    // If no errors, insert movie
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO movies (title, director, release_year, genre, duration, rating, poster, synopsis, user_id) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $result = $stmt->execute([
                $title,
                $director,
                $releaseYear ?: null,
                $genre,
                $duration ?: null,
                $rating ?: null,
                $poster,
                $synopsis,
                $_SESSION['user_id']
            ]);
            
            if ($result) {
                $movieId = $pdo->lastInsertId();
                setFlashMessage('Filme adicionado com sucesso!', 'success');
                redirect('movie_view.php?id=' . $movieId);
            } else {
                $errors[] = 'Erro ao adicionar filme. Por favor, tente novamente.';
            }
        } catch (PDOException $e) {
            error_log("Movie insert error: " . $e->getMessage());
            $errors[] = 'Ocorreu um erro no sistema. Por favor, tente novamente mais tarde.';
        }
    }
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
        <li class="breadcrumb-item active" aria-current="page">Adicionar Filme</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Adicionar Novo Filme</h4>
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
                
                <form id="movie-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="release_year" class="form-label">Ano de Lançamento</label>
                                <input type="number" class="form-control" id="release_year" name="release_year" min="1888" max="<?php echo date('Y') + 5; ?>" value="<?php echo isset($releaseYear) ? htmlspecialchars($releaseYear) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="director" class="form-label">Diretor</label>
                                <input type="text" class="form-control" id="director" name="director" value="<?php echo isset($director) ? htmlspecialchars($director) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="duration" class="form-label">Duração (minutos)</label>
                                <input type="number" class="form-control" id="duration" name="duration" min="1" value="<?php echo isset($duration) ? htmlspecialchars($duration) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="genre" class="form-label">Gênero</label>
                                <input type="text" class="form-control" id="genre" name="genre" list="genre-list" value="<?php echo isset($genre) ? htmlspecialchars($genre) : ''; ?>">
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
                                            <i class="rating-star far fa-star text-warning" data-value="<?php echo $i; ?>"></i>
                                        <?php endfor; ?>
                                        <input type="hidden" name="rating" id="rating" value="<?php echo isset($rating) ? htmlspecialchars($rating) : '0'; ?>">
                                    </div>
                                </div>
                                <div class="form-text">Clique nas estrelas para classificar (0-10)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="poster" class="form-label">URL do Poster</label>
                        <input type="url" class="form-control" id="poster" name="poster" placeholder="https://exemplo.com/imagem.jpg" value="<?php echo isset($poster) ? htmlspecialchars($poster) : ''; ?>">
                        <div class="form-text">Insira um URL válido para uma imagem online. Deixe em branco para usar o ícone padrão.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="synopsis" class="form-label">Sinopse</label>
                        <textarea class="form-control" id="synopsis" name="synopsis" rows="4"><?php echo isset($synopsis) ? htmlspecialchars($synopsis) : ''; ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="movies.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Salvar Filme
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
