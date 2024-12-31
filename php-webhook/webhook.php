<?php
require_once 'config.php';

// Verify that this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

// Get and validate the webhook payload
$payload = file_get_contents('php://input');
if (!$payload) {
    http_response_code(400);
    die(json_encode(['error' => 'No payload received']));
}

// Validate GitHub signature
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
if (!isValidGitHubWebhook($payload, $signature)) {
    http_response_code(401);
    die(json_encode(['error' => 'Invalid signature']));
}

// Decode and validate the payload
$data = json_decode($payload);
if (!$data || !isset($data->repository->full_name)) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid payload format']));
}

// Extract repository information
$fullName = $data->repository->full_name;
$split = explode("/", $fullName);

if (count($split) !== 2) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid repository name format']));
}

// Sanitize input
$user = substr(trim($split[0]), 0, 255);
$repo = substr(trim($split[1]), 0, 255);

try {
    $conn = getDatabaseConnection();
    
    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("CALL SavePush(:username, :repository)");
    $stmt->execute([
        'username' => $user,
        'repository' => $repo
    ]);
    
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch(PDOException $e) {
    error_log("Database error in webhook.php: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => 'Internal server error']));
}
?>
