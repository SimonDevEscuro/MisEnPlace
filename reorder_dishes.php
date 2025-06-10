<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$category_id = (int)($_POST['category_id'] ?? 0);
$order = $_POST['order'] ?? [];

if ($category_id > 0 && is_array($order)) {
    foreach ($order as $index => $id) {
        $stmt = $conn->prepare("UPDATE mep_dishes SET sort_order = ? WHERE id = ? AND category_id = ?");
        $stmt->execute([$index + 1, $id, $category_id]);
    }
}
?>
