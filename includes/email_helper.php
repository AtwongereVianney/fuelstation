<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';

function send_email($to, $subject, $body, $altBody = '') {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'atwongerevianney@gmail.com'; // SMTP username
        $mail->Password   = 'thud zljq tbqe lrkk';    // Make sure this App Password is still valid
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients - FIXED: Use the same email as username
        $mail->setFrom('atwongerevianney@gmail.com', 'Fuel Station Management');
        $mail->addAddress($to);

        //Content
        $mail->isHTML(false); // Changed to false since you're sending plain text
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the actual error for debugging
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Optional: Add a function to test email configuration
function test_email_config() {
    $test_email = "atwongerevianney@gmail.com"; // Replace with your test email
    $result = send_email($test_email, "Test Email", "This is a test email from your application.");
    return $result;
}
?>