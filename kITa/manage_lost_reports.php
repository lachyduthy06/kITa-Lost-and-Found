<?php
session_start();
@include 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get filter and search parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_category = isset($_GET['search_category']) ? $_GET['search_category'] : 'item_name';

// Build where clause
$where_clause = "WHERE 1=1";

if (!empty($search)) {
    $search = mysqli_real_escape_string($conn, $search);
    
    // Dynamic search based on selected category
    switch ($search_category) {
        case 'item_name':
            $where_clause .= " AND item_name LIKE '%$search%'";
            break;
        case 'location_lost':
            $where_clause .= " AND location_lost LIKE '%$search%'";
            break;
        case 'date_lost':
            $where_clause .= " AND report_date = '$search'";
            break;
        case 'reported_by':
            $where_clause .= " AND (Fname LIKE '%$search%' OR Lname LIKE '%$search%')";
            break;
        default:
            $where_clause .= " AND item_name LIKE '%$search%'";
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['mark_all_read'])) {
        // Mark all as read
        $update_query = "UPDATE reported_lost_items SET view_status = 1 WHERE view_status = 0";
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['message'] = "All items marked as read!";
        } else {
            $_SESSION['message'] = "Error marking items as read: " . mysqli_error($conn);
        }
    } elseif (isset($_POST['id_item'])) {
        $id_item = mysqli_real_escape_string($conn, $_POST['id_item']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        $query = "UPDATE reported_lost_items SET status = '$status' WHERE id_item = '$id_item'";
        if (mysqli_query($conn, $query)) {
            $_SESSION['message'] = "Item updated successfully!";
        } else {
            $_SESSION['message'] = "Error updating item: " . mysqli_error($conn);
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Query to get items
$query = "SELECT * FROM reported_lost_items $where_clause ORDER BY report_date DESC, report_time DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost Items Reports</title>
    <link rel="icon" href="images/kitaoldlogo.png" type="img/png">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="fontawesome/css/all.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
    function handleSearch(event) {
        event.preventDefault();
        const searchCategory = document.getElementById('searchCategory').value;
        let searchValue = document.getElementById('searchInput').value;

        // Construct URL with search parameters
        let url = '<?php echo $_SERVER['PHP_SELF']; ?>?';
        
        // Only add search parameters if there's a value
        if (searchValue.trim() !== '') {
            url += `search=${encodeURIComponent(searchValue)}&`;
            url += `search_category=${encodeURIComponent(searchCategory)}`;
        }

        // Redirect to the constructed URL
        window.location.href = url;
    }

    function clearFilters() {
        window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>';
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Track status changes
        const rows = document.querySelectorAll('table tbody tr');
        rows.forEach(row => {
            const statusSelect = row.querySelector('select[name="status"]');
            
            if (statusSelect) {
                statusSelect.addEventListener('change', function() {
                    row.classList.add('row-changed');
                    row.style.backgroundColor = '#f0f0f0';
                });
            }
        });

        // Add event listener for search input
        document.querySelector('#searchInput').addEventListener('input', function(e) {
            if (this.value.trim() === '') {
                window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>';
            }
        });
    });
    </script>
</head>
<body>
    <?php include "sidebar.php"; ?>

    <div class="content">
        <div class="container-fluid">
            <h1 class="mb-4">Lost Items Reports</h1>

            <div class="d-flex justify-content-end mb-3">
                <form method="post" class="d-inline">
                    <button type="submit" name="mark_all_read" class="btn btn-success">
                        <i class="fas fa-envelope-open-text"></i> Mark All as Read
                    </button>
                </form>
            </div>

            <!-- Search form -->
            <div class="search-filters mb-3">
                <form id="searchForm" onsubmit="handleSearch(event)" class="row g-1 align-items-center">
                    <div class="col-md-3">
                        <div class="form-group">
                            <select id="searchCategory" class="form-select">
                                <option value="item_name" <?php echo $search_category == 'item_name' ? 'selected' : ''; ?>>Item Name</option>
                                <option value="location_lost" <?php echo $search_category == 'location_lost' ? 'selected' : ''; ?>>Location Lost</option>
                                <option value="date_lost" <?php echo $search_category == 'date_lost' ? 'selected' : ''; ?>>Date Lost</option>
                                <option value="reported_by" <?php echo $search_category == 'reported_by' ? 'selected' : ''; ?>>Reported By</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <input type="text" id="searchInput" class="form-control" 
                            placeholder="Search for items..." 
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary me-1">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card1">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Reported By</th>
                                    <th>Item Name</th>
                                    <th>Date Lost</th>
                                    <th>Location Lost</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($row['img1'])): ?>
                                            <img src="../uploads/img_reported_lost_items/<?php echo $row['img1']; ?>" 
                                                alt="Item Image" 
                                                class="item-thumbnail"
                                                data-bs-toggle="modal"
                                                data-bs-target="#imageModal<?php echo $row['id_item']; ?>">
                                        <?php else: ?>
                                            <div class="text-center">No Image</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['Fname'] . ' ' . $row['Lname']; ?></td>
                                    <td><?php echo $row['item_name']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['report_date'])); ?></td>
                                    <td><?php echo $row['location_lost']; ?></td>

                                    <form method="post">
                                        <input type="hidden" name="id_item" value="<?php echo $row['id_item']; ?>">
                                        
                                        <td>
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="Missing" <?php if ($row['status'] == 'Missing') echo 'selected'; ?>>Missing</option>
                                                <option value="Found" <?php if ($row['status'] == 'Found') echo 'selected'; ?>>Found</option>
                                            </select>
                                        </td>

                                        <td>
                                            <button type="submit" class="btn btn-success btn-sm">Save</button>
                                        </td>
                                    </form>

                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#itemModal<?php echo $row['id_item']; ?>">
                                            View
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal for each item -->
                                <div class="modal fade" id="itemModal<?php echo $row['id_item']; ?>" tabindex="-1" aria-labelledby="itemModalLabel<?php echo $row['id_item']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="itemModalLabel<?php echo $row['id_item']; ?>">Item Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p><strong>Reported By:</strong> <?php echo $row['Fname'] . ' ' . $row['Lname']; ?></p>
                                                        <p><strong>Email:</strong> <?php echo $row['email']; ?></p>
                                                        <p><strong>Contact No:</strong> <?php echo $row['contact_no']; ?></p>
                                                        <p><strong>College:</strong> <?php echo $row['dept_college']; ?></p>
                                                        <p><strong>Item Name:</strong> <?php echo $row['item_name']; ?></p>
                                                        <p><strong>Item Category:</strong> <?php echo $row['item_category']; ?></p>
                                                        <p><strong>Location Lost:</strong> <?php echo $row['location_lost']; ?></p>
                                                        <p><strong>Date Lost:</strong> <?php echo date('M d, Y', strtotime($row['report_date'])); ?></p>
                                                        <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($row['report_time'])); ?></p>
                                                        <p><strong>Other Details:</strong> <?php echo $row['other_details']; ?></p>
                                                        <p><strong>Status:</strong> <?php echo $row['status']; ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <!-- Carousel -->
                                                        <div id="carouselItem<?php echo $row['id_item']; ?>" class="carousel slide" data-bs-ride="carousel">
                                                            <div class="carousel-inner">
                                                                <?php 
                                                                $activeSet = false;
                                                                for ($i = 1; $i <= 5; $i++): 
                                                                    if (!empty($row["img$i"])): ?>
                                                                        <div class="carousel-item <?php echo !$activeSet ? 'active' : ''; ?>">
                                                                            <img src="../uploads/img_reported_lost_items/<?php echo $row["img$i"]; ?>" class="d-block w-100" alt="Image <?php echo $i; ?>">
                                                                        </div>
                                                                        <?php 
                                                                        $activeSet = true;
                                                                    endif; 
                                                                endfor; ?>
                                                            </div>
                                                            <!-- Carousel Controls -->
                                                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselItem<?php echo $row['id_item']; ?>" data-bs-slide="prev">
                                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                                <span class="visually-hidden">Previous</span>
                                                            </button>
                                                            <button class="carousel-control-next" type="button" data-bs-target="#carouselItem<?php echo $row['id_item']; ?>" data-bs-slide="next">
                                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                                <span class="visually-hidden">Next</span>
                                                            </button>
                                                        </div>
                                                        <!-- End of Carousel -->
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php if (mysqli_num_rows($result) == 0): ?>
                            <div class="alert alert-info text-center">
                                No items found matching the current filters.
                            </div>
                        <?php endif; ?>
                        </div> 
                    </div> 
                </div> 
            </div>
        </div>
</body>
</html>
                                