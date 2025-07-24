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

$filters = [
    'branch_id' => isset($_GET['branch_id']) ? $_GET['branch_id'] : '',
    'notification_type' => isset($_GET['notification_type']) ? $_GET['notification_type'] : '',
    'user_id' => '', // Optionally filter by user
    'start_date' => isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'),
    'end_date' => isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'),
];
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 20;

$notifications = get_notifications($conn, $filters, $page, $per_page);
$total = get_notifications_count($conn, $filters);
$summary = get_notifications_summary($conn, $filters);

$response = [
    'notifications' => $notifications,
    'summary' => $summary,
    'pagination' => [
        'page' => $page,
        'per_page' => $per_page,
        'total' => $total,
        'total_pages' => ceil($total / $per_page)
    ]
];
echo json_encode($response); 