<?php
// Set page title
$pageTitle = 'Login';

// Include necessary files
require_once 'includes/header.php';
require_once 'includes/auth.php';

// Check if user is already logged in
if (isLoggedIn()) {
    setFlashMessage('Você já está logado.', 'info');
    redirect('dashboard.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Basic validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Nome de usuário ou email é obrigatório.';
    }
    
    if (empty($password)) {
        $errors[] = 'Senha é obrigatória.';
    }
    
    // If no errors, try to login user
    if (empty($errors)) {
        $result = loginUser($pdo, $username, $password, $remember);
        
        if ($result['status']) {
            setFlashMessage($result['message'], 'success');
            redirect('dashboard.php');
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>Entrar na sua Conta</h4>
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
                
                <form id="login-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nome de Usuário ou Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Lembrar de mim</label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-info text-white">
                            <i class="fas fa-sign-in-alt me-2"></i>Entrar
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Não tem uma conta? <a href="register.php">Registrar</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Demo credentials alert -->
<div class="row justify-content-center mt-4">
    <div class="col-md-6">
        <div class="alert alert-info">
            <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Credenciais de Demonstração</h5>
            <p class="mb-0"><strong>Admin:</strong> admin / admin123</p>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>
