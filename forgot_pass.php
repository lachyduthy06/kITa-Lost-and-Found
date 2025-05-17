 <?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure the path to autoload.php is correct

$host = 'smtp.gmail.com'; // SMTP server for Gmail
$username = 'kitak2450@gmail.com'; // Your Gmail address
$password = 'rqjk wpfi wzbd coxj'; // Your app-specific password
$port = 587; // SMTP port for TLS

// Create a connection to the database
$hostDb = "localhost";
$dbname = "lost_found_db";
$dbUsername = "root";
$dbPassword = "";

$conn = new mysqli($hostDb, $dbUsername, $dbPassword, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['email'])) {
    $email = $conn->real_escape_string($_POST['email']);

    // Check if the email exists in the database
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);

        // Store the OTP in the database along with the user's email
        $query = "UPDATE users SET otp = '$otp' WHERE email = '$email'";
        if ($conn->query($query) === TRUE) {
            // Set up PHPMailer
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = $host;
                $mail->SMTPAuth = true;
                $mail->Username = $username;
                $mail->Password = $password;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = $port;

                $mail->setFrom($username, 'Your App Name');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'OTP Verification';
                $mail->Body    = 'Your OTP code is: ' . $otp;

                $mail->send();
                $response = array(
                    "success" => true,
                    "message" => "OTP sent to your email."
                );
            } catch (Exception $e) {
                $response = array(
                    "success" => false,
                    "message" => "Email could not be sent. Mailer Error: " . $mail->ErrorInfo
                );
            }
        } else {
            $response = array(
                "success" => false,
                "message" => "Failed to store OTP. Please try again."
            );
        }
    } else {
        $response = array(
            "success" => false,
            "message" => "Email not found."
        );
    }
} else {
    $response = array(
        "success" => false,
        "message" => "Email is required."
    );
}

// Send JSON response back to the client
header('Content-Type: application/json');
echo json_encode($response);

// Close the database connection
$conn->close();
?>
