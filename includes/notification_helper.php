<?php
// Notification Helper - Modular notification logic
// Usage: include this file and use the functions below

require_once __DIR__ . '/../config/db_connect.php';

/**
 * Send notification to specific user(s)
 * @param mysqli $conn
 * @param array|int $to_user_ids
 * @param string $type
 * @param string $title
 * @param string $message
 * @param string|null $action_url
 * @param int|null $branch_id
 * @return bool
 */
function send_notification($conn, $to_user_ids, $type, $title, $message, $action_url = null, $branch_id = null) {
    if (!is_array($to_user_ids)) {
        $to_user_ids = [$to_user_ids];
    }
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, notification_type, title, message, action_url, branch_id, created_at, is_read) VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)");
    foreach ($to_user_ids as $user_id) {
        $uid = $user_id ? intval($user_id) : null;
        $b_id = $branch_id ? intval($branch_id) : null;
        $stmt->bind_param('issssi', $uid, $type, $title, $message, $action_url, $b_id);
        $stmt->execute();
    }
    $stmt->close();
    return true;
}

/**
 * Send notification to all users in a branch
 */
function send_notification_to_branch($conn, $branch_id, $type, $title, $message, $action_url = null) {
    $branch_id = intval($branch_id);
    $user_ids = [];
    $res = $conn->query("SELECT id FROM users WHERE branch_id = $branch_id AND deleted_at IS NULL");
    while ($row = $res->fetch_assoc()) {
        $user_ids[] = $row['id'];
    }
    if ($user_ids) {
        return send_notification($conn, $user_ids, $type, $title, $message, $action_url, $branch_id);
    }
    return false;
}

/**
 * Send notification to all users
 */
function send_notification_to_all($conn, $type, $title, $message, $action_url = null) {
    $user_ids = [];
    $res = $conn->query("SELECT id FROM users WHERE deleted_at IS NULL");
    while ($row = $res->fetch_assoc()) {
        $user_ids[] = $row['id'];
    }
    if ($user_ids) {
        return send_notification($conn, $user_ids, $type, $title, $message, $action_url, null);
    }
    return false;
}

/**
 * Fetch notifications (admin or user view)
 * @param mysqli $conn
 * @param array $filters (user_id, branch_id, type, date range, etc.)
 * @return array
 */
function fetch_notifications($conn, $filters = []) {
    $where = ["n.deleted_at IS NULL"];
    if (!empty($filters['user_id'])) {
        $where[] = "n.user_id = " . intval($filters['user_id']);
    }
    if (!empty($filters['branch_id'])) {
        $where[] = "n.branch_id = " . intval($filters['branch_id']);
    }
    if (!empty($filters['type'])) {
        $where[] = "n.notification_type = '" . $conn->real_escape_string($filters['type']) . "'";
    }
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $where[] = "DATE(n.created_at) BETWEEN '" . $conn->real_escape_string($filters['start_date']) . "' AND '" . $conn->real_escape_string($filters['end_date']) . "'";
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT n.*, u.first_name, u.last_name, b.branch_name FROM notifications n LEFT JOIN users u ON n.user_id = u.id LEFT JOIN branches b ON n.branch_id = b.id $where_sql ORDER BY n.created_at DESC, n.id DESC LIMIT 100";
    $res = $conn->query($sql);
    $notifications = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
    return $notifications;
}

/**
 * Mark notification as read
 */
function mark_notification_as_read($conn, $notification_id, $user_id) {
    $notification_id = intval($notification_id);
    $user_id = intval($user_id);
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = $notification_id AND user_id = $user_id";
    return $conn->query($sql);
} 