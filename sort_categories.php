<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$id = (int)($_GET['id'] ?? 0);
$dir = $_GET['dir'] ?? 'up';

if ($id > 0) {
    $delta = ($dir === 'down') ? 1 : -1;

    $stmt = $conn->prepare("UPDATE mep_categories SET sort_order = sort_order + ? WHERE id = ?");
    $stmt->execute([$delta, $id]);
}

$date = $_GET['date'] ?? date('Y-m-d');
header("Location: view.php?date=" . urlencode($date));
exit;
?>
