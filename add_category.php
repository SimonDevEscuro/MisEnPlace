<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$name = trim($_POST['name'] ?? '');
if ($name === '') {
    header('Location: view.php');
    exit;
}

$stmt = $conn->prepare("INSERT INTO mep_categories (name, sort_order) VALUES (?, 999)");
$stmt->execute([$name]);

header('Location: view.php');
exit;
?>
