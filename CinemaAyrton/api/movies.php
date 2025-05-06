<?php
/**
 * API endpoint for movie operations (AJAX)
 */

// Set the content type to JSON
header('Content-Type: application/json');

// Include necessary files
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Initialize response
$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'movies' => []
];

// Check if it's an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    // Handle GET requests (search and list movies)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get parameters
        $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
        $sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'title_asc';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 9;
        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
        
        try {
            // Start building query
            $query = "SELECT id, title, director, release_year, genre, duration, rating, poster, synopsis, user_id 
                      FROM movies 
                      WHERE is_active = TRUE";
            
            $params = [];
            
            // Add search filter if provided
            if (!empty($search)) {
                $query .= " AND (title LIKE ? OR director LIKE ? OR genre LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            // Add user filter if logged in (only in dashboard context)
            if (isset($_GET['dashboard']) && $_GET['dashboard'] === 'true' && $userId) {
                $query .= " AND user_id = ?";
                $params[] = $userId;
            }
            
            // Add sorting
            switch ($sort) {
                case 'title_desc':
                    $query .= " ORDER BY title DESC";
                    break;
                case 'year_desc':
                    $query .= " ORDER BY release_year DESC, title ASC";
                    break;
                case 'year_asc':
                    $query .= " ORDER BY release_year ASC, title ASC";
                    break;
                case 'rating_desc':
                    $query .= " ORDER BY rating DESC, title ASC";
                    break;
                case 'rating_asc':
                    $query .= " ORDER BY rating ASC, title ASC";
                    break;
                default: // title_asc
                    $query .= " ORDER BY title ASC";
            }
            
            // Add limit
            $query .= " LIMIT ?";
            $params[] = $limit;
            
            // Execute query
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $movies = $stmt->fetchAll();
            
            // Prepare response
            $response = [
                'status' => 'success',
                'message' => count($movies) . ' filme(s) encontrado(s)',
                'movies' => $movies
            ];
            
        } catch (PDOException $e) {
            error_log("API error: " . $e->getMessage());
            $response = [
                'status' => 'error',
                'message' => 'Erro ao buscar filmes'
            ];
        }
    }
    
    // Handle POST requests (add movie)
    else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($data) {
            $title = $data['title'] ?? '';
            $director = $data['director'] ?? '';
            $releaseYear = $data['release_year'] ?? null;
            $genre = $data['genre'] ?? '';
            $duration = $data['duration'] ?? null;
            $rating = $data['rating'] ?? null;
            $poster = $data['poster'] ?? '';
            $synopsis = $data['synopsis'] ?? '';
            
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
                        // Get the newly created movie
                        $newMovieStmt = $pdo->prepare("SELECT id, title, director, release_year, genre, duration, rating, poster, synopsis, user_id FROM movies WHERE id = ?");
                        $newMovieStmt->execute([$movieId]);
                        $newMovie = $newMovieStmt->fetch();
                        
                        $response = [
                            'status' => 'success',
                            'message' => 'Filme adicionado com sucesso!',
                            'movie' => $newMovie
                        ];
                    } else {
                        $response = [
                            'status' => 'error',
                            'message' => 'Erro ao adicionar filme.'
                        ];
                    }
                } catch (PDOException $e) {
                    error_log("API error: " . $e->getMessage());
                    $response = [
                        'status' => 'error',
                        'message' => 'Erro ao adicionar filme.'
                    ];
                }
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Erro de validação',
                    'errors' => $errors
                ];
            }
        }
    }
    
    // Handle PUT requests (update movie)
    else if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isLoggedIn()) {
        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($data && isset($data['id'])) {
            $movieId = (int)$data['id'];
            
            // Check if movie exists and user has permission
            $checkStmt = $pdo->prepare("SELECT user_id FROM movies WHERE id = ? AND is_active = TRUE");
            $checkStmt->execute([$movieId]);
            $movie = $checkStmt->fetch();
            
            if ($movie && ($movie['user_id'] == $_SESSION['user_id'] || isAdmin($pdo))) {
                $title = $data['title'] ?? '';
                $director = $data['director'] ?? '';
                $releaseYear = $data['release_year'] ?? null;
                $genre = $data['genre'] ?? '';
                $duration = $data['duration'] ?? null;
                $rating = $data['rating'] ?? null;
                $poster = $data['poster'] ?? '';
                $synopsis = $data['synopsis'] ?? '';
                
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
                            // Get the updated movie
                            $updatedMovieStmt = $pdo->prepare("SELECT id, title, director, release_year, genre, duration, rating, poster, synopsis, user_id FROM movies WHERE id = ?");
                            $updatedMovieStmt->execute([$movieId]);
                            $updatedMovie = $updatedMovieStmt->fetch();
                            
                            $response = [
                                'status' => 'success',
                                'message' => 'Filme atualizado com sucesso!',
                                'movie' => $updatedMovie
                            ];
                        } else {
                            $response = [
                                'status' => 'error',
                                'message' => 'Erro ao atualizar filme.'
                            ];
                        }
                    } catch (PDOException $e) {
                        error_log("API error: " . $e->getMessage());
                        $response = [
                            'status' => 'error',
                            'message' => 'Erro ao atualizar filme.'
                        ];
                    }
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'Erro de validação',
                        'errors' => $errors
                    ];
                }
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Filme não encontrado ou permissão negada.'
                ];
            }
        }
    }
    
    // Handle DELETE requests (delete movie)
    else if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isLoggedIn()) {
        // Get movie ID from URL
        $parts = explode('/', $_SERVER['REQUEST_URI']);
        $movieId = (int)end($parts);
        
        if ($movieId) {
            // Check if movie exists and user has permission
            $checkStmt = $pdo->prepare("SELECT user_id FROM movies WHERE id = ? AND is_active = TRUE");
            $checkStmt->execute([$movieId]);
            $movie = $checkStmt->fetch();
            
            if ($movie && ($movie['user_id'] == $_SESSION['user_id'] || isAdmin($pdo))) {
                try {
                    // Soft delete the movie
                    $stmt = $pdo->prepare("UPDATE movies SET is_active = FALSE WHERE id = ?");
                    $result = $stmt->execute([$movieId]);
                    
                    if ($result) {
                        $response = [
                            'status' => 'success',
                            'message' => 'Filme excluído com sucesso!'
                        ];
                    } else {
                        $response = [
                            'status' => 'error',
                            'message' => 'Erro ao excluir filme.'
                        ];
                    }
                } catch (PDOException $e) {
                    error_log("API error: " . $e->getMessage());
                    $response = [
                        'status' => 'error',
                        'message' => 'Erro ao excluir filme.'
                    ];
                }
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Filme não encontrado ou permissão negada.'
                ];
            }
        }
    }
}

// Return JSON response
echo json_encode($response);
