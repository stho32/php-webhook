<?php
// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    $envFile = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $envFile);
    foreach ($lines as $line) {
        if (strlen(trim($line)) && strpos(trim($line), '#') !== 0) {
            putenv(trim($line));
        }
    }
}

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS'));

// Security configuration
define('GITHUB_WEBHOOK_SECRET', getenv('GITHUB_WEBHOOK_SECRET'));
define('API_KEY', getenv('API_KEY'));

// Rate limiting
define('RATE_LIMIT_PER_MINUTE', 60);

// Function to establish database connection
function getDatabaseConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $conn;
    } catch(PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        http_response_code(500);
        die(json_encode(['error' => 'Database connection failed']));
    }
}

// Function to validate GitHub webhook signature
function isValidGitHubWebhook($payload, $signature) {
    if (!$signature) {
        return false;
    }
    
    list($algo, $hash) = explode('=', $signature, 2);
    if ($algo !== 'sha256') {
        return false;
    }
    
    $payloadHash = hash_hmac('sha256', $payload, GITHUB_WEBHOOK_SECRET);
    return hash_equals($hash, $payloadHash);
}

// Function to validate API key
function validateApiKey($providedKey) {
    if (!$providedKey || $providedKey !== API_KEY) {
        http_response_code(401);
        die(json_encode(['error' => 'Invalid API key']));
    }
}

// Rate limiting function
function checkRateLimit($ip) {
    $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($ip);
    
    $current = [];
    if (file_exists($cacheFile)) {
        $current = json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    
    $now = time();
    $current = array_filter($current, function($timestamp) use ($now) {
        return $timestamp > ($now - 60);
    });
    
    if (count($current) >= RATE_LIMIT_PER_MINUTE) {
        http_response_code(429);
        die(json_encode(['error' => 'Rate limit exceeded']));
    }
    
    $current[] = $now;
    file_put_contents($cacheFile, json_encode($current));
}
