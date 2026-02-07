<?php
require_once 'includes/knockout_generator.php';

$refl = new ReflectionFunction('isPhaseComplete');
$file = $refl->getFileName();
$start = $refl->getStartLine();
$end = $refl->getEndLine();

echo "File: $file\n";
echo "Lines: $start - $end\n\n";

$lines = file($file);
for ($i = $start - 1; $i < $end; $i++) {
    echo ($i + 1) . ": " . $lines[$i];
}
