PHPMailer Email Notification Implementation
=========================================

This guide explains how PHPMailer was integrated into the Fuel Station Management System for sending email notifications (e.g., when assigning leave to an employee).

---

Step 1: Download and Extract PHPMailer
--------------------------------------
1. Download PHPMailer from https://github.com/PHPMailer/PHPMailer
2. Extract the contents into the project at: `includes/PHPMailer-master/`
3. Ensure the following files exist:
   - `includes/PHPMailer-master/src/Exception.php`
   - `includes/PHPMailer-master/src/PHPMailer.php`
   - `includes/PHPMailer-master/src/SMTP.php`

---

Step 2: Create the Email Helper
-------------------------------
1. Create a file: `includes/email_helper.php`
2. Add the following code:

```
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';

function send_email($to, $subject, $body, $altBody = '') {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'atwongerevianney@gmail.com'; // Your Gmail address
        $mail->Password   = 'your_app_password'; // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('atwongerevianney@gmail.com', 'Fuel Station Management');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(false); // Set to true if sending HTML email
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email sending failed: ' . $mail->ErrorInfo);
        return false;
    }
}

// Optional: Test function
function test_email_config() {
    $test_email = 'atwongerevianney@gmail.com';
    $result = send_email($test_email, 'Test Email', 'This is a test email from your application.');
    return $result;
}
?>
```

---

Step 3: Configure Gmail SMTP
----------------------------
- Use your Gmail address and an App Password (not your regular password).
- To generate an App Password:
  1. Enable 2-Step Verification on your Google account.
  2. Go to Google Account > Security > App Passwords.
  3. Generate a password for 'Mail' and 'Other' (give it a name like 'PHPMailer').
  4. Use the generated password in `$mail->Password`.

---

Step 4: Use the Email Helper in Your Application
------------------------------------------------
1. In your PHP file (e.g., `public/shift_assignments.php`), include the helper:
   ```php
   require_once '../includes/email_helper.php';
   ```
2. After assigning leave, send the email:
   ```php
   $employee_email = $row['email']; // Make sure this is fetched from your DB
   $subject = 'Leave Assigned';
   $body = "Dear {$row['name']},\n\nYou have been assigned a leave on {$leave_date}.\n\nRegards,\nFuel Station Management";
   send_email($employee_email, $subject, $body);
   ```

---

Step 5: Error Logging
---------------------
- If sending fails, the error is logged to your PHP error log for troubleshooting.

---

Step 6: Testing
---------------
- Use the `test_email_config()` function in `email_helper.php` to send a test email and verify your setup.

---

Troubleshooting
---------------
- Ensure your SMTP credentials are correct.
- Check your spam folder for test emails.
- Review your PHP error log for any issues.
- Make sure your server/network allows outbound SMTP connections.

---

This completes the PHPMailer email notification setup for your project. 