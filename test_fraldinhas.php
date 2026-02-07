<?php
require_once 'config/config.php';
require_once 'includes/db.php';

echo "=== Fraldinhas Category Test ===\n\n";

// Find Fraldinhas category
$category = queryOne("SELECT id, name FROM categories WHERE name LIKE '%Fraldinhas%'");
if (!$category) {
    echo "❌ Fraldinhas category not found\n";
    exit;
}
echo "✅ Category: {$category['name']} (ID: {$category['id']})\n\n";

// Get active event
$event = queryOne("SELECT id, name FROM competition_events WHERE active_flag = TRUE LIMIT 1");
if (!$event) {
    echo "❌ No active event found\n";
    exit;
}
echo "✅ Active Event: {$event['name']} (ID: {$event['id']})\n\n";

// Get modality (assuming Futsal)
$modality = queryOne("SELECT id, name FROM modalities WHERE name LIKE '%Futsal%'");
if (!$modality) {
    echo "❌ Futsal modality not found\n";
    exit;
}
echo "✅ Modality: {$modality['name']} (ID: {$modality['id']})\n\n";

// Get teams in this category
$teams = query("SELECT id, school_name_snapshot, group_name, gender 
                FROM competition_teams 
                WHERE competition_event_id = ? 
                AND modality_id = ? 
                AND category_id = ?
                ORDER BY group_name, school_name_snapshot", 
                [$event['id'], $modality['id'], $category['id']]);

echo "=== Teams ({count($teams)}) ===\n";
foreach ($teams as $team) {
    echo "- {$team['school_name_snapshot']} | Group: {$team['group_name']} | Gender: {$team['gender']}\n";
}
echo "\n";

// Get group stage matches
$matches = query("SELECT m.id, m.phase, m.status, m.scheduled_time,
                  ta.school_name_snapshot as team_a,
                  tb.school_name_snapshot as team_b,
                  m.score_team_a, m.score_team_b
                  FROM matches m
                  JOIN competition_teams ta ON m.team_a_id = ta.id
                  JOIN competition_teams tb ON m.team_b_id = tb.id
                  WHERE m.competition_event_id = ?
                  AND m.modality_id = ?
                  AND m.category_id = ?
                  AND m.phase = 'group_stage'
                  ORDER BY m.scheduled_time", 
                  [$event['id'], $modality['id'], $category['id']]);

echo "=== Group Stage Matches ({count($matches)}) ===\n";
$finished = 0;
foreach ($matches as $match) {
    $status = $match['status'];
    $score = ($status === 'finished') ? "{$match['score_team_a']} x {$match['score_team_b']}" : "N/A";
    echo "- {$match['team_a']} vs {$match['team_b']} | Status: {$status} | Score: {$score}\n";
    if ($status === 'finished') $finished++;
}
echo "\nFinished: {$finished}/{count($matches)}\n\n";

// Test standings calculation
if ($finished > 0) {
    require_once 'includes/knockout_generator.php';
    
    echo "=== Testing Standings Calculation ===\n";
    $standings = calculateGroupStandings($event['id'], $modality['id'], $category['id']);
    
    foreach ($standings as $groupName => $teams) {
        echo "\nGroup {$groupName}:\n";
        foreach ($teams as $team) {
            echo sprintf("  %d. %s - P:%d W:%d D:%d L:%d GD:%+d Pts:%d\n",
                $team['position'],
                $team['team_name'],
                $team['played'],
                $team['won'],
                $team['drawn'],
                $team['lost'],
                $team['goal_difference'],
                $team['points']
            );
        }
    }
}

echo "\n=== Test Complete ===\n";
?>
