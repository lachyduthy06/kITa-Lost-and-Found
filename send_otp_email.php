 <?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure PHPMailer is included correctly
require 'vendor/autoload.php'; 

// SMTP server configuration
$host = 'smtp.gmail.com'; // SMTP server for Gmail
$username = 'kitak2450@gmail.com'; // Your Gmail address
$password = 'kitapasswordkitakitapassword'; // Use an app-specific password for Gmail
$port = 587; // SMTP port for TLS

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $otp = $_POST['otp'];

    // Validate input
    if (empty($email) || empty($otp)) {
        echo json_encode(['success' => false, 'message' => 'Email and OTP are required.']);
        exit();
    }

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Configure PHPMailer
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $port;

        // Set email details
        $mail->setFrom($username, 'Your App Name');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'OTP Verification';
        $mail->Body    = 'Your OTP code is: ' . $otp;

        // Send the email
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'OTP sent successfully.']);
    } catch (Exception $e) {
        // Return error message
        echo json_encode(['success' => false, 'message' => 'Email could not be sent. Mailer Error: ' . $mail->ErrorInfo]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
