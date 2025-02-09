<?php
require_once 'admin_auth.php';
require_once 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    http_response_code(400);
    exit('Invalid request');
}

$stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
$stmt->bind_param("i", $data['id']);

if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete application']);
}
