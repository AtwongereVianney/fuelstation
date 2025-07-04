<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

if (isset($_GET['business_id']) && is_numeric($_GET['business_id'])) {
    $business_id = intval($_GET['business_id']);
    
    $sql = "SELECT id, branch_name FROM branches WHERE business_id = ? AND deleted_at IS NULL ORDER BY branch_name";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $business_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $branches = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $branches[] = $row;
    }
    
    echo json_encode(['success' => true, 'branches' => $branches]);
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid business ID']);
}
?>