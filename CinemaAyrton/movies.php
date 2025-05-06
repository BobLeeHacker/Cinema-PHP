<?php
// Set page title
$pageTitle = 'Filmes';

// Include necessary files
require_once 'includes/header.php';
require_once 'includes/auth.php';

// Initialize variables
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$genre = isset($_GET['genre']) ? sanitize($_GET['genre']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 12;
$offset = ($page - 1) * $itemsPerPage;

// Handle delete request if user is logged in
if (isLoggedIn() && isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $movieId = (int)$_GET['delete'];
    
    // Get movie info to check ownership
    $stmt = $pdo->prepare("SELECT user_id FROM movies WHERE id = ?");
    $stmt->execute([$movieId]);
    $movie = $stmt->fetch();
    
    // Check if movie exists and user is the owner or admin
    if ($movie && ($movie['user_id'] == $_SESSION['user_id'] || isAdmin($pdo))) {
        // Soft delete the movie (set is_active to false)
        $stmt = $pdo->prepare("UPDATE movies SET is_active = FALSE WHERE id = ?");
        $result = $stmt->execute([$movieId]);
        
        if ($result) {
            setFlashMessage('Filme excluído com sucesso!', 'success');
        } else {
            setFlashMessage('Erro ao excluir filme.', 'danger');
        }
    } else {
        setFlashMessage('Você não tem permissão para excluir este filme.', 'danger');
    }
    
    // Redirect to remove the query parameter
    redirect('movies.php' . ($search ? "?search=$search" : ''));
}

// Build query based on filters
$params = [];
$query = "SELECT m.*, u.username 
          FROM movies m 
          LEFT JOIN users u ON m.user_id = u.id 
          WHERE m.is_active = TRUE";

if (!empty($search)) {
    $query .= " AND (m.title LIKE ? OR m.director LIKE ? OR m.genre LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($genre)) {
    $query .= " AND m.genre = ?";
    $params[] = $genre;
}

// Get total count for pagination
$countStmt = $pdo->prepare(str_replace("m.*, u.username", "COUNT(*)", $query));
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// Ensure page is within valid range
if ($page < 1) $page = 1;
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

// Add order and limit to query
$query .= " ORDER BY m.title ASC LIMIT ? OFFSET ?";
$params[] = $itemsPerPage;
$params[] = $offset;

// Execute final query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$movies = $stmt->fetchAll();

// Get unique genres for filter
$genreStmt = $pdo->prepare("SELECT DISTINCT genre FROM movies WHERE is_active = TRUE AND genre IS NOT NULL AND genre != '' ORDER BY genre");
$genreStmt->execute();
$genres = $genreStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Início</a></li>
        <li class="breadcrumb-item active" aria-current="page">Filmes</li>
    </ol>
</nav>

<!-- Page Header -->
<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h1 class="h3 mb-0">
            <?php if (!empty($search)): ?>
                Resultados da busca: "<?php echo htmlspecialchars($search); ?>"
            <?php else: ?>
                Todos os Filmes
            <?php endif; ?>
        </h1>
        <p class="text-muted">
            <?php echo $totalItems; ?> filme(s) encontrado(s)
            <?php if (!empty($genre)): ?>
                na categoria "<?php echo htmlspecialchars($genre); ?>"
            <?php endif; ?>
        </p>
    </div>
    <div class="col-md-4 text-md-end">
        <?php if (isLoggedIn()): ?>
            <a href="movie_add.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Adicionar Filme
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
            <div class="col-md-6">
                <label for="search" class="form-label">Pesquisar</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Título, diretor ou gênero..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <label for="genre" class="form-label">Gênero</label>
                <select class="form-select" id="genre" name="genre">
                    <option value="">Todos os Gêneros</option>
                    <?php foreach ($genres as $genreOption): ?>
                        <option value="<?php echo htmlspecialchars($genreOption); ?>" <?php echo ($genre === $genreOption) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genreOption); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Movies List -->
<div class="row">
    <?php if (count($movies) > 0): ?>
        <?php foreach ($movies as $movie): ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card h-100">
                    <?php if (!empty($movie['poster'])): ?>
                        <img src="<?php echo htmlspecialchars($movie['poster']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                    <?php else: ?>
                        <div class="card-img-top bg-dark d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="fas fa-film fa-4x text-light opacity-50"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php 
                            // Highlight search term in title
                            if (!empty($search) && stripos($movie['title'], $search) !== false) {
                                $title = preg_replace('/(' . preg_quote($search, '/') . ')/i', '<span class="search-highlight">$1</span>', htmlspecialchars($movie['title']));
                                echo $title;
                            } else {
                                echo htmlspecialchars($movie['title']);
                            }
                            ?>
                        </h5>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fas fa-user-alt me-1"></i> <?php echo htmlspecialchars($movie['director'] ?: 'Desconhecido'); ?><br>
                                <i class="fas fa-calendar-alt me-1"></i> <?php echo htmlspecialchars($movie['release_year'] ?: 'N/A'); ?>
                                <?php if (!empty($movie['genre'])): ?>
                                    <br><i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($movie['genre']); ?>
                                <?php endif; ?>
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
                        <p class="card-text text-muted">
                            <small>Adicionado por: <?php echo htmlspecialchars($movie['username'] ?? 'Usuário desconhecido'); ?></small>
                        </p>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="movie_view.php?id=<?php echo $movie['id']; ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-eye me-1"></i> Ver Detalhes
                        </a>
                        <?php if (isLoggedIn() && ($movie['user_id'] == $_SESSION['user_id'] || isAdmin($pdo))): ?>
                            <div>
                                <a href="movie_edit.php?id=<?php echo $movie['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit me-1"></i> Editar
                                </a>
                                <a href="movies.php?delete=<?php echo $movie['id']; ?>" class="btn btn-sm btn-danger delete-movie">
                                    <i class="fas fa-trash-alt me-1"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info">
                <?php if (!empty($search)): ?>
                    Nenhum filme encontrado para "<?php echo htmlspecialchars($search); ?>".
                <?php else: ?>
                    Nenhum filme cadastrado ainda.
                <?php endif; ?>
                <?php if (isLoggedIn()): ?>
                    <a href="movie_add.php" class="alert-link">Adicionar um filme</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <?php
    // Create the URL pattern for pagination
    $queryParams = [];
    if (!empty($search)) $queryParams[] = "search=" . urlencode($search);
    if (!empty($genre)) $queryParams[] = "genre=" . urlencode($genre);
    $queryParams[] = "page=%d";
    $urlPattern = '?' . implode('&', $queryParams);
    
    echo generatePagination($page, $totalPages, $urlPattern);
    ?>
<?php endif; ?>

<?php
// Include footer
require_once 'includes/footer.php';
?>
