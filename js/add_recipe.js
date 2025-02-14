function addIngredient() {
    const ingredientsList = document.getElementById('ingredientsList');
    const ingredientCount = ingredientsList.children.length;
    
    if (ingredientCount >= 15) {
        alert('Maximum of 15 ingredients allowed');
        return;
    }

    const row = document.createElement('div');
    row.className = 'ingredient-row d-flex gap-2 align-items-center';
    row.innerHTML = `
        <input type="text" class="form-control" placeholder="Ingredient" required maxlength="50">
        <button type="button" class="btn btn-danger btn-sm" onclick="removeIngredient(this)">
            <i class="fas fa-trash"></i>
        </button>
    `;
    ingredientsList.appendChild(row);
}

function removeIngredient(btn) {
    const ingredientRows = document.querySelectorAll('.ingredient-row');
    if (ingredientRows.length > 1) {
        btn.closest('.ingredient-row').remove();
    }
}

function addStep() {
    const stepsList = document.getElementById('stepsList');
    const stepCount = stepsList.children.length;
    
    if (stepCount >= 20) {
        alert('Maximum of 20 steps allowed');
        return;
    }

    const container = document.createElement('div');
    container.className = 'step-container';
    container.innerHTML = `
        <textarea class="form-control" placeholder="Step ${stepCount + 1}" required maxlength="300"></textarea>
        <i class="fas fa-times delete-btn" onclick="removeStep(this)"></i>
    `;
    stepsList.appendChild(container);
}

function removeStep(icon) {
    const stepContainers = document.querySelectorAll('.step-container');
    if (stepContainers.length > 1) {
        icon.closest('.step-container').remove();
        updateStepNumbers();
    }
}

function updateStepNumbers() {
    document.querySelectorAll('.step-container textarea').forEach((textarea, index) => {
        textarea.placeholder = `Step ${index + 1}`;
    });
}

function previewImage(event) {
    const preview = document.getElementById('imagePreview');
    const file = event.target.files[0];
    
    if (file) {
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Image must be less than 5MB');
            event.target.value = ''; // Clear the file input
            preview.classList.add('d-none');
            return;
        }

        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
    }
}

document.getElementById('recipeForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const loading = document.getElementById('loading');
    loading.classList.remove('d-none');

    try {
        const formData = new FormData();
        formData.append('recipeName', document.getElementById('recipeName').value.trim());
        
        const ingredients = [];
        document.querySelectorAll('.ingredient-row').forEach(row => {
            const ingredientName = row.querySelector('input').value.trim();
            
            if (ingredientName) {
                ingredients.push({
                    name: ingredientName
                });
            }
        });
        formData.append('ingredients', JSON.stringify(ingredients));

        const steps = [];
        document.querySelectorAll('.step-container textarea').forEach(textarea => {
            const stepText = textarea.value.trim();
            if (stepText) {
                steps.push(stepText);
            }
        });
        formData.append('steps', JSON.stringify(steps));

        const imageFile = document.getElementById('recipeImage').files[0];
        if (imageFile) {
            formData.append('image', imageFile);
        }

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        if (result.success) {
            alert('Recipe added successfully!');
            window.location.href = '../includes/recipies.php';
        } else {
            alert(result.message || 'Error adding recipe');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while submitting the recipe');
    } finally {
        loading.classList.add('d-none');
    }
});