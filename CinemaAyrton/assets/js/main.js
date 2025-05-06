/**
 * Main JavaScript functionality for Cinema DB
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Handle movie delete confirmations
    const deleteButtons = document.querySelectorAll('.delete-movie');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Tem certeza que deseja excluir este filme? Esta ação não pode ser desfeita.')) {
                e.preventDefault();
            }
        });
    });

    // AJAX movie search in dashboard
    const dashboardSearchInput = document.getElementById('dashboard-search');
    if (dashboardSearchInput) {
        dashboardSearchInput.addEventListener('input', debounce(function() {
            const searchTerm = this.value.trim();
            if (searchTerm.length >= 2) {
                fetchMovies(searchTerm);
            } else if (searchTerm.length === 0) {
                fetchMovies(''); // Show all movies when search is cleared
            }
        }, 500));
    }

    // Handle movie sorting in dashboard
    const sortSelect = document.getElementById('sort-movies');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const searchTerm = dashboardSearchInput ? dashboardSearchInput.value.trim() : '';
            fetchMovies(searchTerm, this.value);
        });
    }

    // Handle rating stars
    const ratingInputs = document.querySelectorAll('.rating-input');
    ratingInputs.forEach(function(input) {
        const stars = input.querySelectorAll('.rating-star');
        const hiddenInput = input.querySelector('input[type="hidden"]');
        
        stars.forEach(function(star, index) {
            // Show current rating on load
            if (hiddenInput.value >= index + 1) {
                star.classList.add('fas');
                star.classList.remove('far');
            }
            
            // Handle mouse hover
            star.addEventListener('mouseenter', function() {
                for (let i = 0; i <= index; i++) {
                    stars[i].classList.add('fas');
                    stars[i].classList.remove('far');
                }
                for (let i = index + 1; i < stars.length; i++) {
                    stars[i].classList.add('far');
                    stars[i].classList.remove('fas');
                }
            });
            
            // Handle mouse click
            star.addEventListener('click', function() {
                hiddenInput.value = index + 1;
                
                for (let i = 0; i <= index; i++) {
                    stars[i].classList.add('fas');
                    stars[i].classList.remove('far');
                }
                for (let i = index + 1; i < stars.length; i++) {
                    stars[i].classList.add('far');
                    stars[i].classList.remove('fas');
                }
            });
        });
        
        // Handle mouse leave from rating container
        input.addEventListener('mouseleave', function() {
            const rating = parseInt(hiddenInput.value) || 0;
            
            stars.forEach(function(star, index) {
                if (index < rating) {
                    star.classList.add('fas');
                    star.classList.remove('far');
                } else {
                    star.classList.add('far');
                    star.classList.remove('fas');
                }
            });
        });
    });
});

/**
 * Debounce function to limit how often a function can be called
 * @param {Function} func The function to debounce
 * @param {number} wait Wait time in milliseconds
 * @return {Function} Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, wait);
    };
}

/**
 * Fetch movies via AJAX based on search term and sort order
 * @param {string} searchTerm The search term
 * @param {string} sortOrder The sort order
 */
function fetchMovies(searchTerm, sortOrder = 'title_asc') {
    const moviesContainer = document.getElementById('movies-container');
    if (!moviesContainer) return;
    
    // Show loading indicator
    moviesContainer.innerHTML = `
        <div class="spinner-container">
            <div class="spinner-border text-info" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    `;
    
    // Fetch movies from API
    fetch(`api/movies.php?search=${encodeURIComponent(searchTerm)}&sort=${encodeURIComponent(sortOrder)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na requisição');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Clear loading indicator
                moviesContainer.innerHTML = '';
                
                if (data.movies.length === 0) {
                    // No results found
                    moviesContainer.innerHTML = `
                        <div class="alert alert-info">
                            Nenhum filme encontrado para "${searchTerm}".
                        </div>
                    `;
                    return;
                }
                
                // Create a row for the movies
                const row = document.createElement('div');
                row.className = 'row';
                
                // Add each movie to the row
                data.movies.forEach(movie => {
                    const movieCard = createMovieCard(movie);
                    row.appendChild(movieCard);
                });
                
                moviesContainer.appendChild(row);
            } else {
                throw new Error(data.message || 'Erro ao buscar filmes');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            moviesContainer.innerHTML = `
                <div class="alert alert-danger">
                    ${error.message}
                </div>
            `;
        });
}

/**
 * Create a movie card element
 * @param {Object} movie The movie data
 * @return {HTMLElement} The movie card element
 */
function createMovieCard(movie) {
    const col = document.createElement('div');
    col.className = 'col-md-4 mb-4';
    
    // Generate star rating HTML
    let starsHtml = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= Math.round(movie.rating / 2)) {
            starsHtml += '<i class="fas fa-star text-warning"></i>';
        } else {
            starsHtml += '<i class="far fa-star text-warning"></i>';
        }
    }
    
    col.innerHTML = `
        <div class="card h-100">
            <div class="card-img-top bg-dark d-flex align-items-center justify-content-center" style="height: 200px;">
                <i class="fas fa-film fa-4x text-light opacity-50"></i>
            </div>
            <div class="card-body">
                <h5 class="card-title">${movie.title}</h5>
                <p class="card-text">
                    <small class="text-muted">
                        <i class="fas fa-user-alt me-1"></i> ${movie.director || 'Desconhecido'} | 
                        <i class="fas fa-calendar-alt me-1"></i> ${movie.release_year || 'N/A'}
                    </small>
                </p>
                <p class="card-text">
                    ${starsHtml} <small class="text-muted">(${movie.rating}/10)</small>
                </p>
                <p class="card-text">${movie.synopsis ? movie.synopsis.substring(0, 100) + '...' : 'Sem sinopse'}</p>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="movie_view.php?id=${movie.id}" class="btn btn-sm btn-info">
                    <i class="fas fa-eye me-1"></i> Ver
                </a>
                <div>
                    <a href="movie_edit.php?id=${movie.id}" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                    <a href="movies.php?delete=${movie.id}" class="btn btn-sm btn-danger delete-movie">
                        <i class="fas fa-trash-alt me-1"></i> Excluir
                    </a>
                </div>
            </div>
        </div>
    `;
    
    return col;
}
