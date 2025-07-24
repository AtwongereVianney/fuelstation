<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/email_helper.php';

/**
 * Send notifications to users (in-app and email)
 * @param string $type Notification type (info, warning, error, success)
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $action_url Optional action URL
 * @param string $recipient_type 'all', 'users', 'branches', or 'combination'
 * @param array $user_ids Array of user IDs (if applicable)
 * @param array $branch_ids Array of branch IDs (if applicable)
 * @return array [success => bool, message => string]
 */
function send_notification($type, $title, $message, $action_url = '', $recipient_type = 'all', $user_ids = [], $branch_ids = []) {
    global $conn;
    $type = in_array($type, ['info','warning','error','success']) ? $type : 'info';
    $title = trim($title);
    $message = trim($message);
    $action_url = trim($action_url);
    if (!$title || !$message) {
        return ['success' => false, 'message' => 'Title and message are required.'];
    }
    $recipients = [];
    // Determine recipients
    if ($recipient_type === 'all') {
        $sql = "SELECT id, email FROM users WHERE deleted_at IS NULL AND status = 'active'";
        $res = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($res)) $recipients[$row['id']] = $row['email'];
    } else {
        if (($recipient_type === 'users' || $recipient_type === 'combination') && !empty($user_ids)) {
            $ids = array_map('intval', $user_ids);
            if ($ids) {
                $sql = "SELECT id, email FROM users WHERE id IN (" . implode(',', $ids) . ") AND deleted_at IS NULL AND status = 'active'";
                $res = mysqli_query($conn, $sql);
                while ($row = mysqli_fetch_assoc($res)) $recipients[$row['id']] = $row['email'];
            }
        }
        if (($recipient_type === 'branches' || $recipient_type === 'combination') && !empty($branch_ids)) {
            $bids = array_map('intval', $branch_ids);
            if ($bids) {
                $sql = "SELECT id, email FROM users WHERE branch_id IN (" . implode(',', $bids) . ") AND deleted_at IS NULL AND status = 'active'";
                $res = mysqli_query($conn, $sql);
                while ($row = mysqli_fetch_assoc($res)) $recipients[$row['id']] = $row['email'];
            }
        }
    }
    if (!$recipients) {
        return ['success' => false, 'message' => 'No recipients found.'];
    }
    $success_count = 0;
    $fail_count = 0;
    foreach ($recipients as $user_id => $email) {
        $stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, notification_type, title, message, action_url) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issss', $user_id, $type, $title, $message, $action_url);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        // Send email
        if ($ok && $email) {
            $subject = $title;
            $body = $message . ($action_url ? ("\n\nAction: " . $action_url) : '');
            send_email($email, $subject, $body);
        }
        if ($ok) $success_count++; else $fail_count++;
    }
    if ($success_count) {
        return ['success' => true, 'message' => "Notification sent to $success_count user(s)." . ($fail_count ? " $fail_count failed." : '')];
    } else {
        return ['success' => false, 'message' => 'Failed to send notifications.'];
    }
}

/**
 * Fetch notifications for display (admin view, with filters)
 * @param array $filters (branch_id, type, date range, etc.)
 * @return array
 */
function fetch_notifications($filters = []) {
    global $conn;
    $where = ["n.deleted_at IS NULL"];
    if (!empty($filters['branch_id'])) $where[] = "n.branch_id = " . intval($filters['branch_id']);
    if (!empty($filters['type'])) $where[] = "n.notification_type = '" . mysqli_real_escape_string($conn, $filters['type']) . "'";
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $where[] = "DATE(n.created_at) BETWEEN '" . mysqli_real_escape_string($conn, $filters['start_date']) . "' AND '" . mysqli_real_escape_string($conn, $filters['end_date']) . "'";
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    $sql = "SELECT n.*, u.first_name, u.last_name, b.branch_name FROM notifications n LEFT JOIN users u ON n.user_id = u.id LEFT JOIN branches b ON n.branch_id = b.id $where_sql ORDER BY n.created_at DESC, n.id DESC LIMIT 100";
    $res = mysqli_query($conn, $sql);
    $notifications = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $notifications[] = $row;
        }
    }
    return $notifications;
} 