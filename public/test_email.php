<?php
require_once '../includes/email_helper.php';

// Test email configuration
if (isset($_POST['test_email'])) {
    $test_email = $_POST['test_email'];
    $result = test_email_config();
    
    if ($result) {
        $message = "Email test successful! Check your inbox.";
        $alert_class = "alert-success";
    } else {
        $message = "Email test failed. Check error logs.";
        $alert_class = "alert-danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - Fuel Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Email Configuration Test</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($message)): ?>
                            <div class="alert <?php echo $alert_class; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="test_email" class="form-label">Test Email Address</label>
                                <input type="email" class="form-control" id="test_email" name="test_email" 
                                       value="atwongerevianney@gmail.com" required>
                                <div class="form-text">Enter an email address to test the configuration.</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Test Email</button>
                            <a href="manage_users.php" class="btn btn-secondary">Back to Manage Users</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 