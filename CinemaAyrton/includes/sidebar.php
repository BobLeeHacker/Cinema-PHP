<div class="list-group mb-4">
    <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt me-2"></i> Painel Principal
    </a>
    <a href="books.php" class="list-group-item list-group-item-action <?php echo (basename($_SERVER['PHP_SELF']) == 'books.php') ? 'active' : ''; ?>">
        <i class="fas fa-book me-2"></i> Meus Livros
    </a>
    <a href="book_add.php" class="list-group-item list-group-item-action <?php echo (basename($_SERVER['PHP_SELF']) == 'book_add.php') ? 'active' : ''; ?>">
        <i class="fas fa-plus-circle me-2"></i> Adicionar Livro
    </a>
    <a href="profile.php" class="list-group-item list-group-item-action <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
        <i class="fas fa-user me-2"></i> Perfil
    </a>
    <a href="logout.php" class="list-group-item list-group-item-action text-danger">
        <i class="fas fa-sign-out-alt me-2"></i> Terminar Sessão
    </a>
</div>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-chart-pie me-2"></i> Estatísticas
    </div>
    <div class="card-body">
        <?php
        // Get book statistics for current user
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            
            // Total books
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM books WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $total_books = $stmt->fetch()['total'];
            
            // Active books
            $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM books WHERE user_id = ? AND status = 'active'");
            $stmt->execute([$user_id]);
            $active_books = $stmt->fetch()['active'];
            
            // Get reading status
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(CASE WHEN status = 'currently_reading' THEN 1 END) as reading,
                    COUNT(CASE WHEN status = 'read' THEN 1 END) as read,
                    COUNT(CASE WHEN status = 'want_to_read' THEN 1 END) as want_to_read
                FROM reading_status 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $reading_stats = $stmt->fetch();
        }
        ?>
        
        <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Total de Livros
                <span class="badge bg-primary rounded-pill"><?php echo isset($total_books) ? $total_books : 0; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Livros Ativos
                <span class="badge bg-success rounded-pill"><?php echo isset($active_books) ? $active_books : 0; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                A Ler
                <span class="badge bg-warning rounded-pill"><?php echo isset($reading_stats) ? $reading_stats['reading'] : 0; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Lidos
                <span class="badge bg-info rounded-pill"><?php echo isset($reading_stats) ? $reading_stats['read'] : 0; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Quero Ler
                <span class="badge bg-secondary rounded-pill"><?php echo isset($reading_stats) ? $reading_stats['want_to_read'] : 0; ?></span>
            </li>
        </ul>
    </div>
</div>
