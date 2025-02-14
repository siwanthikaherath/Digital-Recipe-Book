<?php
require 'db.php';

try {
    // Prepare base query
    $query = "SELECT * FROM recipes WHERE 1=1";
    $params = [];

    // Check if search query is provided
    if (isset($_GET['query']) && !empty($_GET['query'])) {
        $searchTerm = '%' . $_GET['query'] . '%';
        $query .= " AND (food_name LIKE :search OR ingredients LIKE :search)";
        $params[':search'] = $searchTerm;
    }

    // Prepare and execute statement
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode ingredients and steps from JSON
    foreach ($recipes as &$recipe) {
        $recipe['ingredients'] = json_decode($recipe['ingredients'], true) ?: [];
        $recipe['steps'] = json_decode($recipe['steps'], true) ?: [];
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($recipes);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
