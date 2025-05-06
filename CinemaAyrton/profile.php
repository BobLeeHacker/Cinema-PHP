<?php
// Set page title
$pageTitle = 'Meu Perfil';

// Include necessary files
require_once 'includes/header.php';
require_once 'includes/auth.php';

// Require login
requireLogin();

// Get current user data
$user = getCurrentUser($pdo);

// Process profile update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = $_POST['email'] ?? '';
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
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
        
        $result = updateUserProfile($pdo, $user['id'], $profileData);
        
        if ($result['status']) {
            setFlashMessage($result['message'], 'success');
            // Refresh user data
            $user = getCurrentUser($pdo);
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Process password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    $passwordErrors = [];
    
    if (empty($currentPassword)) {
        $passwordErrors[] = 'Senha atual é obrigatória.';
    }
    
    if (empty($newPassword)) {
        $passwordErrors[] = 'Nova senha é obrigatória.';
    } elseif (strlen($newPassword) < 6) {
        $passwordErrors[] = 'Nova senha deve ter pelo menos 6 caracteres.';
    }
    
    if ($newPassword !== $confirmPassword) {
        $passwordErrors[] = 'As senhas não coincidem.';
    }
    
    // If no errors, change password
    if (empty($passwordErrors)) {
        $result = changeUserPassword($pdo, $user['id'], $currentPassword, $newPassword);
        
        if ($result['status']) {
            setFlashMessage($result['message'], 'success');
        } else {
            $passwordErrors[] = $result['message'];
        }
    }
}

// Get user stats
// Count user's movies
$stmt = $pdo->prepare("SELECT COUNT(*) FROM movies WHERE user_id = ? AND is_active = TRUE");
$stmt->execute([$user['id']]);
$movieCount = $stmt->fetchColumn();

// Get user's account age in days
$accountCreated = new DateTime($user['created_at']);
$now = new DateTime();
$accountAge = $now->diff($accountCreated)->days;
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Início</a></li>
        <li class="breadcrumb-item"><a href="dashboard.php">Painel</a></li>
        <li class="breadcrumb-item active" aria-current="page">Meu Perfil</li>
    </ol>
</nav>

<div class="row">
    <!-- Profile Sidebar -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mb-3">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['username']); ?>&background=random&size=150" class="profile-image" alt="<?php echo htmlspecialchars($user['username']); ?>">
                </div>
                <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                <p class="text-muted">
                    <?php if (!empty($user['first_name']) && !empty($user['last_name'])): ?>
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    <?php endif; ?>
                </p>
                <p><span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'info'; ?>"><?php echo ucfirst($user['role']); ?></span></p>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <h5><?php echo $movieCount; ?></h5>
                        <p class="text-muted">Filmes</p>
                    </div>
                    <div class="col-6">
                        <h5><?php echo $accountAge; ?></h5>
                        <p class="text-muted">Dias de Conta</p>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <small class="text-muted">Membro desde <?php echo formatDate($user['created_at'], 'd/m/Y'); ?></small>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Links Rápidos</h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="dashboard.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-tachometer-alt me-2"></i>Painel</span>
                    <i class="fas fa-chevron-right text-muted"></i>
                </a>
                <a href="movies.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-film me-2"></i>Meus Filmes</span>
                    <span class="badge bg-info rounded-pill"><?php echo $movieCount; ?></span>
                </a>
                <a href="movie_add.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-plus me-2"></i>Adicionar Filme</span>
                    <i class="fas fa-chevron-right text-muted"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Profile Content -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Editar Perfil</h5>
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
                
                <form id="profile-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nome de Usuário</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <div class="form-text">O nome de usuário não pode ser alterado.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Sobrenome</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bio" class="form-label">Biografia</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salvar Alterações
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Alterar Senha</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($passwordErrors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($passwordErrors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form id="password-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Senha Atual</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nova Senha</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">A senha deve ter pelo menos 6 caracteres.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="fas fa-key me-2"></i>Alterar Senha
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
