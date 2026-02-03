<?php
// Debug script to check user role and sidebar visibility
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

session_start();

echo "<h1>Debug: User Role Check</h1>";

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $user = queryOne("SELECT id, name, email, role FROM users WHERE id = ?", [$userId]);
    
    echo "<h2>Current User:</h2>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    echo "<h2>Session Data:</h2>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<h2>Sidebar Visibility:</h2>";
    $userRole = $user['role'];
    echo "<p>User Role: <strong>$userRole</strong></p>";
    
    if ($userRole === 'operator') {
        echo "<p style='color: green;'>✅ Operator navigation SHOULD be visible</p>";
        echo "<p>Expected links:</p>";
        echo "<ul>";
        echo "<li>📊 Minhas Partidas (operator/dashboard.php)</li>";
        echo "<li>🏆 Mata-Mata (operator/knockout_manager.php)</li>";
        echo "</ul>";
    } elseif ($userRole === 'admin') {
        echo "<p style='color: orange;'>⚠️ You are logged in as ADMIN, not operator</p>";
        echo "<p>Operator navigation will NOT appear for admins</p>";
    } else {
        echo "<p style='color: red;'>❌ You are logged in as $userRole</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Not logged in</p>";
}

echo "<hr>";
echo "<h2>All Operators in Database:</h2>";
$operators = query("SELECT u.id, u.name, u.email, u.role FROM users u WHERE u.role = 'operator'");
if (empty($operators)) {
    echo "<p style='color: red;'>⚠️ No operators found in database!</p>";
    echo "<p>You may need to create an operator account or change an existing user's role to 'operator'</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    foreach ($operators as $op) {
        echo "<tr>";
        echo "<td>{$op['id']}</td>";
        echo "<td>{$op['name']}</td>";
        echo "<td>{$op['email']}</td>";
        echo "<td>{$op['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
