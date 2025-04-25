document.addEventListener('DOMContentLoaded', function() {
    // Handle search form
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = document.getElementById('searchInput').value.trim();
            if (searchTerm.length > 0) {
                window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
            }
        });
    }

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (tooltipTriggerList.length > 0) {
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Handle recipe comment form
    const commentForm = document.getElementById('commentForm');
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const recipeId = this.querySelector('[name="recipe_id"]').value;
            const comment = this.querySelector('[name="comment"]').value.trim();
            const submitBtn = this.querySelector('button[type="submit"]');
            const commentsContainer = document.getElementById('commentsContainer');
            
            if (comment.length === 0) {
                showAlert('Please enter a comment', 'danger');
                return;
            }
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading me-2"></span> Submitting...';
            
            // AJAX request to add comment
            fetch('add_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `recipe_id=${recipeId}&comment=${encodeURIComponent(comment)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reset form
                    commentForm.reset();
                    
                    // Add new comment to the list
                    const newComment = createCommentElement(data.comment);
                    commentsContainer.insertBefore(newComment, commentsContainer.firstChild);
                    
                    showAlert('Comment added successfully!', 'success');
                } else {
                    showAlert(data.message || 'Failed to add comment', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Submit Comment';
            });
        });
    }
    
    // Recipe image preview before upload
    const recipeImageInput = document.getElementById('recipeImage');
    const imagePreview = document.getElementById('imagePreview');
    
    if (recipeImageInput && imagePreview) {
        recipeImageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Dynamic ingredient and step fields for recipe form
    const addIngredientBtn = document.getElementById('addIngredient');
    const ingredientsContainer = document.getElementById('ingredientsContainer');
    
    if (addIngredientBtn && ingredientsContainer) {
        addIngredientBtn.addEventListener('click', function() {
            const index = document.querySelectorAll('.ingredient-row').length;
            const newRow = document.createElement('div');
            newRow.className = 'ingredient-row input-group mb-2';
            newRow.innerHTML = `
                <input type="text" class="form-control" name="ingredients[]" required placeholder="Ingredient ${index + 1}">
                <button type="button" class="btn btn-outline-danger remove-ingredient">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;
            ingredientsContainer.appendChild(newRow);
            
            // Add event listener to the remove button
            newRow.querySelector('.remove-ingredient').addEventListener('click', function() {
                ingredientsContainer.removeChild(newRow);
            });
        });
    }
    
    const addStepBtn = document.getElementById('addStep');
    const stepsContainer = document.getElementById('stepsContainer');
    
    if (addStepBtn && stepsContainer) {
        addStepBtn.addEventListener('click', function() {
            const index = document.querySelectorAll('.step-row').length;
            const newRow = document.createElement('div');
            newRow.className = 'step-row input-group mb-2';
            newRow.innerHTML = `
                <span class="input-group-text">Step ${index + 1}</span>
                <textarea class="form-control" name="steps[]" required rows="2"></textarea>
                <button type="button" class="btn btn-outline-danger remove-step">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;
            stepsContainer.appendChild(newRow);
            
            // Add event listener to the remove button
            newRow.querySelector('.remove-step').addEventListener('click', function() {
                stepsContainer.removeChild(newRow);
            });
        });
    }
    
    // Helper Functions
    function showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alertContainer') || document.createElement('div');
        
        if (!document.getElementById('alertContainer')) {
            alertContainer.id = 'alertContainer';
            alertContainer.className = 'alert-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(alertContainer);
        }
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        alertContainer.appendChild(alert);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
    
    function createCommentElement(comment) {
        const div = document.createElement('div');
        div.className = 'comment-item';
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="comment-user">${escapeHTML(comment.username)}</span>
                <small class="comment-date">${comment.created_at}</small>
            </div>
            <p class="mb-0">${escapeHTML(comment.comment)}</p>
        `;
        return div;
    }
    
    function escapeHTML(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    // Mobile menu close on click
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    const navbarCollapse = document.getElementById('navbarNav');
    
    if (navbarCollapse && navLinks.length > 0) {
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
                    if (bsCollapse) {
                        bsCollapse.hide();
                    }
                }
            });
        });
    }
});