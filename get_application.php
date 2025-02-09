<?php
require_once 'admin_auth.php';
require_once 'db_connect.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Invalid request');
}

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();

if (!$application) {
    http_response_code(404);
    exit('Application not found');
}

header('Content-Type: application/json');
// Add CORS headers if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

echo json_encode($application);
