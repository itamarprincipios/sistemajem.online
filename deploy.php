<?php
/**
 * Deploy Script - Pull latest changes from GitHub
 * 
 * SECURITY WARNING: This file should be protected!
 * Add authentication or delete after use.
 */

// Security: Simple token authentication
define('DEPLOY_TOKEN', 'jem_deploy_2024'); // Change this to a secure token

// Check if token is provided
if (!isset($_GET['token']) || $_GET['token'] !== DEPLOY_TOKEN) {
    http_response_code(403);
    die('Access denied. Invalid token.');
}

// Set execution time limit
set_time_limit(300); // 5 minutes

echo "<h1>🚀 Deploy Script - Sistema JEM</h1>";
echo "<pre>";

// Get the current directory
$dir = __DIR__;
echo "📁 Current directory: $dir\n\n";

// Check if git is available
echo "🔍 Checking Git installation...\n";
exec('git --version 2>&1', $output, $return);
if ($return !== 0) {
    echo "❌ ERROR: Git is not installed or not available.\n";
    echo implode("\n", $output);
    exit(1);
}
echo "✅ " . implode("\n", $output) . "\n\n";
$output = [];

// Check if this is a git repository
echo "🔍 Checking if this is a Git repository...\n";
if (!is_dir($dir . '/.git')) {
    echo "❌ ERROR: This directory is not a Git repository.\n";
    echo "You need to clone the repository first.\n";
    exit(1);
}
echo "✅ Git repository detected\n\n";

// Get current branch
echo "📌 Current branch:\n";
exec('git branch 2>&1', $output, $return);
echo implode("\n", $output) . "\n\n";
$output = [];

// Fetch latest changes
echo "📥 Fetching latest changes from remote...\n";
exec('git fetch origin 2>&1', $output, $return);
echo implode("\n", $output) . "\n";
if ($return !== 0) {
    echo "❌ ERROR: Failed to fetch from remote.\n";
    exit(1);
}
echo "✅ Fetch completed\n\n";
$output = [];

// Check current status
echo "📊 Current status:\n";
exec('git status 2>&1', $output, $return);
echo implode("\n", $output) . "\n\n";
$output = [];

// Pull latest changes
echo "⬇️ Pulling latest changes from origin/main...\n";
exec('git pull origin main 2>&1', $output, $return);
echo implode("\n", $output) . "\n";

if ($return === 0) {
    echo "\n✅ Deploy completed successfully!\n";
    echo "🎉 Your site is now up to date.\n";
} else {
    echo "\n❌ ERROR: Deploy failed.\n";
    echo "Please check the errors above and try again.\n";
}

// Show latest commit
echo "\n📝 Latest commit:\n";
exec('git log -1 --oneline 2>&1', $output, $return);
echo implode("\n", $output) . "\n";

echo "</pre>";

echo "<hr>";
echo "<p><strong>⚠️ SECURITY WARNING:</strong> Delete this file after use or protect it with strong authentication!</p>";
?>
