<?php

include('inc/db.php'); 
include('inc/functions.php');

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager' && $_SESSION['role'] !== 'Online_Manager') {
    header("Location: access_denied.php");
    exit();
}

if (isset($_POST['months'])) {
    // Your current code already handles the months parameter correctly
    $months = intval($_POST['months']);

    $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $tourName = isset($_POST['tour_name']) ? trim($_POST['tour_name']) : '';
    $tourLang = isset($_POST['tour_lang']) ? trim($_POST['tour_lang']) : '';
    $agentFirstName = isset($_POST['agent_first_name']) ? trim($_POST['agent_first_name']) : '';
    $agentLastName = isset($_POST['agent_last_name']) ? trim($_POST['agent_last_name']) : '';

    // Check if months is zero to get all bookings
    if ($months === 0) {
        $startDate = '1970-01-01'; // Set to a very old date to include all records
        $endDate = date('Y-m-d');
    } else {
        $startDate = date('Y-m-d', strtotime("-$months months"));
        $endDate = date('Y-m-t');  // Get the end date of the current month

    }

    try {
        // Build the SQL query
        $sql = "
        SELECT b.*, u.First_Name AS agent_first_name, u.Last_Name AS agent_last_name
        FROM booking b
        LEFT JOIN users u ON b.agent_id = u.Id
        WHERE b.booking_date BETWEEN :start_date AND :end_date
    ";
        
        // Append filters if any filters are provided
        if (!empty($firstName)) {
            $sql .= " AND b.first_name LIKE :firstName";
        }
        if (!empty($lastName)) {
            $sql .= " AND b.last_name LIKE :lastName";
        }
        if (!empty($tourName)) {
            $sql .= " AND b.tour_name LIKE :tourName";
        }
        if (!empty($tourLang)) {
            $sql .= " AND b.lang LIKE :tourLang";
        }
        if (!empty($agentFirstName)) {
            $sql .= " AND u.First_Name LIKE :agentFirstName";
        }
        if (!empty($agentLastName)) {
            $sql .= " AND u.Last_Name LIKE :agentLastName";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);

        // Bind parameters for the filters if they are provided
        if (!empty($firstName)) {
            $firstName = "%$firstName%";
            $stmt->bindParam(':firstName', $firstName);
        }
        if (!empty($lastName)) {
            $lastName = "%$lastName%";
            $stmt->bindParam(':lastName', $lastName);
        }
        if (!empty($tourName)) {
            $tourName = "%$tourName%";
            $stmt->bindParam(':tourName', $tourName);
        }
        if (!empty($tourLang)) {
            $tourLang = "%$tourLang%";
            $stmt->bindParam(':tourLang', $tourLang);
        }
        if (!empty($agentFirstName)) {
            $agentFirstName = "%$agentFirstName%";
            $stmt->bindParam(':agentFirstName', $agentFirstName);
        }
        if (!empty($agentLastName)) {
            $agentLastName = "%$agentLastName%";
            $stmt->bindParam(':agentLastName', $agentLastName);
        }

        // Execute the SQL statement
        $stmt->execute();

        // Fetch results and generate the HTML for table rows
        $bookingData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $output = '';
        $count = 0;

        foreach ($bookingData as $row) {
            $rowClass = ($count % 2 === 0) ? 'even-row' : 'odd-row';
            $output .= '<tr class="' . $rowClass . '">';
            // Category display logic
            // $category_code = '';
            // switch ($row['category']) {
            //     case 'Street':
            //         $category_code = '<span style="background-color: #FF2400; padding: 2px; color: white;">S</span>';
            //         break;
            //     case 'Hotel':
            //         $category_code = '<span style="background-color: #007FFF; padding: 2px; color: white;">H</span>';
            //         break;
            //     case 'Online':
            //         $category_code = '<span style="background-color: #50C878; padding: 2px; color: white;">O</span>';
            //         break;
            //     case 'Multiday':
            //         $category_code = '<span style="background-color: #FF77FF; padding: 2px; color: white;">M</span>';
            //         break;
            //     default:  
            //     $category_code = htmlspecialchars($row['category']);
            //     break;
            // }
            // $output .= '<td>' . $category_code . ' ' . htmlspecialchars($row['voucher_no']) . '</td>'; 
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

            // Calculate the net price based on price and food/ticket costs
            $totalFood = $row['adult_food'] + $row['child_food'];
            $totalTickets = $row['adult_tickets'] + $row['child_tickets'];
            $netPrice = $row['price'] - ($totalFood + $totalTickets);

            // Add the Net Price to the output (formatted to 2 decimal places)
            $output .= '<td>' . number_format($netPrice, 2) . '</td>'; // This will be the Net Price column
            $output .= '<td>' . htmlspecialchars(($row['agent_first_name'] ?? 'N/A') . ' ' . ($row['agent_last_name'] ?? 'N/A')) . '</td>';
            
            // Actions Column
            $output .= '<td>';
            $output .= '<a href="booking-edit.php?id=' . htmlspecialchars($row['id']) . '"><i class="fa fa-edit" style="color:blue">&nbsp;&nbsp;</i></a>';
            $output .= '<a href="booking-dublicate.php?id=' . htmlspecialchars($row['id']) . '"><i class="fa fa-copy" style="color:green">&nbsp;&nbsp;</i></a>';
            $output .= '<a href="booking-delete.php?id=' . htmlspecialchars($row['id']) . '"><i class="fa fa-trash" style="color:red">&nbsp;&nbsp;</i></a>';
            $output .= '<a href="booking-pdf.php?id=' . htmlspecialchars($row['id']) . '"><i class="fa fa-ticket" style="color:gray"></i></a>';
            $output .= '</td>';
        
            $output .= '</tr>';
            $count++;
        }        

        echo $output; // Send the generated HTML back to the AJAX success function
    } catch (Exception $e) {
        echo 'Error: ' . htmlspecialchars($e->getMessage());
    }
} else {
    echo 'No months parameter sent.';
}
