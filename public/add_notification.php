<?php
session_start();
require_once '../config/db_connect.php';
require_once '../includes/notifications_helper.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}
// Validate required fields
$required = ['title', 'message', 'notification_type', 'branch_id'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing field: ' . $field]);
        exit;
    }
}
$data = [
    'user_id' => $_SESSION['user_id'],
    'branch_id' => $_POST['branch_id'],
    'notification_type' => $_POST['notification_type'],
    'title' => $_POST['title'],
    'message' => $_POST['message'],
    'action_url' => isset($_POST['action_url']) ? $_POST['action_url'] : ''
];
$success = add_notification($conn, $data);
echo json_encode(['success' => $success]); 