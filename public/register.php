<?php
session_start();
require_once '../config/db_connect.php';

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_employee'])) {
    // Get form data
    $business_name_input = trim($_POST['business_name'] ?? '');
    $branch_name_input = trim($_POST['branch_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $national_id = trim($_POST['national_id'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_phone = trim($_POST['emergency_contact_phone'] ?? '');
    $hired_date = $_POST['hired_date'] ?? '';

    // Debug: Check if form data is being received
    echo "<!-- DEBUG: Form submitted with business_name: $business_name_input, branch_name: $branch_name_input -->";

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || 
        empty($first_name) || empty($last_name) || empty($business_name_input) || empty($branch_name_input)) {
        $errors[] = 'All required fields must be filled.';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    // Check database connection
    if (!$conn) {
        $errors[] = 'Database connection failed: ' . mysqli_connect_error();
    }

    // Check for existing username/email
    if (empty($errors)) {
        $sql = "SELECT id FROM users WHERE username=? OR email=? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            $errors[] = 'Database prepare failed: ' . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, 'ss', $username, $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors[] = 'Username or email already exists.';
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Generate auto-increment Employee ID
    if (empty($errors)) {
        $sql = "SELECT employee_id FROM users WHERE employee_id LIKE 'EMP%' ORDER BY employee_id DESC LIMIT 1";
        $result = mysqli_query($conn, $sql);
        
        if (!$result) {
            $errors[] = 'Failed to generate employee ID: ' . mysqli_error($conn);
        } else {
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $last_id = $row['employee_id'];
                $number = intval(substr($last_id, 3)) + 1;
                $employee_id = 'EMP' . str_pad($number, 4, '0', STR_PAD_LEFT);
            } else {
                $employee_id = 'EMP0001';
            }
        }
    }

    // Look up business_id
    $business_id = 0;
    if (!empty($business_name_input)) {
        $sql = "SELECT id FROM businesses WHERE LOWER(business_name) = LOWER(?) AND deleted_at IS NULL LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $business_name_input);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            if ($row) {
                $business_id = $row['id'];
            } else {
                $errors[] = 'Business not found. Please enter a valid business name.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = 'Database error (business lookup): ' . mysqli_error($conn);
        }
    } else {
        $errors[] = 'Business name is required.';
    }

    // Look up branch_id
    $branch_id = 0;
    if (!empty($branch_name_input) && $business_id) {
        $sql = "SELECT id FROM branches WHERE LOWER(branch_name) = LOWER(?) AND business_id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $branch_name_input, $business_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            if ($row) {
                $branch_id = $row['id'];
            } else {
                $errors[] = 'Branch not found for the selected business. Please enter a valid branch name.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = 'Database error (branch lookup): ' . mysqli_error($conn);
        }
    } else if (empty($branch_name_input)) {
        $errors[] = 'Branch name is required.';
    }

    // Insert user if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $status = 'active';
        
        // Handle empty dates - convert to NULL
        $date_of_birth = !empty($date_of_birth) ? $date_of_birth : null;
        $hired_date = !empty($hired_date) ? $hired_date : null;
        $gender = !empty($gender) ? $gender : null;
        
        $sql = "INSERT INTO users (business_id, branch_id, employee_id, username, email, phone, password, 
                first_name, last_name, middle_name, date_of_birth, gender, national_id, address, city, 
                district, emergency_contact_name, emergency_contact_phone, hired_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            $errors[] = 'Database prepare failed: ' . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, 'iissssssssssssssssss', 
                $business_id, $branch_id, $employee_id, $username, $email, $phone, $hashed_password,
                $first_name, $last_name, $middle_name, $date_of_birth, $gender, $national_id, $address, 
                $city, $district, $emergency_contact_name, $emergency_contact_phone, $hired_date, $status);
            
            if (mysqli_stmt_execute($stmt)) {
                $user_id = mysqli_insert_id($conn);
                
                // Check if user_roles table exists and role_id 5 exists
                $check_role_sql = "SELECT id FROM roles WHERE id = 5 LIMIT 1";
                $check_role_result = mysqli_query($conn, $check_role_sql);
                
                if ($check_role_result && mysqli_num_rows($check_role_result) > 0) {
                    // Assign default role (fuel_attendant, id=5)
                    $role_id = 5;
                    $assigned_by = $user_id;
                    $sql_role = "INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (?, ?, ?)";
                    $stmt_role = mysqli_prepare($conn, $sql_role);
                    
                    if ($stmt_role) {
                        mysqli_stmt_bind_param($stmt_role, 'iii', $user_id, $role_id, $assigned_by);
                        if (!mysqli_stmt_execute($stmt_role)) {
                            $errors[] = 'Failed to assign role: ' . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt_role);
                    }
                }
                
                if (empty($errors)) {
                    $success = 'Registration successful! Employee ID: ' . $employee_id . '. You can now <a href="login.php">login</a>.';
                }
            } else {
                $errors[] = 'Registration failed: ' . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch businesses for dropdown
$businesses_sql = "SELECT id, business_name FROM businesses WHERE deleted_at IS NULL ORDER BY business_name";
$businesses_result = mysqli_query($conn, $businesses_sql);

if (!$businesses_result) {
    $errors[] = 'Failed to load businesses: ' . mysqli_error($conn);
}

// Fetch branches for dropdown (will be filtered by JavaScript)
$branches_sql = "SELECT id, branch_name, business_id FROM branches WHERE deleted_at IS NULL ORDER BY branch_name";
$branches_result = mysqli_query($conn, $branches_sql);

if (!$branches_result) {
    $errors[] = 'Failed to load branches: ' . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Uganda Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Employee Registration</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error) echo '<div>' . htmlspecialchars($error) . '</div>'; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <!-- Business and Branch Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Business Information</h5>
                            </div>
                            <div class="col-md-6">
                                <label for="business_name" class="form-label">Business Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="business_name" name="business_name" value="<?php echo htmlspecialchars($_POST['business_name'] ?? ''); ?>" required>
                                <div class="form-text">Enter the exact business name as registered in the system.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="branch_name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="branch_name" name="branch_name" value="<?php echo htmlspecialchars($_POST['branch_name'] ?? ''); ?>" required>
                                <div class="form-text">Enter the exact branch name as registered in the system.</div>
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Personal Information</h5>
                            </div>
                            <div class="col-md-4">
                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name" 
                                       value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="national_id" class="form-label">National ID</label>
                                <input type="text" class="form-control" id="national_id" name="national_id" 
                                       value="<?php echo htmlspecialchars($_POST['national_id'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="hired_date" class="form-label">Hired Date</label>
                                <input type="date" class="form-control" id="hired_date" name="hired_date" 
                                       value="<?php echo htmlspecialchars($_POST['hired_date'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Contact Information</h5>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="district" class="form-label">District</label>
                                <input type="text" class="form-control" id="district" name="district" 
                                       value="<?php echo htmlspecialchars($_POST['district'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Emergency Contact -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Emergency Contact</h5>
                            </div>
                            <div class="col-md-6">
                                <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
                                       value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                                <input type="text" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" 
                                       value="<?php echo htmlspecialchars($_POST['emergency_contact_phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Account Information</h5>
                            </div>
                            <div class="col-md-4">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-4">
                                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="register_employee" class="btn btn-primary btn-lg">Register Employee</button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        Already have an account? <a href="login.php">Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>