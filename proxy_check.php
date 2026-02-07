<?php
$url = "https://sistemajem.online/api/standings-api.php?action=group_standings&event_id=3&modality_id=12&category_id=5&gender=F";
$resp = file_get_contents($url);
header('Content-Type: application/json');
echo $resp;
