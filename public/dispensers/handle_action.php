<?php
require_once '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $fuel_type_id = intval($_POST['fuel_type_id'] ?? 0);
    $branch_id = intval($_POST['branch_id'] ?? 0);
    if ($action === 'add_dispenser') {
        $tank_id = intval($_POST['tank_id']);
        $dispenser_number = mysqli_real_escape_string($conn, $_POST['dispenser_number']);
        $pump_price = floatval($_POST['pump_price']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $sql = "INSERT INTO fuel_dispensers (branch_id, tank_id, dispenser_number, pump_price, status) VALUES ($branch_id, $tank_id, '$dispenser_number', $pump_price, '$status')";
        mysqli_query($conn, $sql);
    } elseif ($action === 'edit_dispenser') {
        $id = intval($_POST['dispenser_id']);
        $tank_id = intval($_POST['tank_id']);
        $dispenser_number = mysqli_real_escape_string($conn, $_POST['dispenser_number']);
        $pump_price = floatval($_POST['pump_price']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $sql = "UPDATE fuel_dispensers SET tank_id=$tank_id, dispenser_number='$dispenser_number', pump_price=$pump_price, status='$status' WHERE id=$id";
        mysqli_query($conn, $sql);
    } elseif ($action === 'delete_dispenser') {
        $id = intval($_POST['dispenser_id']);
        $sql = "UPDATE fuel_dispensers SET deleted_at=NOW() WHERE id=$id";
        mysqli_query($conn, $sql);
    }
    header("Location: ../fuel_type_info.php?branch_id=$branch_id&fuel_type_id=$fuel_type_id");
    exit;
} 