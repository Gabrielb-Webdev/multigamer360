<?php
/**
 * GitHub Webhook Handler for Hostinger
 * This file receives push notifications from GitHub and automatically pulls updates
 * 
 * Setup in GitHub:
 * 1. Go to: Settings → Webhooks → Add webhook
 * 2. Payload URL: https://your-site.hostingersite.com/github-webhook.php
 * 3. Content type: application/json
 * 4. Events: Just the push event
 */

// Log file
$log_file = __DIR__ . '/logs/webhook.log';

// Function to log messages
function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_dir = dirname($log_file);
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Get the payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Verify it's a push event
if (isset($_SERVER['HTTP_X_GITHUB_EVENT']) && $_SERVER['HTTP_X_GITHUB_EVENT'] === 'push') {
    
    logMessage("GitHub webhook received - Starting pull...");
    
    // Change to the repository directory
    $repo_path = __DIR__;
    chdir($repo_path);
    
    // Execute git pull
    $output = [];
    $return_var = 0;
    
    // Fetch and pull from origin/main
    exec('git fetch origin 2>&1', $output, $return_var);
    logMessage("Git fetch output: " . implode("\n", $output));
    
    $output = [];
    exec('git reset --hard origin/main 2>&1', $output, $return_var);
    logMessage("Git reset output: " . implode("\n", $output));
    
    $output = [];
    exec('git pull origin main 2>&1', $output, $return_var);
    logMessage("Git pull output: " . implode("\n", $output));
    
    if ($return_var === 0) {
        logMessage("✅ Pull successful!");
        
        // Clear OPcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
            logMessage("OPcache cleared");
        }
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Repository updated successfully',
            'commit' => $data['after'] ?? 'unknown'
        ]);
    } else {
        logMessage("❌ Pull failed with return code: $return_var");
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Git pull failed',
            'output' => $output
        ]);
    }
} else {
    logMessage("Invalid request - not a push event");
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid webhook event'
    ]);
}
