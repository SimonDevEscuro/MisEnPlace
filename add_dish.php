<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$name = trim($_POST['name'] ?? '');
$category_id = (int)($_POST['category_id'] ?? 0);
$date = $_POST['date'] ?? date('Y-m-d');

if ($name && $category_id > 0) {
    $stmt = $conn->prepare("INSERT INTO mep_dishes (category_id, name, date, status, priority) VALUES (?, ?, ?, 'from_scratch', 0)");
    $stmt->execute([$category_id, $name, $date]);
}

header("Location: view.php?date=" . urlencode($date));
exit;
?>
