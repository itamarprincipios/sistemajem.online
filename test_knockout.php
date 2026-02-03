<?php
// Simple test to verify operator/knockout_manager.php exists and is accessible
echo "<h1>Testing Knockout Manager Access</h1>";

// Check if file exists
$file = __DIR__ . '/operator/knockout_manager.php';
if (file_exists($file)) {
    echo "<p style='color: green;'>✅ File EXISTS at: $file</p>";
    echo "<p>File size: " . filesize($file) . " bytes</p>";
    echo "<p>Last modified: " . date("Y-m-d H:i:s", filemtime($file)) . "</p>";
} else {
    echo "<p style='color: red;'>❌ File NOT FOUND at: $file</p>";
}

// Check session and role
session_start();
echo "<hr><h2>Session Check:</h2>";
if (isset($_SESSION['user_id'])) {
    require_once 'includes/db.php';
    $user = queryOne("SELECT id, name, role FROM users WHERE id = ?", [$_SESSION['user_id']]);
    echo "<p>Logged in as: <strong>{$user['name']}</strong></p>";
    echo "<p>Role: <strong>{$user['role']}</strong></p>";
    
    if ($user['role'] === 'operator') {
        echo "<p style='color: green;'>✅ You ARE an operator - sidebar should show Mata-Mata link</p>";
        echo "<p><a href='operator/knockout_manager.php' style='padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 5px;'>🏆 Go to Knockout Manager</a></p>";
    } else {
        echo "<p style='color: orange;'>⚠️ You are NOT an operator (you are {$user['role']})</p>";
        echo "<p>The Mata-Mata link will NOT appear in your sidebar</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Not logged in</p>";
}

echo "<hr><h2>Direct Access Test:</h2>";
echo "<p>Try accessing directly:</p>";
echo "<ul>";
echo "<li><a href='operator/knockout_manager.php' target='_blank'>operator/knockout_manager.php</a></li>";
echo "<li><a href='debug_role.php' target='_blank'>debug_role.php</a></li>";
echo "</ul>";
?>
