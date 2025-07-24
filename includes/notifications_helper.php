<?php
// Helper functions for notifications (procedural)
require_once __DIR__ . '/../config/db_connect.php';

function get_notifications($conn, $filters = [], $page = 1, $per_page = 20) {
    $where = ["n.deleted_at IS NULL"];
    if (!empty($filters['branch_id'])) $where[] = "n.branch_id = " . intval($filters['branch_id']);
    if (!empty($filters['notification_type'])) $where[] = "n.notification_type = '" . mysqli_real_escape_string($conn, $filters['notification_type']) . "'";
    if (!empty($filters['user_id'])) $where[] = "n.user_id = " . intval($filters['user_id']);
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $where[] = "DATE(n.created_at) BETWEEN '" . mysqli_real_escape_string($conn, $filters['start_date']) . "' AND '" . mysqli_real_escape_string($conn, $filters['end_date']) . "'";
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $offset = ($page - 1) * $per_page;
    $sql = "SELECT n.*, u.first_name, u.last_name, b.branch_name FROM notifications n LEFT JOIN users u ON n.user_id = u.id LEFT JOIN branches b ON n.branch_id = b.id $where_sql ORDER BY n.created_at DESC, n.id DESC LIMIT $per_page OFFSET $offset";
    $res = mysqli_query($conn, $sql);
    $notifications = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $notifications[] = $row;
        }
    }
    return $notifications;
}

function get_notifications_count($conn, $filters = []) {
    $where = ["deleted_at IS NULL"];
    if (!empty($filters['branch_id'])) $where[] = "branch_id = " . intval($filters['branch_id']);
    if (!empty($filters['notification_type'])) $where[] = "notification_type = '" . mysqli_real_escape_string($conn, $filters['notification_type']) . "'";
    if (!empty($filters['user_id'])) $where[] = "user_id = " . intval($filters['user_id']);
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $where[] = "DATE(created_at) BETWEEN '" . mysqli_real_escape_string($conn, $filters['start_date']) . "' AND '" . mysqli_real_escape_string($conn, $filters['end_date']) . "'";
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT COUNT(*) as total FROM notifications $where_sql";
    $res = mysqli_query($conn, $sql);
    $row = $res ? mysqli_fetch_assoc($res) : ['total' => 0];
    return intval($row['total']);
}

function get_notifications_summary($conn, $filters = []) {
    $summary = [
        'total' => 0,
        'by_type' => [],
        'by_status' => []
    ];
    $where = ["deleted_at IS NULL"];
    if (!empty($filters['branch_id'])) $where[] = "branch_id = " . intval($filters['branch_id']);
    if (!empty($filters['notification_type'])) $where[] = "notification_type = '" . mysqli_real_escape_string($conn, $filters['notification_type']) . "'";
    if (!empty($filters['user_id'])) $where[] = "user_id = " . intval($filters['user_id']);
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $where[] = "DATE(created_at) BETWEEN '" . mysqli_real_escape_string($conn, $filters['start_date']) . "' AND '" . mysqli_real_escape_string($conn, $filters['end_date']) . "'";
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    // Total
    $sql = "SELECT COUNT(*) as total FROM notifications $where_sql";
    $res = mysqli_query($conn, $sql);
    $row = $res ? mysqli_fetch_assoc($res) : ['total' => 0];
    $summary['total'] = intval($row['total']);
    // By type
    $sql = "SELECT notification_type, COUNT(*) as cnt FROM notifications $where_sql GROUP BY notification_type";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $summary['by_type'][$row['notification_type']] = intval($row['cnt']);
        }
    }
    // By status
    $sql = "SELECT is_read, COUNT(*) as cnt FROM notifications $where_sql GROUP BY is_read";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $status = $row['is_read'] ? 'Read' : 'Unread';
            $summary['by_status'][$status] = intval($row['cnt']);
        }
    }
    return $summary;
}

function mark_notification_as_read($conn, $notification_id, $user_id = null) {
    $id = intval($notification_id);
    $user_sql = $user_id ? " AND user_id = " . intval($user_id) : '';
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = $id $user_sql LIMIT 1";
    return mysqli_query($conn, $sql);
} 