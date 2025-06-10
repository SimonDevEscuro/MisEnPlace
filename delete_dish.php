<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$id = (int)($_GET['id'] ?? 0);
$date = $_GET['date'] ?? date('Y-m-d');

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM mep_dishes WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: view.php?date=" . urlencode($date));
exit;
?>
