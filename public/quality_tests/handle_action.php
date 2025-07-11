<?php
require_once '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $fuel_type_id = intval($_POST['fuel_type_id'] ?? 0);
    $branch_id = intval($_POST['branch_id'] ?? 0);
    if ($action === 'add_quality_test') {
        $tank_id = intval($_POST['tank_id']);
        $test_date = mysqli_real_escape_string($conn, $_POST['test_date']);
        $test_type = mysqli_real_escape_string($conn, $_POST['test_type']);
        $density = floatval($_POST['density']);
        $octane_rating = floatval($_POST['octane_rating']);
        $test_result = mysqli_real_escape_string($conn, $_POST['test_result']);
        $tested_by = mysqli_real_escape_string($conn, $_POST['tested_by']);
        $sql = "INSERT INTO fuel_quality_tests (branch_id, fuel_type_id, tank_id, test_date, test_type, density, octane_rating, test_result, tested_by) VALUES ($branch_id, $fuel_type_id, $tank_id, '$test_date', '$test_type', $density, $octane_rating, '$test_result', '$tested_by')";
        mysqli_query($conn, $sql);
    } elseif ($action === 'edit_quality_test') {
        $id = intval($_POST['quality_test_id']);
        $tank_id = intval($_POST['tank_id']);
        $test_date = mysqli_real_escape_string($conn, $_POST['test_date']);
        $test_type = mysqli_real_escape_string($conn, $_POST['test_type']);
        $density = floatval($_POST['density']);
        $octane_rating = floatval($_POST['octane_rating']);
        $test_result = mysqli_real_escape_string($conn, $_POST['test_result']);
        $tested_by = mysqli_real_escape_string($conn, $_POST['tested_by']);
        $sql = "UPDATE fuel_quality_tests SET tank_id=$tank_id, test_date='$test_date', test_type='$test_type', density=$density, octane_rating=$octane_rating, test_result='$test_result', tested_by='$tested_by' WHERE id=$id";
        mysqli_query($conn, $sql);
    } elseif ($action === 'delete_quality_test') {
        $id = intval($_POST['quality_test_id']);
        $sql = "UPDATE fuel_quality_tests SET deleted_at=NOW() WHERE id=$id";
        mysqli_query($conn, $sql);
    }
    header("Location: ../fuel_type_info.php?branch_id=$branch_id&fuel_type_id=$fuel_type_id");
    exit;
} 