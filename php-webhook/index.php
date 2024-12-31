<?php
require_once 'config.php';

// Check rate limit
checkRateLimit($_SERVER['REMOTE_ADDR']);

// Validate API key
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
validateApiKey($apiKey);

// Validate and sanitize input parameters
$user = isset($_REQUEST["user"]) ? substr(trim($_REQUEST["user"]), 0, 255) : '';
$repo = isset($_REQUEST["repository"]) ? substr(trim($_REQUEST["repository"]), 0, 255) : '';

if (empty($user) || empty($repo)) {
    http_response_code(400);
    die(json_encode(['error' => 'Missing required parameters']));
}

try {
    $conn = getDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT LastPushDateTime FROM LastPushForRepository WHERE username = :username AND repository = :repository");
    $stmt->execute([
        'username' => $user,
        'repository' => $repo
    ]);
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        header('Content-Type: application/json');
        echo json_encode(['lastPushDateTime' => $row["LastPushDateTime"]]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No push data found for repository']);
    }
    
} catch(PDOException $e) {
    error_log("Database error in index.php: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => 'Internal server error']));
}