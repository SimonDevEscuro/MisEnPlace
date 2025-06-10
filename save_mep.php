<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (!isset($_POST['data'], $_POST['date'])) {
    http_response_code(400);
    echo 'Invalid request.';
    exit;
}

$date = $_POST['date'];
$data = json_decode($_POST['data'], true);

// Verwijder bestaande entries voor deze datum
$stmt = $conn->prepare("DELETE FROM mep_saved_lists WHERE date = ?");
$stmt->execute([$date]);

// Nieuwe data opslaan
$stmt = $conn->prepare("INSERT INTO mep_saved_lists (dish_id, name, status, notes, priority, date) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($data as $item) {
    $stmt->execute([
        $item['id'],
        $item['name'],
        $item['status'],
        $item['notes'],
        $item['priority'],
        $date
    ]);
}

echo 'MEP saved successfully.';
