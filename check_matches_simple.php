<?php
require_once 'includes/db.php';

$sql = "
    SELECT m.id, m.category_id, m.modality_id, t1.gender as team_gender, cat.name as category_name, mdl.name as modality_name
    FROM matches m
    LEFT JOIN competition_teams t1 ON m.team_a_id = t1.id
    LEFT JOIN categories cat ON m.category_id = cat.id
    LEFT JOIN modalities mdl ON m.modality_id = mdl.id
    WHERE cat.name LIKE '%Fraldinha%' AND mdl.name LIKE '%Society%'
";

$matches = query($sql);
header('Content-Type: application/json');
echo json_encode($matches, JSON_PRETTY_PRINT);
