document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const recipeGrid = document.getElementById('recipeGrid');

    const recipeModal = new bootstrap.Modal(document.getElementById('recipeModal'));
    const recipeModalTitle = document.getElementById('recipeModalTitle');
    const recipeModalBody = document.getElementById('recipeModalBody');

    async function loadRecipes(query = '') {
        try {
            const response = await fetch(`../includes/load_recipes.php${query ? `?query=${encodeURIComponent(query)}` : ''}`);
            const recipes = await response.json();
            displayRecipes(recipes);
        } catch (error) {
            console.error('Error loading recipes:', error);
            recipeGrid.innerHTML = '<div class="alert alert-danger">Error loading recipes</div>';
        }
    }

    function displayRecipes(recipes) {
        recipeGrid.innerHTML = '';
        if (recipes.length === 0) {
            recipeGrid.innerHTML = '<div class="alert alert-info">No recipes found</div>';
            return;
        }

        recipes.forEach(recipe => {
            const card = document.createElement('div');
            card.className = 'card recipe-card h-100';
            card.innerHTML = `
                <img src="${recipe.image || 'placeholder.jpg'}" class="card-img-top recipe-image" alt="${recipe.food_name}">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title flex-grow-1">${recipe.food_name}</h5>
                    <button class="btn btn-custom view-recipe mt-auto" data-recipe-id="${recipe.id}">
                        View Details
                    </button>

                </div>
            `;

            const viewButton = card.querySelector('.view-recipe');
            viewButton.addEventListener('click', () => showRecipeDetails(recipe));

            recipeGrid.appendChild(card);
        });
    }

    function showRecipeDetails(recipe) {
        // Extract 'name' from ingredient objects or fallback to stringify if invalid
        const ingredients = Array.isArray(recipe.ingredients)
            ? recipe.ingredients.map(item => (item.name ? item.name : JSON.stringify(item)))
            : [];
        
        const steps = Array.isArray(recipe.steps)
            ? recipe.steps.map(item => (typeof item === 'string' ? item : JSON.stringify(item)))
            : [];
    
        recipeModalTitle.textContent = recipe.food_name;
        recipeModalBody.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <img src="${recipe.image || 'placeholder.jpg'}" class="img-fluid mb-3" alt="${recipe.food_name}">
                </div>
                <div class="col-md-6">
                    <div class="modal-ingredients">
                        <h4>Ingredients</h4>
                        <ul>
                            ${ingredients.map(ing => `<li><i class="fas fa-utensil-spoon me-2 text-primary"></i>${ing}</li>`).join('')}
                        </ul>
                    </div>
                    <div class="modal-steps">
                        <h4>Cooking Steps</h4>
                        <ol>
                            ${steps.map((step, index) => `
                                <li>
                                    <span class="fw-bold">Step ${index + 1}:</span> 
                                    ${step.trim()}
                                </li>
                            `).join('')}
                        </ol>
                    </div>
                </div>
            </div>
            <div class="comments-section mt-4">
                <h4>Comments</h4>
                <div id="commentsList"></div>
                <div class="mt-3">
                    <input type="text" id="commentName" class="form-control mb-2" placeholder="Your Name">
                    <textarea id="commentText" class="form-control mb-2" placeholder="Your Comment"></textarea>
                    <button class="btn btn-primary submit-comment" data-recipe-id="${recipe.id}">
                        Submit Comment
                    </button>
                </div>
            </div>
        `;
    

        loadComments(recipe.id);

        const submitCommentBtn = recipeModalBody.querySelector('.submit-comment');
        submitCommentBtn.addEventListener('click', () => submitComment(recipe.id));

        recipeModal.show();
    }

    async function loadComments(recipeId) {
        const commentsList = document.getElementById('commentsList');
        try {
            const response = await fetch(`../includes/load_comments.php?recipe_id=${recipeId}`);
            const comments = await response.json();
            commentsList.innerHTML = comments.length
                ? comments.map(c => `
                    <div class="card mb-2">
                        <div class="card-body">
                            <h6 class="card-title">${c.username}</h6>
                            <p class="card-text">${c.comment}</p>
                            <small class="text-muted">${new Date(c.created_at).toLocaleString()}</small>
                        </div>
                    </div>
                `).join('')
                : '<p class="text-muted">No comments yet. Be the first to comment!</p>';
        } catch (error) {
            console.error('Error loading comments:', error);
            commentsList.innerHTML = '<p class="text-danger">Error loading comments</p>';
        }
    }

    async function submitComment(recipeId) {
        const nameInput = document.getElementById('commentName');
        const commentInput = document.getElementById('commentText');

        if (!nameInput.value.trim() || !commentInput.value.trim()) {
            alert('Please enter both name and comment');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('recipe_id', recipeId);
            formData.append('username', nameInput.value);
            formData.append('comment', commentInput.value);

            const response = await fetch('../auth/add_comment.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                loadComments(recipeId);
                nameInput.value = '';
                commentInput.value = '';
            } else {
                alert(result.message || 'Failed to submit comment');
            }
        } catch (error) {
            console.error('Error submitting comment:', error);
            alert('Error submitting comment');
        }
    }

    searchButton.addEventListener('click', () => loadRecipes(searchInput.value));
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') loadRecipes(searchInput.value);
    });

    loadRecipes();
});