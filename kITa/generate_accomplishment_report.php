<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
session_start();
@include 'db.php';

if (isset($_POST['update_signatories'])) {
    foreach ($_POST['signatories'] as $id => $data) {
        $name = $data['name'];
        $position = $data['position'];
        $query = "UPDATE signatories SET name = ?, position = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssi", $name, $position, $id);
        mysqli_stmt_execute($stmt);
    }
    header("Location: generate_report.php");
    exit();
}

// Fetch signatories
$signatories_query = "SELECT * FROM signatories";
$signatories_result = mysqli_query($conn, $signatories_query);
$signatories = mysqli_fetch_all($signatories_result, MYSQLI_ASSOC);

// Check if user is not logged in
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Check if item ID is provided
if (!isset($_GET['id'])) {
    header("Location: claimed_items.php");
    exit();
}

$item_id = mysqli_real_escape_string($conn, $_GET['id']);

// Query for the specific claimed item
$query = "SELECT * FROM claim_reports 
          WHERE id_item = ? 
          AND status = 'Claimed' 
          AND remark = 'Approved' 
          LIMIT 1";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $item_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$item = mysqli_fetch_assoc($result);

if (!$item) {
    header("Location: claimed_items.php");
    exit();
}

// Initialize DOMPDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->setChroot(__DIR__); // Restrict access to the current directory
$dompdf = new Dompdf($options);

// Start HTML content
$html = '
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    .header { 
        text-align: center; 
        display: inline-block; 
        width: 100%;
        margin-top: 20px;
        margin-bottom: 20px;
        position: relative; /* Make the header a positioned element */
    }
    .logo {
        vertical-align: top;
        width: 80px;
        margin-right: -70px;
        margin-top: -20px;
    }
    .text-content { 
        display: inline-block;
        vertical-align: middle;
        text-align: center;
    }
    .title { font-weight: bold; font-size: 16px; }
    .college { font-weight: bold; font-size: 12px; }
    .subtitle { font-weight: bold; font-size: 12px; }
    .reptitle { font-weight: bold; font-size: 14px; }
    .timestamp {
        position: absolute; 
        top: -40px;
        right: 0;
        font-size: 10px;
        text-align: right;
    }
    .table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 20px; 
        font-size: 11px; 
    }
    .table, .table th, .table td { 
        border: 1px solid black; 
        padding: 8px;
        text-align: left;
    }
    .image-container {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        gap: 30px;
        margin-top: 20px;
        width: 100%;
    }
    .image-box {
        text-align: center;
        flex: 1;
        page-break-inside: avoid;
        width: 50%; /* Set to take up half the width */
        margin: 0 auto; /* Center the image box */
    }
    .image-box h4 {
        margin-bottom: 10px;
    }
    .item-image {
        width: 100%; /* Take full width of the image-box */
        height: auto;
        max-height: 400px; /* Approximately half of A4 height */
        object-fit: contain; /* Maintain aspect ratio */
        border: 1px solid #ddd;
        padding: 5px;
        border-radius: 5px;
    }
    .no-page-break {
        page-break-inside: avoid;
    }
    .signatories {
        text-align: center;
        margin-top: 30%;
    }
    .sign-row {
        display: block;
        margin-top: 30px;
    }
    .sign-col {
        width: 48%; /* Each column takes up almost half of the page width */
        float: left;
        font-size: 12px;
    }
    .sign-col strong {
        display: block;
        margin-bottom: 5px;
    }
    .sign-col small {
        display: block;
        font-size: 10px;
    }
    /* Clear floats to prevent layout issues */
    .sign-row:after {
        content: "";
        display: table;
        clear: both;
    }
</style>

<div class="header">
    <img class="logo" src="' . __DIR__ . '/images/cvsu_logo.png" alt="University Logo">
    <div class="text-content">
        <div>Republic of the Philippines</div>
        <div class="title">CAVITE STATE UNIVERSITY</div>
        <div class="subtitle">Don Severino delas Alas Campus</div>
        <div>Indang, Cavite</div>
        <br>
        <div class="college">COLLEGE OF ENGINEERING AND INFORMATION TECHNOLOGY</div>
        <br>
        <div class="reptitle">ITEM CLAIM ACCOMPLISHMENT REPORT</div>
    </div>
    <div class="timestamp">
        Generated on: ' . date('F d, Y') . '
    </div>
</div>

<div class="content">
    <p>This is to certify that the following item has been successfully claimed from the University Civil Security Services:</p>

    <h4>Item Information</h4>
    <table class="table">
        <tr>
            <td width="30%"><strong>Report ID:</strong></td>
            <td>' . htmlspecialchars($item['id_item']) . '</td>
        </tr>
        <tr>
            <td><strong>Item Name:</strong></td>
            <td>' . htmlspecialchars($item['item_name']) . '</td>
        </tr>
        <tr>
            <td><strong>Item Category:</strong></td>
            <td>' . htmlspecialchars($item['item_category']) . '</td>
        </tr>
        <tr>
            <td><strong>Location Found:</strong></td>
            <td>' . htmlspecialchars($item['location_found']) . '</td>
        </tr>
        <tr>
            <td><strong>Other Details:</strong></td>
            <td>' . htmlspecialchars($item['other_details']) . '</td>
        </tr>
    </table>

    <h4>Claimer Information</h4>
    <table class="table">
        <tr>
            <td width="30%"><strong>Claimed By:</strong></td>
            <td>' . htmlspecialchars($item['claim_Fname'] . ' ' . $item['claim_Lname']) . '</td>
        </tr>
        <tr>
            <td><strong>Email:</strong></td>
            <td>' . htmlspecialchars($item['claim_email']) . '</td>
        </tr>
        <tr>
            <td><strong>Contact Number:</strong></td>
            <td>' . htmlspecialchars($item['claim_contact']) . '</td>
        </tr>
        <tr>
            <td><strong>Date Claimed:</strong></td>
            <td>' . date('F d, Y', strtotime($item['claim_date'])) . '</td>
        </tr>
        <tr>
            <td><strong>Time Claimed:</strong></td>
            <td>' . date('h:i A', strtotime($item['claim_time'])) . '</td>
        </tr>
        <tr>
            <td><strong>Claimer\'s Description:</strong></td>
            <td>' . htmlspecialchars($item['claim_desc']) . '</td>
        </tr>
    </table>';

    // Add item image and claimer's ID if available
    if (!empty($item['img1']) || !empty($item['validID'])) {
        $html .= '<div class="image-container">';

        // Add item image if available
        if (!empty($item['img1']) && file_exists(__DIR__ . '/../uploads/img_reported_items/' . $item['img1'])) {
            $itemImagePath = __DIR__ . '/../uploads/img_reported_items/' . $item['img1'];
            $itemImageData = base64_encode(file_get_contents($itemImagePath));
            $html .= '<div class="image-box no-page-break">
                        <h4>Item Image</h4>
                        <img class="item-image" src="data:image/jpeg;base64,' . $itemImageData . '" alt="Item Image">
                    </div>';
        }

        $html .= '</div>';
    }


    $html .= '</table>
    <div class="signatories">';
    $counter = 0; // Counter to track column positions
    
    foreach ($signatories as $signatory) {
        // Start a new row if the counter is 0
        if ($counter % 2 === 0) {
            $html .= '<div class="sign-row">';
        }
    
        // Add a signatory column
        $html .= '<div class="sign-col">
            <strong>' . htmlspecialchars($signatory['role']) . ':</strong> ' . htmlspecialchars($signatory['name']) . '<br>';
        if (!empty($signatory['position'])) {
            $html .= '<small>' . htmlspecialchars($signatory['position']) . '</small>';
        }
        $html .= '</div>';
    
        $counter++;
    
        // Close the row after two columns
        if ($counter % 2 === 0) {
            $html .= '</div>';
        }
    }
    
    // Close the last row if it’s incomplete
    if ($counter % 2 !== 0) {
        $html .= '</div>';
    }
    
    $html .= '</div>';

// Load content into DOMPDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('accomplishment_report_' . $item['item_name'] . '.pdf', ['Attachment' => false]);
exit();
?>
