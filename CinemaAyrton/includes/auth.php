<?php
/**
 * Authentication functions for user login, registration and session management
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Register a new user
 *
 * @param PDO $pdo Database connection
 * @param string $username Username
 * @param string $email Email
 * @param string $password Password
 * @param string $firstName First name
 * @param string $lastName Last name
 * @return array Result with status and message
 */
function registerUser($pdo, $username, $email, $password, $firstName = '', $lastName = '') {
    // Sanitize input
    $username = sanitize($username);
    $email = sanitize($email);
    $firstName = sanitize($firstName);
    $lastName = sanitize($lastName);
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        return ['status' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos.'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => false, 'message' => 'Por favor, forneça um endereço de email válido.'];
    }
    
    if (strlen($password) < 6) {
        return ['status' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres.'];
    }
    
    try {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            return ['status' => false, 'message' => 'Nome de usuário já está em uso.'];
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            return ['status' => false, 'message' => 'Email já está em uso.'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name) 
                              VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$username, $email, $hashedPassword, $firstName, $lastName]);
        
        if ($result) {
            return ['status' => true, 'message' => 'Registro realizado com sucesso! Você já pode fazer login.'];
        } else {
            return ['status' => false, 'message' => 'Erro ao registrar. Por favor, tente novamente.'];
        }
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['status' => false, 'message' => 'Ocorreu um erro no sistema. Por favor, tente novamente mais tarde.'];
    }
}

/**
 * Login a user
 *
 * @param PDO $pdo Database connection
 * @param string $username Username or email
 * @param string $password Password
 * @param bool $remember Remember login
 * @return array Result with status and message
 */
function loginUser($pdo, $username, $password, $remember = false) {
    // Sanitize input
    $username = sanitize($username);
    
    // Validate input
    if (empty($username) || empty($password)) {
        return ['status' => false, 'message' => 'Por favor, preencha todos os campos.'];
    }
    
    try {
        // Check if username/email exists and user is active
        $stmt = $pdo->prepare("SELECT id, username, email, password FROM users 
                              WHERE (username = ? OR email = ?) AND is_active = TRUE");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['status' => false, 'message' => 'Usuário não encontrado ou conta desativada.'];
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // If remember me, set cookie (30 days)
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (86400 * 30), "/", "", false, true);
                
                // Store token in database (in a real app, you'd have a tokens table)
                // This is simplified for this example
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                $stmt->execute([$token, $user['id']]);
            }
            
            return ['status' => true, 'message' => 'Login realizado com sucesso!'];
        } else {
            return ['status' => false, 'message' => 'Senha incorreta. Por favor, tente novamente.'];
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return ['status' => false, 'message' => 'Ocorreu um erro no sistema. Por favor, tente novamente mais tarde.'];
    }
}

/**
 * Logout the current user
 *
 * @return void
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Clear remember token cookie if it exists
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, "/", "", false, true);
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Check if user has admin role
 *
 * @param PDO $pdo Database connection
 * @return bool True if user is admin, false otherwise
 */
function isAdmin($pdo) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    return ($user && $user['role'] === 'admin');
}

/**
 * Update user profile
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param array $data Profile data to update
 * @return array Result with status and message
 */
function updateUserProfile($pdo, $userId, $data) {
    // Make sure only the current user or an admin can update profile
    if ($_SESSION['user_id'] != $userId && !isAdmin($pdo)) {
        return ['status' => false, 'message' => 'Você não tem permissão para atualizar este perfil.'];
    }
    
    // Fields that can be updated
    $allowedFields = ['email', 'first_name', 'last_name', 'bio'];
    $updateFields = [];
    $params = [];
    
    // Prepare update statement
    foreach ($data as $field => $value) {
        if (in_array($field, $allowedFields)) {
            $updateFields[] = "$field = ?";
            $params[] = sanitize($value);
        }
    }
    
    // Check if there's anything to update
    if (empty($updateFields)) {
        return ['status' => false, 'message' => 'Nenhum campo válido para atualização.'];
    }
    
    // Add user ID to params
    $params[] = $userId;
    
    try {
        // Update user profile
        $stmt = $pdo->prepare("UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?");
        $result = $stmt->execute($params);
        
        if ($result) {
            return ['status' => true, 'message' => 'Perfil atualizado com sucesso!'];
        } else {
            return ['status' => false, 'message' => 'Erro ao atualizar perfil. Por favor, tente novamente.'];
        }
    } catch (PDOException $e) {
        error_log("Profile update error: " . $e->getMessage());
        return ['status' => false, 'message' => 'Ocorreu um erro no sistema. Por favor, tente novamente mais tarde.'];
    }
}

/**
 * Change user password
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param string $currentPassword Current password
 * @param string $newPassword New password
 * @return array Result with status and message
 */
function changeUserPassword($pdo, $userId, $currentPassword, $newPassword) {
    // Make sure only the current user can change their password
    if ($_SESSION['user_id'] != $userId) {
        return ['status' => false, 'message' => 'Você não tem permissão para mudar esta senha.'];
    }
    
    // Validate input
    if (empty($currentPassword) || empty($newPassword)) {
        return ['status' => false, 'message' => 'Por favor, preencha todos os campos.'];
    }
    
    if (strlen($newPassword) < 6) {
        return ['status' => false, 'message' => 'A nova senha deve ter pelo menos 6 caracteres.'];
    }
    
    try {
        // Get current password hash
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['status' => false, 'message' => 'Usuário não encontrado.'];
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return ['status' => false, 'message' => 'Senha atual incorreta.'];
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $result = $stmt->execute([$hashedPassword, $userId]);
        
        if ($result) {
            return ['status' => true, 'message' => 'Senha alterada com sucesso!'];
        } else {
            return ['status' => false, 'message' => 'Erro ao alterar senha. Por favor, tente novamente.'];
        }
    } catch (PDOException $e) {
        error_log("Password change error: " . $e->getMessage());
        return ['status' => false, 'message' => 'Ocorreu um erro no sistema. Por favor, tente novamente mais tarde.'];
    }
}

/**
 * Require user to be logged in, redirect if not
 *
 * @param string $redirectUrl URL to redirect to if not logged in
 * @return void
 */
function requireLogin($redirectUrl = 'login.php') {
    if (!isLoggedIn()) {
        setFlashMessage('Você precisa estar logado para acessar esta página.', 'warning');
        redirect($redirectUrl);
    }
}

/**
 * Require user to be admin, redirect if not
 *
 * @param PDO $pdo Database connection
 * @param string $redirectUrl URL to redirect to if not admin
 * @return void
 */
function requireAdmin($pdo, $redirectUrl = 'index.php') {
    if (!isAdmin($pdo)) {
        setFlashMessage('Você não tem permissão para acessar esta página.', 'danger');
        redirect($redirectUrl);
    }
}
