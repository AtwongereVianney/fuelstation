<?php
require_once '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $fuel_type_id = intval($_POST['fuel_type_id'] ?? 0);
    $branch_id = intval($_POST['branch_id'] ?? 0);
    if ($action === 'add_sale') {
        $dispenser_id = intval($_POST['dispenser_id']);
        $transaction_date = mysqli_real_escape_string($conn, $_POST['transaction_date']);
        $transaction_time = mysqli_real_escape_string($conn, $_POST['transaction_time']);
        $quantity = floatval($_POST['quantity']);
        $unit_price = floatval($_POST['unit_price']);
        $final_amount = floatval($_POST['final_amount']);
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $attendant_id = intval($_POST['attendant_id']);
        $transaction_number = uniqid('TXN-');
        $sql = "INSERT INTO sales_transactions (branch_id, dispenser_id, fuel_type_id, transaction_date, transaction_time, quantity, unit_price, final_amount, payment_method, attendant_id, transaction_number) VALUES ($branch_id, $dispenser_id, $fuel_type_id, '$transaction_date', '$transaction_time', $quantity, $unit_price, $final_amount, '$payment_method', $attendant_id, '$transaction_number')";
        mysqli_query($conn, $sql);
    } elseif ($action === 'edit_sale') {
        $id = intval($_POST['sale_id']);
        $dispenser_id = intval($_POST['dispenser_id']);
        $transaction_time = mysqli_real_escape_string($conn, $_POST['transaction_time']);
        $quantity = floatval($_POST['quantity']);
        $unit_price = floatval($_POST['unit_price']);
        $final_amount = floatval($_POST['final_amount']);
        $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
        $attendant_id = intval($_POST['attendant_id']);
        $sql = "UPDATE sales_transactions SET dispenser_id=$dispenser_id, transaction_time='$transaction_time', quantity=$quantity, unit_price=$unit_price, final_amount=$final_amount, payment_method='$payment_method', attendant_id=$attendant_id WHERE id=$id";
        mysqli_query($conn, $sql);
    } elseif ($action === 'delete_sale') {
        $id = intval($_POST['sale_id']);
        $sql = "UPDATE sales_transactions SET deleted_at=NOW() WHERE id=$id";
        mysqli_query($conn, $sql);
    }
    header("Location: ../fuel_type_info.php?branch_id=$branch_id&fuel_type_id=$fuel_type_id");
    exit;
} 