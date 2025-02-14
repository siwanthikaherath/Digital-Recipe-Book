<?php
session_start();
require '../includes/db.php';

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
            $uploadDir = '../includes/uploads/';
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

    <link rel="stylesheet" href="../css/add_recipe.css">
    
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

    <!--Javascript code-->
    <script src="../js/add_recipe.js"></script>


</body>
</html>