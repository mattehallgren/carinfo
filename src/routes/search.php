<?php

require_once '../controllers/SearchController.php';  

$searchController = new SearchController();

$params = $_GET;

try {
    $results = $searchController->search($params);

    echo json_encode([
        'success' => true,
        'data' => $results,
        'count' => count($results)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
