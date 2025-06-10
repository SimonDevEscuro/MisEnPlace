<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$order = $_POST['order'] ?? [];

if (is_array($order)) {
    foreach ($order as $index => $id) {
        $stmt = $conn->prepare("UPDATE mep_categories SET sort_order = ? WHERE id = ?");
        $stmt->execute([$index + 1, $id]);
    }
}
?>
