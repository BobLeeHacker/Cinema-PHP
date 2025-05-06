<?php
/**
 * API endpoint for user operations (AJAX)
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
    'message' => 'Invalid request'
];

// Check if it's an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    // Handle GET requests (get user data)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isLoggedIn()) {
        // Get user ID (current user or specific user for admins)
        $userId = isset($_GET['id']) && isAdmin($pdo) ? (int)$_GET['id'] : $_SESSION['user_id'];
        
        try {
            // Get user data
            $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, bio, profile_image, role, created_at 
                                  FROM users 
                                  WHERE id = ? AND is_active = TRUE");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Remove sensitive data
                unset($user['password']);
                
                // Get user stats
                $statsStmt = $pdo->prepare("SELECT 
                                          (SELECT COUNT(*) FROM movies WHERE user_id = ? AND is_active = TRUE) as movie_count,
                                          (SELECT COUNT(*) FROM reviews WHERE user_id = ? AND is_active = TRUE) as review_count");
                $statsStmt->execute([$userId, $userId]);
                $stats = $statsStmt->fetch();
                
                $user['stats'] = $stats;
                
                $response = [
                    'status' => 'success',
                    'user' => $user
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Usuário não encontrado'
                ];
            }
        } catch (PDOException $e) {
            error_log("API error: " . $e->getMessage());
            $response = [
                'status' => 'error',
                'message' => 'Erro ao buscar dados do usuário'
            ];
        }
    }
    
    // Handle POST requests (update user profile)
    else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($data) {
            // Get user ID (current user or specific user for admins)
            $userId = isset($data['id']) && isAdmin($pdo) ? (int)$data['id'] : $_SESSION['user_id'];
            
            // Make sure user exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND is_active = TRUE");
            $checkStmt->execute([$userId]);
            if (!$checkStmt->fetch()) {
                $response = [
                    'status' => 'error',
                    'message' => 'Usuário não encontrado'
                ];
            } else {
                // Update user profile
                $email = $data['email'] ?? '';
                $firstName = $data['first_name'] ?? '';
                $lastName = $data['last_name'] ?? '';
                $bio = $data['bio'] ?? '';
                
                // Basic validation
                $errors = [];
                
                if (empty($email)) {
                    $errors[] = 'Email é obrigatório.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Por favor, forneça um email válido.';
                }
                
                // If no errors, update profile
                if (empty($errors)) {
                    $profileData = [
                        'email' => $email,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'bio' => $bio
                    ];
                    
                    $result = updateUserProfile($pdo, $userId, $profileData);
                    
                    if ($result['status']) {
                        // Get updated user data
                        $updatedStmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, bio, profile_image, role, created_at 
                                                    FROM users 
                                                    WHERE id = ?");
                        $updatedStmt->execute([$userId]);
                        $updatedUser = $updatedStmt->fetch();
                        
                        // Remove sensitive data
                        unset($updatedUser['password']);
                        
                        $response = [
                            'status' => 'success',
                            'message' => $result['message'],
                            'user' => $updatedUser
                        ];
                    } else {
                        $response = [
                            'status' => 'error',
                            'message' => $result['message']
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
    }
    
    // Handle PUT requests (change user password)
    else if ($_SERVER['REQUEST_METHOD'] === 'PUT' && isLoggedIn()) {
        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($data && isset($data['current_password']) && isset($data['new_password'])) {
            $userId = $_SESSION['user_id']; // Only allow changing own password
            $currentPassword = $data['current_password'];
            $newPassword = $data['new_password'];
            
            $result = changeUserPassword($pdo, $userId, $currentPassword, $newPassword);
            
            if ($result['status']) {
                $response = [
                    'status' => 'success',
                    'message' => $result['message']
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => $result['message']
                ];
            }
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Dados incompletos para alteração de senha'
            ];
        }
    }
    
    // Handle GET requests for user's movies
    else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['movies']) && isLoggedIn()) {
        // Get user ID (current user or specific user for admins)
        $userId = isset($_GET['id']) && isAdmin($pdo) ? (int)$_GET['id'] : $_SESSION['user_id'];
        
        try {
            // Get user's movies
            $stmt = $pdo->prepare("SELECT id, title, director, release_year, genre, duration, rating, poster, synopsis, created_at 
                                  FROM movies 
                                  WHERE user_id = ? AND is_active = TRUE 
                                  ORDER BY title ASC");
            $stmt->execute([$userId]);
            $movies = $stmt->fetchAll();
            
            $response = [
                'status' => 'success',
                'count' => count($movies),
                'movies' => $movies
            ];
        } catch (PDOException $e) {
            error_log("API error: " . $e->getMessage());
            $response = [
                'status' => 'error',
                'message' => 'Erro ao buscar filmes do usuário'
            ];
        }
    }
    
    // Handle GET requests for user's reviews
    else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['reviews']) && isLoggedIn()) {
        // Get user ID (current user or specific user for admins)
        $userId = isset($_GET['id']) && isAdmin($pdo) ? (int)$_GET['id'] : $_SESSION['user_id'];
        
        try {
            // Get user's reviews with movie info
            $stmt = $pdo->prepare("SELECT r.id, r.rating, r.comment, r.created_at, 
                                  m.id as movie_id, m.title as movie_title, m.poster as movie_poster 
                                  FROM reviews r
                                  JOIN movies m ON r.movie_id = m.id
                                  WHERE r.user_id = ? AND r.is_active = TRUE AND m.is_active = TRUE
                                  ORDER BY r.created_at DESC");
            $stmt->execute([$userId]);
            $reviews = $stmt->fetchAll();
            
            $response = [
                'status' => 'success',
                'count' => count($reviews),
                'reviews' => $reviews
            ];
        } catch (PDOException $e) {
            error_log("API error: " . $e->getMessage());
            $response = [
                'status' => 'error',
                'message' => 'Erro ao buscar avaliações do usuário'
            ];
        }
    }
    
    // Handle DELETE requests (admin only - deactivate user)
    else if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isLoggedIn() && isAdmin($pdo)) {
        // Get user ID from URL
        $parts = explode('/', $_SERVER['REQUEST_URI']);
        $userId = (int)end($parts);
        
        if ($userId && $userId != $_SESSION['user_id']) { // Prevent deactivating self
            try {
                // Soft delete the user
                $stmt = $pdo->prepare("UPDATE users SET is_active = FALSE WHERE id = ?");
                $result = $stmt->execute([$userId]);
                
                if ($result) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Usuário desativado com sucesso!'
                    ];
                } else {
                    $response = [
                        'status' => 'error',
                        'message' => 'Erro ao desativar usuário.'
                    ];
                }
            } catch (PDOException $e) {
                error_log("API error: " . $e->getMessage());
                $response = [
                    'status' => 'error',
                    'message' => 'Erro ao desativar usuário.'
                ];
            }
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Usuário inválido ou tentativa de desativar a si mesmo.'
            ];
        }
    }
}

// Return JSON response
echo json_encode($response);
