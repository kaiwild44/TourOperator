<?php
include('inc/db.php'); 
include('inc/functions.php');

// Check user role for access control
if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

// Retrieve date range from POST request
$startDate = $_POST['start_date'] ?? null;
$endDate = $_POST['end_date'] ?? null;

try {
    // Start building the SQL query
    $sql = "
    SELECT b.*, u.First_Name AS agent_first_name, u.Last_Name AS agent_last_name
    FROM booking b
    LEFT JOIN users u ON b.agent_id = u.Id
    WHERE b.booking_date BETWEEN :startDate AND :endDate
    ";

    // Prepare the SQL statement
    $stmt = $pdo->prepare($sql);
    
    // Bind the date parameters
    $stmt->bindParam(':startDate', $startDate);
    $stmt->bindParam(':endDate', $endDate);

    // Execute the SQL statement
    $stmt->execute();

    // Fetch results and generate HTML for table rows
    $bookingData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $output = '';
    $count = 0;

    foreach ($bookingData as $row) {
        $rowClass = ($count % 2 === 0) ? 'even-row' : 'odd-row';
        $output .= '<tr class="' . $rowClass . '">';
        
        // Construct the output for each booking record
        $output .= '<td>' . htmlspecialchars($row['voucher_no']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['booking_date']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['tour_name']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['tour_type']) . ' / ' . htmlspecialchars($row['lang']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['tour_date']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['pickup_time']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['hotel']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['phone_no']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['email']) . '</td>';
        $output .= '<td>' . htmlspecialchars($row['adult_no'] . ' / ' . $row['child_no'] . ' / ' . $row['infant_no']) . '</td>';
        $output .= '<td>' . number_format(($row['adult_food'] + $row['child_food']), 2) . ' \\ ' .
                     number_format(($row['adult_tickets'] + $row['child_tickets']), 2) . '</td>';
        $output .= '<td>' . number_format($row['price'], 0) . '</td>';
        $output .= '<td>' . ($row['paid_cash'] + $row['paid_card']) . '</td>';
        $output .= '<td>' . number_format($row['balance_due'], 0) . '</td>';

        // Calculate the net price based on the total price and any associated costs
        $totalFood = $row['adult_food'] + $row['child_food'];
        $totalTickets = $row['adult_tickets'] + $row['child_tickets'];
        $netPrice = $row['price'] - ($totalFood + $totalTickets);

        // Add the Net Price to the output (formatted to 2 decimal places)
        $output .= '<td>' . number_format($netPrice, 2) . '</td>';
        $output .= '<td>' . htmlspecialchars(($row['agent_first_name'] ?? 'N/A') . ' ' . ($row['agent_last_name'] ?? 'N/A')) . '</td>';
        
        // Actions Column for edit, delete, etc.
        $output .= '<td>';
        $output .= '<a href="booking-duplicate.php?id=' . htmlspecialchars($row['id']) . '"><i class="fa fa-copy" style="color:green">&nbsp;&nbsp;</i></a>';
        $output .= '<a href="booking-delete.php?id=' . htmlspecialchars($row['id']) . '"><i class="fa fa-trash" style="color:red">&nbsp;&nbsp;</i></a>';
        $output .= '<a href="booking-pdf.php?id=' . htmlspecialchars($row['id']) . '"><i class="fa fa-ticket" style="color:gray"></i></a>';
        $output .= '</td>';
        
        $output .= '</tr>';
        $count++;
    }        

    // Output the entire generated HTML for the bookings table
    echo $output;

} catch (Exception $e) {
    // Handle errors gracefully
    echo 'Error: ' . htmlspecialchars($e->getMessage());
}