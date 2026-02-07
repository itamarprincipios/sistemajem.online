<?php
$url = 'https://sistemajem.online/api/matches-api.php?action=list&event_id=1,3';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if (isset($data['data'])) {
    $counts = [];
    foreach ($data['data'] as $m) {
        $eid = $m['competition_event_id'];
        $mid = $m['modality_id'];
        $mname = $m['modality_name'];
        $key = "Event $eid - Mod $mid ($mname)";
        if (!isset($counts[$key])) $counts[$key] = 0;
        $counts[$key]++;
    }
    print_r($counts);
} else {
    echo "NO DATA FOUND\n";
    echo $response;
}
