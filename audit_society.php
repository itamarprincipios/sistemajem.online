<?php
require_once 'includes/db.php';

$sql = "
    SELECT cat.id as cat_id, cat.name as cat_name, ct.gender, COUNT(DISTINCT ct.group_name) as group_count, COUNT(*) as team_count
    FROM competition_teams ct
    JOIN categories cat ON ct.category_id = cat.id
    WHERE ct.competition_event_id = 3 AND ct.modality_id = 12
    GROUP BY cat.id, cat.name, ct.gender
    ORDER BY cat.name, ct.gender
";

$stats = query($sql);
header('Content-Type: application/json');
echo json_encode($stats, JSON_PRETTY_PRINT);
