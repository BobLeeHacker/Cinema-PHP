    </div><!-- End of main container -->

    <!-- Footer -->
    <footer class="footer bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-film me-2"></i>Cinema DB</h5>
                    <p class="mb-3">O seu sistema de gestão de filmes favoritos.</p>
                    <p class="small text-muted">
                        &copy; <?php echo date('Y'); ?> Cinema DB. Todos os direitos reservados.
                    </p>
                </div>
                <div class="col-md-3">
                    <h5>Links Rápidos</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-decoration-none"><i class="fas fa-home me-1"></i> Início</a></li>
                        <li><a href="movies.php" class="text-decoration-none"><i class="fas fa-video me-1"></i> Filmes</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="dashboard.php" class="text-decoration-none"><i class="fas fa-tachometer-alt me-1"></i> Painel</a></li>
                            <li><a href="profile.php" class="text-decoration-none"><i class="fas fa-user me-1"></i> Meu Perfil</a></li>
                        <?php else: ?>
                            <li><a href="login.php" class="text-decoration-none"><i class="fas fa-sign-in-alt me-1"></i> Entrar</a></li>
                            <li><a href="register.php" class="text-decoration-none"><i class="fas fa-user-plus me-1"></i> Registrar</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contato</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> contato@cinemadb.com</li>
                        <li><i class="fas fa-phone me-2"></i> +351 123 456 789</li>
                        <li class="mt-3">
                            <a href="#" class="text-decoration-none me-2"><i class="fab fa-facebook fa-lg"></i></a>
                            <a href="#" class="text-decoration-none me-2"><i class="fab fa-twitter fa-lg"></i></a>
                            <a href="#" class="text-decoration-none me-2"><i class="fab fa-instagram fa-lg"></i></a>
                            <a href="#" class="text-decoration-none"><i class="fab fa-youtube fa-lg"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Main JavaScript -->
    <script src="assets/js/main.js"></script>
    
    <!-- Validation JavaScript -->
    <script src="assets/js/validation.js"></script>
</body>
</html>
