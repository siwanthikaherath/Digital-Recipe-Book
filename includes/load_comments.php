<?php
require 'db.php';

try {
    // Validate recipe_id
    if (!isset($_GET['recipe_id']) || !is_numeric($_GET['recipe_id'])) {
        throw new Exception('Invalid recipe ID');
    }

    $recipeId = $_GET['recipe_id'];

    // Prepare query to fetch comments
    $query = "SELECT * FROM comments WHERE recipe_id = :recipe_id ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':recipe_id' => $recipeId]);
    $comments = $stmt->fetchAll();

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($comments);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>