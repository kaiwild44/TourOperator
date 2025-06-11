<?php
ob_start();
include('db.php');
include('functions.php');

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$userRole = $_SESSION['role'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TourOperator.az</title>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.5.1/css/all.css">

<!-- Favicon -->
<link rel="shortcut icon" href="assets/css/favicon.ico" type="image/x-icon">

<!-- Custom CSS -->
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/style.css">

<!-- Date Range Picker CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

<!-- jQuery and Date Libraries -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js" defer></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
        <nav>
            <div id="logo">
                <a href="index.php">Tour<span>Operator</span>.az</a>
            </div>
            <label for="sub" class="toggle" id="hamburger">
                <i id="icon" class="fa fa-bars"></i>
            </label>
            <input type="checkbox" id="sub">
            <ul>
                <li>
                    <label for="sub-1" class="toggle">Board</label>
                    <a href="#">Board</a>
                    <input type="checkbox" id="sub-1">
                    <ul>
                        <li><a href="index.php">Board</a></li>
                        <?php if ($userRole === 'Superadmin' || $userRole === 'Admin' || $userRole === 'Sales_Manager') { ?>
                        <li><a href="board.php">Archive</a></li>
                        <?php } ?>
                    </ul>
                </li>
                <?php if ($userRole === 'Superadmin' || $userRole === 'Admin' || $userRole === 'Sales_Manager') { ?>
                    <li><a href="booking.php">Booking</a></li>
                    <li>
                        <label for="sub-2" class="toggle">Reports</label>
                        <a href="#">Reports</a>
                        <input type="checkbox" id="sub-2">
                        <ul>
                            <li><a href="bookings.php">Bookings</a></li>
                            <li><a href="booking-excel.php">Bookings (Excel)</a></li>
                            <li><a href="cashier.php">Cashier</a></li>
                            <li><a href="report-sales.php">Promoters</a></li>
                            <li><a href="report-partners.php">Partners</a></li>
                        </ul>
                    </li>
                <?php } elseif ($userRole === 'Online_Manager') { ?>
                    <li><a href="booking.php">Booking</a></li>
                    <li>
                        <label for="sub-2" class="toggle">Reports</label>
                        <a href="#">Reports</a>
                        <input type="checkbox" id="sub-2">
                        <ul>
                            <li><a href="bookings.php">Bookings</a></li>
                            <!-- Only the Bookings submenu is displayed for Online_Manager -->
                        </ul>
                    </li>
                <?php } ?>
                <?php if ($userRole === 'Superadmin' || $userRole === 'Admin' || $userRole === 'Sales_Manager') { ?>
                <li>
                    <label for="sub-3" class="toggle">Schedule</label>
                    <a href="#">Schedule</a>
                    <input type="checkbox" id="sub-3">
                    <ul>
                        <li><a href="guide-schedule.php">Guides</a></li>
                        <li><a href="guidance_stats.php">Guidance Stats</a></li>
                        <li><a href="driver-schedule.php">Drivers</a></li>
                        <li><a href="driver_stats.php">Driver Stats</a></li>
                    </ul>
                </li>
                <?php } ?>
                <?php if ($userRole === 'Superadmin' || $userRole === 'Admin' || $userRole === 'Sales_Manager' || $userRole === 'Promoter') { ?>
                <li>
                    <label for="sub-4" class="toggle">Documentation</label>
                    <a href="#">Documentation</a>
                    <input type="checkbox" id="sub-4">
                    <ul>
                        <li><a href="documentation.php">Promoter Training</a></li>
                    </ul>
                </li>
                <?php } ?>
                <?php if ($userRole === 'Superadmin' || $userRole === 'Admin' || $userRole === 'Sales_Manager') { ?>
                <li>
                    <label for="sub-5" class="toggle">Settings</label>
                    <a href="#">Settings</a>
                    <input type="checkbox" id="sub-5">
                    <ul>
                        <li><a href="users.php">Users</a></li>
                        <li><a href="tours.php">Tours</a></li>
                        <li><a href="groups.php">Groups</a></li>
                    </ul>
                </li>
                <?php } ?>
                <?php if ($userRole === 'Superadmin' || $userRole === 'Admin' || $userRole === 'Sales_Manager') { ?>
                <li><a href="programs.php">Programs</a></li>
                <?php } ?>
                <li>
                <label for="sub-6" class="toggle"><?php echo $username; ?></label>
                    <a href="#"><?php echo $username; ?></a>
                    <input type="checkbox" id="sub-6">
                    <ul>
                        <li><a href="users-profile.php">User Profile</a></li>
                        <?php if ($userRole === 'Guide' || $userRole === 'Hired_Guide' || $userRole === 'Driver' || $userRole === 'Hired_Driver' ) { ?>
                        <li><a href="my-program.php">My Program</a></li>
                        <?php } ?>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </header>
    <main>
        <div class="container">