<?php
require_once 'config/config.php';
require_once 'includes/db.php';

echo "--- MODALITY & REGISTRATION CHECK ---\n";
try {
    $mods = query("SELECT id, name FROM modalities");
    echo "Modalities Found: \n";
    foreach ($mods as $m) {
        $regs = queryOne("SELECT COUNT(*) as c FROM registrations WHERE modality_id = ?", [$m['id']]);
        $approved = queryOne("SELECT COUNT(*) as c FROM registrations WHERE modality_id = ? AND status = 'approved'", [$m['id']]);
        echo " - ID {$m['id']} [{$m['name']}]: {$regs['c']} total, {$approved['c']} approved.\n";
    }

    $allRegs = queryOne("SELECT COUNT(*) as c FROM registrations");
    echo "\nTOTAL REGISTRATIONS IN SYSTEM: " . $allRegs['c'] . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
