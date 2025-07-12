<?php
require_once '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $fuel_type_id = intval($_POST['fuel_type_id'] ?? 0);
    $branch_id = intval($_POST['branch_id'] ?? 0);
    if ($action === 'add_price') {
        $old_price = floatval($_POST['old_price']);
        $new_price = floatval($_POST['new_price']);
        $effective_date = mysqli_real_escape_string($conn, $_POST['effective_date']);
        $changed_by = mysqli_real_escape_string($conn, $_POST['changed_by']);
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);
        $sql = "INSERT INTO fuel_price_history (branch_id, fuel_type_id, old_price, new_price, effective_date, changed_by, reason) VALUES ($branch_id, $fuel_type_id, $old_price, $new_price, '$effective_date', '$changed_by', '$reason')";
        mysqli_query($conn, $sql);
    } elseif ($action === 'edit_price') {
        $id = intval($_POST['price_id']);
        $old_price = floatval($_POST['old_price']);
        $new_price = floatval($_POST['new_price']);
        $effective_date = mysqli_real_escape_string($conn, $_POST['effective_date']);
        $changed_by = mysqli_real_escape_string($conn, $_POST['changed_by']);
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);
        $sql = "UPDATE fuel_price_history SET old_price=$old_price, new_price=$new_price, effective_date='$effective_date', changed_by='$changed_by', reason='$reason' WHERE id=$id";
        mysqli_query($conn, $sql);
    } elseif ($action === 'delete_price') {
        $id = intval($_POST['price_id']);
        $sql = "UPDATE fuel_price_history SET deleted_at=NOW() WHERE id=$id";
        mysqli_query($conn, $sql);
    }
    header("Location: ../fuel_type_info.php?branch_id=$branch_id&fuel_type_id=$fuel_type_id&active_tab=price");
    exit;
} 