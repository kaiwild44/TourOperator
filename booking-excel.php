<?php

include('inc/header.php');

// Check if the user is not Superadmin or Admin
if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin') {
    header("Location: access_denied.php");
    exit();
}

// Define a mapping array for tour names
$tourNameMappings = array(
    'Gobus-Absheron' => 'Qobustan Abşeron',
    'Baku-Night' => 'Baku Night',
    'Gabala' => 'Şamaxı Qəbələ',
    'Guba-Shahdag' => 'Quba and Shahdagh',
    'Guba-Khinalig' => 'Quba and Khynaliq',
    'Gabala-Sheki' => 'Qəbələ + Şəki',
    'Old-City' => 'Old City',
);

// Check if the booking was successfully added
$bookingSuccess = false;
if (isset($_SESSION['booking_success']) && $_SESSION['booking_success']) {
    $bookingSuccess = true;
    // Clear the session variable to prevent showing the message again on page refresh
    unset($_SESSION['booking_success']);
}

// Fetch booking data from the database
$query = "SELECT booking.*, users.First_Name AS agent_first_name, users.Last_Name AS agent_last_name
          FROM booking
          LEFT JOIN users ON booking.agent_id = users.Id";
$stmt = $pdo->query($query);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="wrapper-fit-centered">
<h2 class="my-20 text-center">Bookings (Excel)</h2>
    <?php
    // Filter parameters
    $tourNameFilter = isset($_GET['tour-name-filter']) ? $_GET['tour-name-filter'] : 'all';
    $agentFilter = isset($_GET['agent-filter']) ? $_GET['agent-filter'] : 'all';
    $nameFilter = isset($_GET['name-filter']) ? $_GET['name-filter'] : '';
    $dateFrom = isset($_GET['date-from']) ? $_GET['date-from'] : '';
    $dateTo = isset($_GET['date-to']) ? $_GET['date-to'] : '';
    $tourDateFrom = isset($_GET['tour-date-from']) ? $_GET['tour-date-from'] : '';
    $tourDateTo = isset($_GET['tour-date-to']) ? $_GET['tour-date-to'] : '';
    $categoryFilter = isset($_GET['category-filter']) ? $_GET['category-filter'] : 'all';

    // Construct the base query
    $query = "SELECT booking.*, users.First_Name AS agent_first_name, users.Last_Name AS agent_last_name
    FROM booking
    LEFT JOIN users ON booking.agent_id = users.Id
    WHERE 1=1"; // 1 is always true

    // Append conditions based on filtering options
    $params = [];

    if ($tourNameFilter !== 'all') {
    $query .= " AND booking.tour_name = :filterName";
    $params['filterName'] = $tourNameFilter;
    }

    if ($agentFilter !== 'all') {
    $query .= " AND CONCAT(users.First_Name, ' ', users.Last_Name) = :agentName";
    $params['agentName'] = $agentFilter;
    }

    if (!empty($nameFilter)) {
        // Split the name filter into first name and last name
        $nameParts = explode(' ', $nameFilter);
        $firstName = isset($nameParts[0]) ? $nameParts[0] : '';
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

        // Add conditions for filtering by both first name and last name
        $query .= " AND booking.first_name = :firstNameFilter AND booking.last_name = :lastNameFilter";
        $params['firstNameFilter'] = $firstName;
        $params['lastNameFilter'] = $lastName;
    }

    if (!empty($dateFrom)) {
        $query .= " AND booking.booking_date >= :dateFrom"; // Change here
        $params['dateFrom'] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $query .= " AND booking.booking_date <= :dateTo"; // Change here
        $params['dateTo'] = $dateTo;
    }

    if (!empty($tourDateFrom)) {
        $query .= " AND booking.tour_date >= :tourDateFrom";
        $params['tourDateFrom'] = $tourDateFrom;
    }

    if (!empty($tourDateTo)) {
        $query .= " AND booking.tour_date <= :tourDateTo";
        $params['tourDateTo'] = $tourDateTo;
    }

    if ($categoryFilter !== 'all') {
        $query .= " AND booking.category = :category";
        $params['category'] = $categoryFilter;
    }
    
    $query .= " ORDER BY booking.booking_date";

    // Prepare and execute the query with parameters
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ?>


    <div class="side-by-side">
        <form action="" method="get" id="booking-filters">
            
            <select id="tour-name-filter" name="tour-name-filter">
                <option value="all">Tour</option>
                <?php
                // Fetch distinct tour names from the database
                $nameQuery = "SELECT DISTINCT tour_name FROM booking";
                $nameStmt = $pdo->query($nameQuery);

                while ($nameRow = $nameStmt->fetch(PDO::FETCH_ASSOC)) {
                    $selected = ($tourNameFilter === $nameRow['tour_name']) ? 'selected' : '';
                    echo '<option value="' . $nameRow['tour_name'] . '" ' . $selected . '>' . $nameRow['tour_name'] . '</option>';
                }
                ?>
            </select>

            <input type="date" id="tour-date-from" name="tour-date-from" value="<?php echo $tourDateFrom; ?>">
            <input type="date" id="tour-date-to" name="tour-date-to" value="<?php echo $tourDateTo; ?>">

            <select id="name-filter" name="name-filter">
                <option value="">Tourist</option>
                <?php
                // Fetch distinct names from the database
                $nameQuery = "SELECT DISTINCT CONCAT(first_name, ' ', last_name) AS full_name FROM booking";
                $nameStmt = $pdo->query($nameQuery);

                while ($nameRow = $nameStmt->fetch(PDO::FETCH_ASSOC)) {
                    $selected = isset($_GET['name-filter']) && $_GET['name-filter'] === $nameRow['full_name'] ? 'selected' : '';
                    echo '<option value="' . $nameRow['full_name'] . '" ' . $selected . '>' . $nameRow['full_name'] . '</option>';
                }
                ?>
            </select>

            <input type="date" id="date-from" name="date-from" value="<?php echo $dateFrom; ?>">
            <input type="date" id="date-to" name="date-to" value="<?php echo $dateTo; ?>">
            <!-- Add category filter -->
            <select id="category-filter" name="category-filter">
                <option value="all">Category</option>
                <?php
                // Fetch distinct categories from the database
                $categoryQuery = "SELECT DISTINCT category FROM booking";
                $categoryStmt = $pdo->query($categoryQuery);

                while ($categoryRow = $categoryStmt->fetch(PDO::FETCH_ASSOC)) {
                    $selected = ($categoryFilter === $categoryRow['category']) ? 'selected' : '';
                    echo '<option value="' . $categoryRow['category'] . '" ' . $selected . '>' . $categoryRow['category'] . '</option>';
                }
                ?>
            </select>
            <!-- End of category filter -->

            <!-- Add agent filter -->
            <select id="agent-filter" name="agent-filter">
                <option value="all">Agent</option>
                <?php
                // Fetch distinct agent names from the database that are present in the bookings
                $agentQuery = "SELECT DISTINCT CONCAT(First_Name, ' ', Last_Name) AS agent_name FROM users 
                            WHERE CONCAT(First_Name, ' ', Last_Name) IN (
                                    SELECT DISTINCT CONCAT(users.First_Name, ' ', users.Last_Name) AS agent_name 
                                    FROM users
                                    INNER JOIN booking ON booking.agent_id = users.Id
                            )";
                $agentStmt = $pdo->query($agentQuery);

                while ($agentRow = $agentStmt->fetch(PDO::FETCH_ASSOC)) {
                    $selected = ($agentFilter === $agentRow['agent_name']) ? 'selected' : '';
                    echo '<option value="' . $agentRow['agent_name'] . '" ' . $selected . '>' . $agentRow['agent_name'] . '</option>';
                }
                ?>
            </select>
            <!-- End of agent filter -->
            <button type="submit" class="btn">Filter</button>
            <button type="button" class="btn" onclick="clearFilters()">Clear Filters</button>
        </form>
        <br>
    </div>
    <div class="table-container">
        <table id="booking-table" class="table font-small-8">
                <tr class="sticky-header">
                    <th>Date</th>
                    <th>Voucher</th>
                    <th>Paid</th>
                    <th>Price</th>
                    <th>Tour Date</th>
                    <th>Tour Name</th>
                    <th>Bal</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Adl</th>
                    <th>Chl</th>
                    <th>Hotel</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>Room</th>
                    <th></th>
                    <th>Agent</th>
                    <th>Payment</th>
                </tr>
                <?php foreach ($bookings as $index => $booking): ?>
                <tr class="<?php echo $index % 2 == 0 ? 'even-row' : 'odd-row'; ?>">
                    <td><?php echo date('d-m-y', strtotime($booking['booking_date'])); ?></td>
                    <!--<td><?php echo $booking['voucher_no']; ?></td>-->
                    <td><?php
                        // Switch statement
                        $category_code = '';
                        switch ($booking['category']) {
                            case 'Promoter':
                                $category_code = '<span style="background-color: #7F00FF; padding: 2px; color: white;">P</span>';
                                break;
                            case 'Street':
                                $category_code = '<span style="background-color: #FF2400; padding: 2px; color: white;">S</span>';
                                break;
                            case 'Hotel':
                                $category_code = '<span style="background-color: #007FFF; padding: 2px; color: white;">H</span>';
                                break;
                            case 'Online':
                                $category_code = '<span style="background-color: #50C878; padding: 2px; color: white;">W</span>';
                                break;
                            case 'Web':
                                $category_code = '<span style="background-color: #FFA500; padding: 2px; color: white;">W</span>';
                                break;
                            case 'Multiday':
                                $category_code = '<span style="background-color: #FF77FF; padding: 2px; color: white;">M</span>';
                                break;
                            default:
                                $category_code = $booking['category']; // If category is none of the specified values
                                break;
                        }
                        
                        echo $category_code . " ". $booking['voucher_no']; // Concatenate with voucher_no
                    ?></td>
                    <td><?php echo $booking['paid_cash'] + $booking['paid_card']; ?></td>
                    <td><?php echo number_format($booking['price'], 0); ?></td>
                    <td><?php echo date('d-m-y', strtotime($booking['tour_date'])); ?></td>
                    <td>
                        <?php
                        // Check if the tour type is private
                        if ($booking['tour_type'] == 'Pr') {
                            // Display the private tour type and tour name with appropriate formatting
                            echo '<span class="error-msg">Private - </span>';
                        }

                        // Check if the tour name exists in the mapping array
                        if (isset($tourNameMappings[$booking['tour_name']])) {
                            // Display the mapped tour name if it exists
                            echo $tourNameMappings[$booking['tour_name']];
                        } else {
                            // Otherwise, display the original tour name
                            echo $booking['tour_name'];
                        }
                        ?>
                    </td>
                    <td><?php echo number_format($booking['balance_due'], 0); ?></td>
                    <td>
                        <?php
                        // Initialize an empty string to hold the information about excluded items indicators
                        $excludedItems = '';

                        // Check if food is excluded
                        if ($booking['food'] == 0) {
                            $excludedItems .= '<span class="error-msg">F-Ex</span>';
                        }

                        // Check if tickets are excluded
                        if ($booking['tickets'] == 0) {
                            // Add a comma separator if both food and tickets are excluded
                            if (!empty($excludedItems)) {
                                $excludedItems .= ', ';
                            }
                            $excludedItems .= '<span class="error-msg">T-Ex</span>';
                        }

                        // Output the tourist's name
                        echo $booking['first_name'] . ' ' . $booking['last_name'];

                        // Output language in parentheses if set
                        if (!empty($booking['lang'])) {
                            echo ' (' . $booking['lang'] . ')';
                        }

                        // Output excluded items indicators in parentheses if there are any
                        if (!empty($excludedItems)) {
                            echo ' (' . $excludedItems . ')';
                        }
                        ?>
                    </td>
                    <td>
                    <?php 
                    echo $booking['phone_no'];

                    // Check if there is a phone number extra and display it
                    if (!empty($booking['phone_no_extra'])) {
                        echo ", " . $booking['phone_no_extra'];
                    }

                    // Check if there is an email and display it
                    if (!empty($booking['email'])) {
                        echo ", " . $booking['email'];
                    }
                    ?>
                    </td>
                    <td><?php echo $booking['adult_no']; ?></td>
                    <td><?php echo $booking['child_no']; ?></td>
                    <td><?php echo $booking['hotel']; ?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td><?php echo $booking['room_no']; ?></td>
                    <td></td>
                    <td><?php echo $booking['agent_first_name'] . ' ' . $booking['agent_last_name']; ?></td>
                    <td><?php echo $booking['payment_method']; ?></td>
                </tr>
                <?php endforeach; ?>
        </table>
    </div>
    <br>
    <button class="btn" onclick="copyToClipboard()">Copy for Excel</button>
</div>

<script>
    function clearFilters() {
        document.getElementById('tour-name-filter').value = 'all';
        document.getElementById('tour-date-from').value = '';
        document.getElementById('tour-date-to').value = '';
        document.getElementById('name-filter').value = '';
        document.getElementById('agent-filter').value = 'all';
        document.getElementById('category-filter').value = 'all'; // Reset category filter
        document.getElementById('date-from').value = '';
        document.getElementById('date-to').value = '';

        // Submit the form without any filter parameters
        document.getElementById('booking-filters').submit();
    }
</script>

<script>
    function copyToClipboard() {
        var table = document.getElementById("booking-table");
        var range = document.createRange();
        var selection = window.getSelection();
        var copiedText = '';

        // Iterate through each row of the table, starting from the second row (index 1)
        for (var i = 1; i < table.rows.length; i++) {
            var row = table.rows[i];
        
            // Iterate through each cell of the row
            for (var j = 0; j < row.cells.length; j++) {
                var cell = row.cells[j];
        
                // Check if the cell is not a <th> element
                if (cell.tagName !== 'TH') {
                    // Add cell content to the copied text
                    copiedText += cell.innerText + '\t';
                }
            }
            // Add a new line after each row
            copiedText += '\n';
        }

        // Create a textarea element to hold the copied text
        var textarea = document.createElement('textarea');
        textarea.value = copiedText;

        // Append the textarea to the document body
        document.body.appendChild(textarea);

        // Select the content of the textarea
        textarea.select();

        // Copy the selected content
        document.execCommand('copy');

        // Remove the textarea from the document body
        document.body.removeChild(textarea);

        // Alert the user
        alert("Copied! Now you can paste!");
    }
</script>


<?php include('inc/footer.php'); ?>
