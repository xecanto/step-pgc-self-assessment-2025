<?php
require_once 'admin_auth.php';
require_once 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    http_response_code(400);
    exit('Invalid request');
}

$sql = "UPDATE applications SET 
        status = ?,
        program = ?,
        full_name = ?,
        father_name = ?,
        mobile = ?,
        email = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssi",
    $data['status'],
    $data['program'],
    $data['full_name'],
    $data['father_name'],
    $data['mobile'],
    $data['email'],
    $data['id']
);

if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update application']);
}
