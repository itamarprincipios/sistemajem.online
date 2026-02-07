<?php
require_once 'includes/db.php';
require_once 'includes/knockout_generator.php';

$eventId = 3;
$modId = 12;
$catId = 5;
$gender = 'F';

$standings = calculateGroupStandings($eventId, $modId, $catId, $gender);
$groups = array_keys($standings);
sort($groups);

$matches = [];
for ($i = 0; $i < count($groups); $i++) {
    $groupA = $groups[$i];
    $groupB = $groups[($i + 1) % count($groups)];
    
    $firstA = null;
    foreach ($standings[$groupA] as $t) {
        if ($t['position'] === 1) { $firstA = $t; break; }
    }
    
    $secondB = null;
    foreach ($standings[$groupB] as $t) {
        if ($t['position'] === 2) { $secondB = $t; break; }
    }
    
    if ($firstA && $secondB) {
        $matches[] = [
            'a' => $firstA['team_name'] . " (1º $groupA)",
            'b' => $secondB['team_name'] . " (2º $groupB)"
        ];
    }
}

echo "Groups found: " . implode(', ', $groups) . "\n";
echo "Matches predicted: " . count($matches) . "\n";
foreach ($matches as $m) {
    echo $m['a'] . " VS " . $m['b'] . "\n";
}
