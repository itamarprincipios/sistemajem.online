<?php
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

header('Content-Type: text/plain');

$userId = getCurrentUserId();
$isAdmin = isAdmin();
echo "User ID: $userId\n";
echo "Is Admin: " . ($isAdmin ? "Yes" : "No") . "\n";

if ($userId) {
    if (!$isAdmin) {
        $opInfo = queryOne("SELECT * FROM competition_operators WHERE user_id = ? AND active = 1", [$userId]);
        echo "Operator Info:\n";
        print_r($opInfo);
    }
}

echo "\nRecent Matches (Last 5):\n";
$recent = query("SELECT id, modality_id, scheduled_time, venue, status FROM matches ORDER BY id DESC LIMIT 5");
print_r($recent);
