<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Ontvang JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Valideer en filter input
$id = isset($data['id']) ? (int)$data['id'] : 0;
$field = $data['field'] ?? '';
$value = $data['value'] ?? null;

$allowed_fields = ['status', 'notes', 'priority'];

if ($id <= 0 || !in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Ongeldige invoer']);
    exit;
}

// Bereid query voor
$query = \"UPDATE mep_dishes SET $field = :value WHERE id = :id LIMIT 1\";

try {
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':value', $value);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
