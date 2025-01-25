<?php
require 'db.php';

try {
    // Validate inputs
    $requiredFields = ['recipe_id', 'username', 'comment'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Missing or empty $field");
        }
    }

    $recipeId = $_POST['recipe_id'];
    $username = strip_tags(trim($_POST['username']));
    $commentText = strip_tags(trim($_POST['comment']));

    // Prepare insert query
    $query = "INSERT INTO comments (recipe_id, username, comment) VALUES (:recipe_id, :username, :comment)";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([
        ':recipe_id' => $recipeId,
        ':username' => $username,
        ':comment' => $commentText
    ]);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $result, 
        'message' => $result ? 'Comment added successfully' : 'Failed to add comment'
    ]);

} catch(Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>