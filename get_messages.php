<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lost_found_db";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$user_id = $_GET['user_id'];

// Check if the user is an admin
$admin_check_sql = "SELECT * FROM admins WHERE id = ?";
$admin_check_stmt = $conn->prepare($admin_check_sql);
$admin_check_stmt->bind_param("i", $user_id);
$admin_check_stmt->execute();
$admin_result = $admin_check_stmt->get_result();
$is_admin = $admin_result->num_rows > 0;
$admin_check_stmt->close();

if ($is_admin) {
    $sql = "SELECT * FROM admin_message ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT * FROM admin_message WHERE sender_id = ? OR receiver_id = ? ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);

$stmt->close();
$conn->close();
?>
