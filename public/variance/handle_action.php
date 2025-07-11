<?php
require_once '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $fuel_type_id = intval($_POST['fuel_type_id'] ?? 0);
    $branch_id = intval($_POST['branch_id'] ?? 0);
    if ($action === 'add_variance') {
        $tank_id = intval($_POST['tank_id']);
        $variance_date = mysqli_real_escape_string($conn, $_POST['variance_date']);
        $expected_quantity = floatval($_POST['expected_quantity']);
        $actual_quantity = floatval($_POST['actual_quantity']);
        $variance_quantity = floatval($_POST['variance_quantity']);
        $variance_type = mysqli_real_escape_string($conn, $_POST['variance_type']);
        $variance_reason = mysqli_real_escape_string($conn, $_POST['variance_reason']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $sql = "INSERT INTO fuel_variances (branch_id, fuel_type_id, tank_id, variance_date, expected_quantity, actual_quantity, variance_quantity, variance_type, variance_reason, status) VALUES ($branch_id, $fuel_type_id, $tank_id, '$variance_date', $expected_quantity, $actual_quantity, $variance_quantity, '$variance_type', '$variance_reason', '$status')";
        mysqli_query($conn, $sql);
    } elseif ($action === 'edit_variance') {
        $id = intval($_POST['variance_id']);
        $tank_id = intval($_POST['tank_id']);
        $variance_date = mysqli_real_escape_string($conn, $_POST['variance_date']);
        $expected_quantity = floatval($_POST['expected_quantity']);
        $actual_quantity = floatval($_POST['actual_quantity']);
        $variance_quantity = floatval($_POST['variance_quantity']);
        $variance_type = mysqli_real_escape_string($conn, $_POST['variance_type']);
        $variance_reason = mysqli_real_escape_string($conn, $_POST['variance_reason']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $sql = "UPDATE fuel_variances SET tank_id=$tank_id, variance_date='$variance_date', expected_quantity=$expected_quantity, actual_quantity=$actual_quantity, variance_quantity=$variance_quantity, variance_type='$variance_type', variance_reason='$variance_reason', status='$status' WHERE id=$id";
        mysqli_query($conn, $sql);
    } elseif ($action === 'delete_variance') {
        $id = intval($_POST['variance_id']);
        $sql = "UPDATE fuel_variances SET deleted_at=NOW() WHERE id=$id";
        mysqli_query($conn, $sql);
    }
    header("Location: ../fuel_type_info.php?branch_id=$branch_id&fuel_type_id=$fuel_type_id");
    exit;
} 