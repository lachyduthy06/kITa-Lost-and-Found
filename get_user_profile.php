 <?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lost_found_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Capture email from query parameter
$email = $_GET['email'];

// SQL query to fetch user profile data
$sql = "SELECT Fname, Lname, contactNo, dept FROM users WHERE email = ?";

// Prepare and bind the statement
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);

// Execute the statement and fetch the result
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Output user data in JSON format
    $row = $result->fetch_assoc();
    echo json_encode($row);
} else {
    // No user found
    echo json_encode(array("error" => "User not found"));
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
