/**
 * Books JavaScript functionality
 * Handles book-related operations like adding, editing, and deleting books
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize book search filters
    initializeBookFilters();
    
    // Initialize book deletion confirmation
    initializeBookDeletion();
    
    // Initialize book status toggling
    initializeBookStatusToggle();
    
    // Initialize book form submission
    initializeBookForm();
    
    // Initialize lazy loading for book images
    initializeLazyLoading();
});

/**
 * Initialize book search filters
 */
function initializeBookFilters() {
    const filterForm = document.getElementById('bookFilterForm');
    
    if (filterForm) {
        // Handle filter form submission
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading indicator
            const resultsContainer = document.getElementById('bookResults');
            if (resultsContainer) {
                resultsContainer.innerHTML = '<div class="text-center p-5"><div class="loading-spinner"></div><p class="mt-3">A carregar resultados...</p></div>';
            }
            
            // Collect form data
            const formData = new FormData(this);
            const searchParams = new URLSearchParams();
            
            for (const [key, value] of formData.entries()) {
                if (value.trim() !== '') {
                    searchParams.append(key, value);
                }
            }
            
            // AJAX request to get filtered books
            fetch('api/books.php?' + searchParams.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        displayBooks(data.books, resultsContainer);
                    } else {
                        resultsContainer.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching books:', error);
                    resultsContainer.innerHTML = '<div class="alert alert-danger">Erro ao carregar livros. Por favor, tente novamente.</div>';
                });
        });
        
        // Handle filter reset
        const resetButton = filterForm.querySelector('button[type="reset"]');
        if (resetButton) {
            resetButton.addEventListener('click', function() {
                // Wait for form reset then submit to get all books
                setTimeout(() => {
                    filterForm.dispatchEvent(new Event('submit'));
                }, 10);
            });
        }
        
        // Submit on page load to initialize with all books
        filterForm.dispatchEvent(new Event('submit'));
    }
}

/**
 * Display books in the results container
 * 
 * @param {Array} books - Array of book objects
 * @param {HTMLElement} container - Container element to display books in
 */
function displayBooks(books, container) {
    if (!books || books.length === 0) {
        container.innerHTML = '<div class="alert alert-info">Nenhum livro encontrado.</div>';
        return;
    }
    
    let html = '<div class="row">';
    
    books.forEach(book => {
        html += `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 book-item">
                    <div class="position-relative">
                        <img src="${book.cover_image || 'https://via.placeholder.com/300x450?text=Sem+Capa'}" 
                            class="card-img-top book-cover" alt="${book.title}" loading="lazy">
                        <div class="position-absolute top-0 end-0 p-2">
                            ${book.status === 'active' 
                                ? '<span class="badge bg-success">Ativo</span>' 
                                : '<span class="badge bg-secondary">Inativo</span>'}
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title book-title">${book.title}</h5>
                        <p class="card-text book-author">por ${book.author}</p>
                        <p class="card-text book-description">${book.description || 'Sem descrição disponível.'}</p>
                    </div>
                    <div class="card-footer bg-transparent d-flex justify-content-between">
                        <a href="book_view.php?id=${book.id}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                        <a href="book_edit.php?id=${book.id}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-book" data-book-id="${book.id}" data-book-title="${book.title}">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
    
    // Re-initialize deletion confirmation for new buttons
    initializeBookDeletion();
}

/**
 * Initialize book deletion confirmation
 */
function initializeBookDeletion() {
    const deleteButtons = document.querySelectorAll('.delete-book');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const bookId = this.dataset.bookId;
            const bookTitle = this.dataset.bookTitle;
            
            showConfirmationModal(
                'Confirmar Eliminação',
                `Tem a certeza que pretende eliminar o livro "${bookTitle}"? Esta ação não pode ser desfeita.`,
                'Eliminar',
                'btn-danger',
                function() {
                    // Send AJAX request to delete the book
                    fetch('api/books.php?action=delete&id=' + bookId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                showToast('Sucesso', data.message, 'success');
                                
                                // Remove book from display or reload page
                                const bookItem = button.closest('.col-md-6');
                                if (bookItem) {
                                    bookItem.remove();
                                } else {
                                    location.reload();
                                }
                            } else {
                                showToast('Erro', data.message, 'danger');
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting book:', error);
                            showToast('Erro', 'Erro ao eliminar livro. Por favor, tente novamente.', 'danger');
                        });
                }
            );
        });
    });
}

/**
 * Initialize book status toggle
 */
function initializeBookStatusToggle() {
    const statusToggles = document.querySelectorAll('.toggle-status');
    
    statusToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const bookId = this.dataset.bookId;
            const currentStatus = this.dataset.currentStatus;
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            // Send AJAX request to update status
            fetch(`api/books.php?action=update_status&id=${bookId}&status=${newStatus}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast('Sucesso', data.message, 'success');
                        
                        // Update button text and data attributes
                        this.dataset.currentStatus = newStatus;
                        
                        if (newStatus === 'active') {
                            this.innerHTML = '<i class="fas fa-toggle-on text-success"></i> Ativo';
                            this.classList.remove('btn-outline-secondary');
                            this.classList.add('btn-outline-success');
                        } else {
                            this.innerHTML = '<i class="fas fa-toggle-off text-secondary"></i> Inativo';
                            this.classList.remove('btn-outline-success');
                            this.classList.add('btn-outline-secondary');
                        }
                        
                        // Update badge if exists
                        const badge = document.querySelector(`.status-badge[data-book-id="${bookId}"]`);
                        if (badge) {
                            badge.textContent = newStatus === 'active' ? 'Ativo' : 'Inativo';
                            badge.className = `badge ${newStatus === 'active' ? 'bg-success' : 'bg-secondary'} status-badge`;
                        }
                    } else {
                        showToast('Erro', data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error updating book status:', error);
                    showToast('Erro', 'Erro ao atualizar estado do livro. Por favor, tente novamente.', 'danger');
                });
        });
    });
}

/**
 * Initialize book form submission
 */
function initializeBookForm() {
    const bookForm = document.getElementById('bookForm');
    
    if (bookForm) {
        bookForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm(this)) {
                return;
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> A processar...';
            
            // Get form data
            const formData = new FormData(this);
            
            // Get book ID if editing
            const bookId = this.dataset.bookId;
            let url = 'api/books.php?action=add';
            
            if (bookId) {
                url = `api/books.php?action=update&id=${bookId}`;
            }
            
            // Send AJAX request
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                if (data.status === 'success') {
                    showToast('Sucesso', data.message, 'success');
                    
                    // Redirect after successful operation
                    setTimeout(() => {
                        window.location.href = bookId ? `book_view.php?id=${bookId}` : 'books.php';
                    }, 1500);
                } else {
                    showToast('Erro', data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error submitting book form:', error);
                
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                showToast('Erro', 'Erro ao processar o formulário. Por favor, tente novamente.', 'danger');
            });
        });
    }
}

/**
 * Initialize lazy loading for book images
 */
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[loading="lazy"]');
        
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('fade-in');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(function(image) {
            imageObserver.observe(image);
        });
    }
}
