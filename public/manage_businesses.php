<?php
session_start();
require_once '../config/db_connect.php';

// Only allow super admins
if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'super_admin') {
    header('Location: dashboard.php');
    exit;
}

// Handle Add Business
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_business'])) {
    $business_name = trim($_POST['business_name'] ?? '');
    $business_type = trim($_POST['business_type'] ?? 'individual');
    $registration_number = trim($_POST['registration_number'] ?? '');
    $tin_number = trim($_POST['tin_number'] ?? '');
    $vat_number = trim($_POST['vat_number'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? 'Uganda');
    $status = trim($_POST['status'] ?? 'active');
    $established_date = trim($_POST['established_date'] ?? null);
    if ($business_name && $business_type) {
        $stmt = mysqli_prepare($conn, "INSERT INTO businesses (business_name, business_type, registration_number, tin_number, vat_number, license_number, phone, email, website, address, city, district, region, postal_code, country, status, established_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssssssssssssssss', $business_name, $business_type, $registration_number, $tin_number, $vat_number, $license_number, $phone, $email, $website, $address, $city, $district, $region, $postal_code, $country, $status, $established_date);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['success_message'] = 'Business added successfully!';
        header('Location: manage_businesses.php');
        exit;
    }
}

// Handle Add Branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_branch'])) {
    $business_id = intval($_POST['business_id'] ?? 0);
    $branch_code = trim($_POST['branch_code'] ?? '');
    $branch_name = trim($_POST['branch_name'] ?? '');
    $branch_type = trim($_POST['branch_type'] ?? 'sub_branch');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $gps_coordinates = trim($_POST['gps_coordinates'] ?? '');
    $operational_hours = trim($_POST['operational_hours'] ?? '');
    $manager_name = trim($_POST['manager_name'] ?? '');
    $manager_phone = trim($_POST['manager_phone'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $opening_date = trim($_POST['opening_date'] ?? null);
    if ($business_id && $branch_code && $branch_name) {
        $stmt = mysqli_prepare($conn, "INSERT INTO branches (business_id, branch_code, branch_name, branch_type, phone, email, address, city, district, region, postal_code, gps_coordinates, operational_hours, manager_name, manager_phone, status, opening_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issssssssssssssss', $business_id, $branch_code, $branch_name, $branch_type, $phone, $email, $address, $city, $district, $region, $postal_code, $gps_coordinates, $operational_hours, $manager_name, $manager_phone, $status, $opening_date);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['success_message'] = 'Branch added successfully!';
        header('Location: manage_businesses.php');
        exit;
    }
}

// Fetch all businesses
$businesses = [];
$res = mysqli_query($conn, "SELECT * FROM businesses WHERE deleted_at IS NULL ORDER BY created_at DESC");
while ($row = mysqli_fetch_assoc($res)) {
    $businesses[] = $row;
}
// Fetch branches for each business
$branches_by_business = [];
$res = mysqli_query($conn, "SELECT * FROM branches WHERE deleted_at IS NULL ORDER BY business_id, branch_name");
while ($row = mysqli_fetch_assoc($res)) {
    $branches_by_business[$row['business_id']][] = $row;
}
function h($str) { return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Businesses & Branches</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light mt-5">
<?php include '../includes/header.php'; ?>
<div class="container py-4">
    <h2 class="mb-4">Manage Businesses & Branches</h2>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo h($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Add New Business</div>
        <div class="card-body">
            <form method="post">
                <div class="row g-2">
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Business Name</label>
                        <input type="text" name="business_name" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Business Type</label>
                        <select name="business_type" class="form-select" required>
                            <option value="individual">Individual</option>
                            <option value="partnership">Partnership</option>
                            <option value="limited_company">Limited Company</option>
                            <option value="corporation">Corporation</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Registration Number</label>
                        <input type="text" name="registration_number" class="form-control">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">TIN Number</label>
                        <input type="text" name="tin_number" class="form-control">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">VAT Number</label>
                        <input type="text" name="vat_number" class="form-control">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">License Number</label>
                        <input type="text" name="license_number" class="form-control">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Website</label>
                        <input type="text" name="website" class="form-control">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">District</label>
                        <input type="text" name="district" class="form-control">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Region</label>
                        <input type="text" name="region" class="form-control">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Postal Code</label>
                        <input type="text" name="postal_code" class="form-control">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-control" value="Uganda">
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Established Date</label>
                        <input type="date" name="established_date" class="form-control">
                    </div>
                </div>
                <button type="submit" name="add_business" class="btn btn-success mt-3">Add Business</button>
            </form>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">Businesses & Branches</div>
        <div class="card-body">
            <?php foreach ($businesses as $biz): ?>
                <div class="mb-4 border rounded p-3">
                    <h5 class="mb-2">Business: <?php echo h($biz['business_name']); ?> <span class="badge bg-info ms-2"><?php echo h($biz['business_type']); ?></span></h5>
                    <div class="mb-2 small text-muted">Reg #: <?php echo h($biz['registration_number']); ?> | TIN: <?php echo h($biz['tin_number']); ?> | Status: <?php echo h($biz['status']); ?></div>
                    <div class="mb-2">Address: <?php echo h($biz['address']); ?>, <?php echo h($biz['city']); ?>, <?php echo h($biz['district']); ?>, <?php echo h($biz['country']); ?></div>
                    <div class="mb-2">Phone: <?php echo h($biz['phone']); ?> | Email: <?php echo h($biz['email']); ?></div>
                    <div class="mb-2">Established: <?php echo h($biz['established_date']); ?></div>
                    <div class="mb-2">
                        <strong>Branches:</strong>
                        <ul class="list-group mb-2">
                            <?php if (!empty($branches_by_business[$biz['id']])): ?>
                                <?php foreach ($branches_by_business[$biz['id']] as $branch): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo h($branch['branch_name']); ?> (<?php echo h($branch['branch_code']); ?>) - <?php echo h($branch['status']); ?>
                                        <span class="small text-muted">Manager: <?php echo h($branch['manager_name']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item">No branches yet.</li>
                            <?php endif; ?>
                        </ul>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#addBranchForm<?php echo $biz['id']; ?>">Add Branch</button>
                        <div class="collapse mt-2" id="addBranchForm<?php echo $biz['id']; ?>">
                            <form method="post">
                                <input type="hidden" name="business_id" value="<?php echo $biz['id']; ?>">
                                <div class="row g-2">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Branch Code</label>
                                        <input type="text" name="branch_code" class="form-control" required>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Branch Name</label>
                                        <input type="text" name="branch_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Branch Type</label>
                                        <select name="branch_type" class="form-select">
                                            <option value="main">Main</option>
                                            <option value="sub_branch">Sub Branch</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Address</label>
                                        <input type="text" name="address" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">City</label>
                                        <input type="text" name="city" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">District</label>
                                        <input type="text" name="district" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Region</label>
                                        <input type="text" name="region" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Postal Code</label>
                                        <input type="text" name="postal_code" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">GPS Coordinates</label>
                                        <input type="text" name="gps_coordinates" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Operational Hours</label>
                                        <input type="text" name="operational_hours" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Manager Name</label>
                                        <input type="text" name="manager_name" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Manager Phone</label>
                                        <input type="text" name="manager_phone" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="under_maintenance">Under Maintenance</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label">Opening Date</label>
                                        <input type="date" name="opening_date" class="form-control">
                                    </div>
                                </div>
                                <button type="submit" name="add_branch" class="btn btn-success mt-2">Add Branch</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 