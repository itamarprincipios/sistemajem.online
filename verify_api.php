<?php
require_once 'includes/db.php';

$eventId = 3;
$modId = 12;
$catId = 5;
$gender = 'F';

// Parameters to call the API
$url = "https://sistemajem.online/api/knockout-api.php?action=knockout_status&event_id=$eventId&modality_id=$modId&category_id=$catId&gender=$gender";

header('Content-Type: application/json');

// We simulate the API call logic here to be sure, or we could use curl
// But since I'm on the server, I can just call the logic in knockout-api.php or check it via curl

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// We might need to bypass auth if it's strict, but for diag it should work if we are logged in or if we mock auth
// Actually, I'll just check the logic directly in a script that mimics knockout-api.php

echo file_get_contents($url);
