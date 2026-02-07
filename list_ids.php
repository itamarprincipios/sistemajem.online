<?php
require_once 'includes/db.php';

echo "MODALITIES:\n";
$mods = query("SELECT id, name FROM modalities");
foreach ($mods as $m) echo "{$m['id']} | {$m['name']}\n";

echo "\nCATEGORIES:\n";
$cats = query("SELECT id, name FROM categories");
foreach ($cats as $c) echo "{$c['id']} | {$c['name']}\n";

echo "\nCOMPETITION TEAMS (Categories present):\n";
$ct = query("SELECT DISTINCT category_id, modality_id FROM competition_teams");
foreach ($ct as $row) echo "Cat: {$row['category_id']} | Mod: {$row['modality_id']}\n";
