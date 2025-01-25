<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not authenticated');
        }

        if (empty($_POST['recipeName']) || empty($_POST['ingredients']) || empty($_POST['steps'])) {
            throw new Exception('Missing required fields');
        }

        $recipeName = htmlspecialchars(trim($_POST['recipeName']));
        $ingredients = json_decode($_POST['ingredients'], true);
        $steps = json_decode($_POST['steps'], true);

        if (!$ingredients || !$steps) {
            throw new Exception('Invalid data format');
        }

        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $fileName;

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $_FILES['image']['tmp_name']);
            finfo_close($fileInfo);

            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception('Invalid image format');
            }

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                throw new Exception('Failed to upload image');
            }

            $imagePath = $targetPath;
        }

        $stmt = $pdo->prepare("INSERT INTO recipes (food_name, ingredients, steps, image, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $recipeName,
            json_encode($ingredients),
            json_encode($steps),
            $imagePath,
            $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Recipe added successfully']);
        exit;

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Recipe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .recipe-form {
            background-color: white;
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 12px;
        }
        .ingredient-row, .step-container {
            position: relative;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .delete-btn {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            color: #dc3545;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        .delete-btn:hover {
            opacity: 1;
        }
        .preview-image {
            max-width: 250px;
            max-height: 250px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="recipe-form">
            <h2 class="text-center mb-4">Create New Recipe</h2>
            <form id="recipeForm">
                <div class="mb-3">
                    <label for="recipeName" class="form-label">Recipe Name</label>
                    <input type="text" class="form-control" id="recipeName" required maxlength="100">
                    <div class="form-text">Maximum 100 characters</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ingredients</label>
                    <div id="ingredientsList">
                        <div class="ingredient-row d-flex gap-2 align-items-center">
                            <input type="text" class="form-control" placeholder="Ingredient" required maxlength="50">
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeIngredient(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary mt-2" onclick="addIngredient()">
                        <i class="fas fa-plus"></i> Add Ingredient
                    </button>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cooking Steps</label>
                    <div id="stepsList">
                        <div class="step-container">
                            <textarea class="form-control" placeholder="Step 1" required maxlength="300"></textarea>
                            <i class="fas fa-times delete-btn" onclick="removeStep(this)"></i>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary mt-2" onclick="addStep()">
                        <i class="fas fa-plus"></i> Add Step
                    </button>
                </div>

                <div class="mb-3">
                    <label for="recipeImage" class="form-label">Recipe Image</label>
                    <input type="file" class="form-control" id="recipeImage" accept="image/jpeg,image/png,image/gif" onchange="previewImage(event)">
                    <img id="imagePreview" class="preview-image mt-2 d-none" alt="Recipe preview">
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-upload me-2"></i>Submit Recipe
                </button>
            </form>
        </div>
    </div>

    <div id="loading" class="loading d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script>
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
                    window.location.href = 'recipies.html';
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
    </script>
</body>
</html>