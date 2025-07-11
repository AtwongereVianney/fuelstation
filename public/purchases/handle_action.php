<?php
require_once '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $fuel_type_id = intval($_POST['fuel_type_id'] ?? 0);
    $branch_id = intval($_POST['branch_id'] ?? 0);
    if ($action === 'add_purchase') {
        $supplier_id = intval($_POST['supplier_id']);
        $delivery_date = mysqli_real_escape_string($conn, $_POST['delivery_date']);
        $quantity_delivered = floatval($_POST['quantity_delivered']);
        $unit_cost = floatval($_POST['unit_cost']);
        $total_cost = floatval($_POST['total_cost']);
        $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
        $sql = "INSERT INTO fuel_purchases (branch_id, fuel_type_id, supplier_id, delivery_date, quantity_delivered, unit_cost, total_cost, payment_status) VALUES ($branch_id, $fuel_type_id, $supplier_id, '$delivery_date', $quantity_delivered, $unit_cost, $total_cost, '$payment_status')";
        mysqli_query($conn, $sql);
    } elseif ($action === 'edit_purchase') {
        $id = intval($_POST['purchase_id']);
        $supplier_id = intval($_POST['supplier_id']);
        $delivery_date = mysqli_real_escape_string($conn, $_POST['delivery_date']);
        $quantity_delivered = floatval($_POST['quantity_delivered']);
        $unit_cost = floatval($_POST['unit_cost']);
        $total_cost = floatval($_POST['total_cost']);
        $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status']);
        $sql = "UPDATE fuel_purchases SET supplier_id=$supplier_id, delivery_date='$delivery_date', quantity_delivered=$quantity_delivered, unit_cost=$unit_cost, total_cost=$total_cost, payment_status='$payment_status' WHERE id=$id";
        mysqli_query($conn, $sql);
    } elseif ($action === 'delete_purchase') {
        $id = intval($_POST['purchase_id']);
        $sql = "UPDATE fuel_purchases SET deleted_at=NOW() WHERE id=$id";
        mysqli_query($conn, $sql);
    }
    header("Location: ../fuel_type_info.php?branch_id=$branch_id&fuel_type_id=$fuel_type_id");
    exit;
} 