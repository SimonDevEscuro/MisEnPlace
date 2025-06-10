<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$id = (int)($_GET['id'] ?? 0);
$date = $_GET['date'] ?? date('Y-m-d');

if ($id > 0) {
    // Verwijder eerst alle gerechten uit deze categorie
    $conn->prepare("DELETE FROM mep_dishes WHERE category_id = ?")->execute([$id]);
    // Verwijder daarna de categorie zelf
    $conn->prepare("DELETE FROM mep_categories WHERE id = ?")->execute([$id]);
}

header("Location: view.php?date=" . urlencode($date));
exit;
?>
