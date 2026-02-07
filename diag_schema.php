<?php
require_once 'includes/db.php';

echo "SCHEMA OF matches TABLE:\n";
$cols = query("DESCRIBE matches");
foreach ($cols as $c) echo "{$c['Field']} | {$c['Type']}\n";

echo "\nSAMPLE MATCH WITH GENDER:\n";
$sample = queryOne("SELECT * FROM matches LIMIT 1");
print_r($sample);
