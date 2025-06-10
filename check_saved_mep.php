<?php
require_once 'db.php';

$date = $_GET['date'] ?? '';
$response = ['exists' => false];

if ($date) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM saved_mep WHERE date = ?");
    $stmt->execute([$date]);
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        $response['exists'] = true;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
