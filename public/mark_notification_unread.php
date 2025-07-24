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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['notification_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}
$notification_id = intval($_POST['notification_id']);
$user_id = $_SESSION['user_id'];
$success = mark_notification_as_unread($conn, $notification_id, $user_id);
echo json_encode(['success' => $success]);