<?php
session_start();
@include 'db.php';
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Query to get total unclaimed and claimed items
$unclaimedQuery = "SELECT COUNT(*) as total_unclaimed FROM reported_items WHERE status = 'Unclaimed' AND remark = 'Approved' OR remark = 'Pending'";
$unclaimedResult = mysqli_query($conn, $unclaimedQuery);
$unclaimedRow = mysqli_fetch_assoc($unclaimedResult);
$totalUnclaimedItems = $unclaimedRow['total_unclaimed'];

$claimedQuery = "SELECT COUNT(*) as total_claimed FROM reported_items WHERE status = 'Claimed' AND remark = 'Approved'";
$claimedResult = mysqli_query($conn, $claimedQuery);
$claimedRow = mysqli_fetch_assoc($claimedResult);
$totalClaimedItems = $claimedRow['total_claimed'];

// Query to get the recently reported lost items
$recentlyReportLostQuery = "SELECT * FROM reported_lost_items WHERE status = 'Missing' ORDER BY report_date DESC, report_time DESC LIMIT 5";
$recentlyReportLostResult = mysqli_query($conn, $recentlyReportLostQuery);

// Query to get recently lost and found items
$recentlyLostQuery = "SELECT * FROM reported_items WHERE status = 'Unclaimed' AND remark = 'Approved' ORDER BY report_date DESC, report_time DESC LIMIT 5";
$recentlyLostResult = mysqli_query($conn, $recentlyLostQuery);

$recentlyFoundQuery = "SELECT * FROM claim_reports WHERE status = 'Claimed' AND remark = 'Approved' ORDER BY claim_date DESC, claim_time DESC LIMIT 5";
$recentlyFoundResult = mysqli_query($conn, $recentlyFoundQuery);

// Query to get most lost and found item categories
$mostLostFoundCategoriesQuery = "SELECT item_category, 
                                        COUNT(*) as total_count,
                                        SUM(CASE WHEN status = 'Unclaimed' AND remark = 'Approved' OR remark = 'Pending' THEN 1 ELSE 0 END) as found_count,
                                        SUM(CASE WHEN status = 'Claimed' AND remark = 'Approved' THEN 1 ELSE 0 END) as claimed_count
                                 FROM reported_items
                                 WHERE remark = 'Approved' OR remark = 'Pending'
                                 GROUP BY item_category
                                 ORDER BY total_count DESC
                                 LIMIT 5";
$mostLostFoundCategoriesResult = mysqli_query($conn, $mostLostFoundCategoriesQuery);

// Query to get most lost reported item categories
$mostLostReportCategory = "SELECT item_category,
                                  COUNT(*) as total_count,
                                  SUM(CASE WHEN status = 'Missing' OR status = 'Found' THEN 1 ELSE 0 END) as lost_count
                           FROM reported_lost_items
                           WHERE status = 'Missing'
                           GROUP BY item_category
                           ORDER BY total_count DESC
                           LIMIT 5";
$mostLostReportCategoryResult = mysqli_query($conn, $mostLostReportCategory);

// Query to get college statistics
$collegeStatsQuery = "SELECT 
    dept_college,
    COUNT(*) as total_reports,
    SUM(CASE WHEN status = 'Unclaimed' AND remark = 'Approved' THEN 1 ELSE 0 END) as found_items,
    SUM(CASE WHEN status = 'Claimed' AND remark = 'Approved' THEN 1 ELSE 0 END) as claimed_items
FROM reported_items
WHERE remark = 'Approved'
GROUP BY dept_college
ORDER BY total_reports DESC";
$collegeStatsResult = mysqli_query($conn, $collegeStatsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" href="images/kitaoldlogo.png" type="img/png" sizes="48x48">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/dashboard_styles.css">
    <link rel="stylesheet" href="fontawesome/css/all.min.css">

    <script src="package/dist/chart.umd.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <?php include "sidebar.php"; ?>

    <div class="content container-fluid">
        <!-- Dashboard Header with Report Button -->
        <div class="dashboard-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">Dashboard</h1>
                <div class="d-flex gap-2">
                    <a href="generate_report.php" class="btn btn-success btn-report">
                        <i class="fas fa-file-pdf me-2"></i>Generate Report
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recently Reported Items -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h4>Recently Reported Found Items</h4>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($recentlyLostResult)) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['item_name']); ?></td>
                                    <td><?= htmlspecialchars($row['item_category']); ?></td>
                                    <td><?= htmlspecialchars($row['location_found']); ?></td>
                                    <td><?= date('M d, Y', strtotime($row['report_date'])); ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recently Claimed Items -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h4>Recently Claimed Items</h4>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($recentlyFoundResult)) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['item_name']); ?></td>
                                    <td><?= htmlspecialchars($row['item_category']); ?></td>
                                    <td><?= htmlspecialchars($row['location_found']); ?></td>
                                    <td><?= date('M d, Y', strtotime($row['claim_date'])); ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Recently Reported Lost Items -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h4>Recently Reported Lost Items</h4>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($recentlyReportLostResult)) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['item_name']); ?></td>
                                    <td><?= htmlspecialchars($row['item_category']); ?></td>
                                    <td><?= htmlspecialchars($row['location_lost']); ?></td>
                                    <td><?= date('M d, Y', strtotime($row['report_date'])); ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Total Claimed and Unclaimed Items</h4>
                        <div class="chart-container">
                            <canvas id="totalItemsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Most Frequent Item Categories</h4>
                        <div class="chart-container">
                            <canvas id="categoriesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Reports by College</h4>
                        <div class="chart-container">
                            <canvas id="collegeStatsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts JavaScript -->
    <script>
        // Total Items Chart
        new Chart(document.getElementById('totalItemsChart'), {
            type: 'doughnut',
            data: {
                labels: ['Unclaimed Items', 'Claimed Items'],
                datasets: [{
                    label: 'Count',
                    data: [<?php echo $totalUnclaimedItems; ?>, <?php echo $totalClaimedItems; ?>],
                    backgroundColor: ['#4CAF50', '#2196F3'],
                    borderColor: ['#3d8b40', '#1976d2'],
                    borderWidth: 0
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });

        // Categories Chart
        new Chart(document.getElementById('categoriesChart'), {
            type: 'line',
            data: {
                labels: [<?php
                    mysqli_data_seek($mostLostFoundCategoriesResult, 0);
                    while ($row = mysqli_fetch_assoc($mostLostFoundCategoriesResult)) {
                        echo "'" . addslashes($row['item_category']) . "',";
                    }
                ?>],
                datasets: [{
                    label: 'Lost',
                    data: [<?php
                        mysqli_data_seek($mostLostReportCategoryResult, 0);
                        while ($row = mysqli_fetch_assoc($mostLostReportCategoryResult)) {
                            echo $row['lost_count'] . ",";
                        }
                    ?>],
                    backgroundColor: '#8B0000',
                    borderColor: '#A52A2A',
                    borderWidth: 1
                },
                {
                    label: 'Found',
                    data: [<?php
                        mysqli_data_seek($mostLostFoundCategoriesResult, 0);
                        while ($row = mysqli_fetch_assoc($mostLostFoundCategoriesResult)) {
                            echo $row['found_count'] . ",";
                        }
                    ?>],
                    backgroundColor: '#4CAF50',
                    borderColor: '#3d8b40',
                    borderWidth: 1
                },
                {
                    label: 'Claimed',
                    data: [<?php
                        mysqli_data_seek($mostLostFoundCategoriesResult, 0);
                        while ($row = mysqli_fetch_assoc($mostLostFoundCategoriesResult)) {
                            echo $row['claimed_count'] . ",";
                        }
                    ?>],
                    backgroundColor: '#2196F3',
                    borderColor: '#1976D2',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // College Statistics Chart
        new Chart(document.getElementById('collegeStatsChart'), {
            type: 'bar',
            data: {
                labels: [<?php
                    mysqli_data_seek($collegeStatsResult, 0);
                    while ($row = mysqli_fetch_assoc($collegeStatsResult)) {
                        echo "'" . addslashes($row['dept_college']) . "',";
                    }
                ?>],
                datasets: [{
                    label: 'Found Items',
                    data: [<?php
                        mysqli_data_seek($collegeStatsResult, 0);
                        while ($row = mysqli_fetch_assoc($collegeStatsResult)) {
                            echo $row['found_items'] . ",";
                        }
                    ?>],
                    backgroundColor: '#FF9800',
                    borderColor: '#F57C00',
                    borderWidth: 1
                },
                {
                    label: 'Claimed Items',
                    data: [<?php
                        mysqli_data_seek($collegeStatsResult, 0);
                        while ($row = mysqli_fetch_assoc($collegeStatsResult)) {
                            echo $row['claimed_items'] . ",";
                        }
                    ?>],
                    backgroundColor: '#2196F3',
                    borderColor: '#1976D2',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: 'Lost and Found Items by College'
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        title: {
                            display: true,
                            text: 'Number of Items'
                        }
                    }
                }
            }
        });
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const links = document.querySelectorAll('.nav-link, .dropdown-item');
        const currentPath = window.location.pathname.split("/").pop();

        links.forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });
    });
    </script>
</body>
</html>