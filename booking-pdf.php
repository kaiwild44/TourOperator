<?php
// Database connection information
include('inc/db.php');
require('inc/pdf/fpdf186/fpdf.php'); // Include FPDF library

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager' && $_SESSION['role'] !== 'Online_Manager') {
    header("Location: access_denied.php");
    exit();
}

// Check if the ID parameter is provided in the URL
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch data for the specific entry using the provided ID
    $stmt = $pdo->prepare("SELECT b.voucher_no, b.booking_date, b.first_name, b.last_name, b.country, b.hotel, b.room_no, b.phone_no, b.tour_name, b.tour_type, b.tour_date, b.adult_no, b.child_no, b.infant_no, b.pickup_time, b.price, b.paid_cash, b.balance_due, b.agent_id, b.food, b.tickets, u.First_Name AS agent_first_name, u.Last_Name AS agent_last_name
    FROM booking b
    LEFT JOIN users u ON b.agent_id = u.Id
    WHERE b.id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if($booking) {
        // Calculate end time based on the start time (pickup time)
        // $startTime = strtotime($booking['pickup_time']);
        // $endTime = strtotime('+1 hour', $startTime);

        // Format the pickup time with the one-hour range
        // $pickupTime = date('H:i', $startTime) . ' - ' . date('H:i A', $endTime);

        // Set the file name
        $fileName = $booking['first_name'] . $booking['last_name'] . date('Ymd') . ".pdf";

        // Create a new PDF instance with A4 portrait page size
        $pdf = new FPDF();
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('Arial', 'B', 12);

        // Logo Area
        $pdf->Image('img/logo.png', 10, 10, 50); // Adjust the path and dimensions as per your logo

        // Tour Name Area
        // Modify tour type display
        $tourType = ($booking['tour_type'] == 'Gr') ? 'Group' : (($booking['tour_type'] == 'Pr') ? 'Private' : $booking['tour_type']);

        // Set font size for the title
        $pdf->SetFont('Arial', 'B', 14); // Font family, style (bold), and size (14 points)

        $pdf->SetFont('Arial', '', 18); // Reset font size to regular (12 points)
        $pdf->Cell(200, 6, 'Booking: ' . $booking['voucher_no'], 0, 1, 'C');
        $pdf->Ln(8); // Adjusted vertical spacing

        // Date Area
        $pdf->SetY(10); // Set vertical position to align with the top
        $pdf->Cell(190, 6, $booking['booking_date'], 0, 1, 'R'); // Adjusted for spacing

        $pdf->SetFont('Arial', '', 12); // Reset font size to regular (12 points)
        // Concatenate the agent's first name and last name for display
        $agentName = $booking['agent_first_name'] . ' ' . $booking['agent_last_name'];
        $pdf->Cell(190, 6, $agentName, 0, 1, 'R');


        // Determine food status
        $food_status = ($booking['food'] == 1) ? 'Included' : 'Excluded';

        // Determine tickets status
        $tickets_status = ($booking['tickets'] == 1) ? 'Included' : 'Excluded';

        $phone_no = $booking['phone_no'];

        $phone_no = $booking['phone_no'];
        $whatsapp1 = 'https://wa.me/' . $phone_no;
        
        // Data
        $data = array(
            'Tour Name:' => $booking['tour_name'],
            'Tour Date:' => $booking['tour_date'],
            'Full Name:' => $booking['first_name'] . ' ' . $booking['last_name'],
            'Country:' => $booking['country'],
            'Phone No:' => $phone_no, // Display phone number textually
            'Pickup Point:' => ($booking['room_no'] ? $booking['hotel'] . ' (Room: ' . $booking['room_no'] . ')' : $booking['hotel']), // Check if room number is available
            'Pickup Time:' => $booking['pickup_time'],
            // 'Pickup Time:' => $pickupTime, // Use the calculated pickup time range
            'Adults:' => $booking['adult_no'],
            'Children:' => $booking['child_no'],
            'Infants:' => $booking['infant_no'],
            'Price:' => $booking['price'],
            'Paid:' => $booking['paid_cash'],
            'Balance Due:' => $booking['balance_due'],
            'Food:' => $food_status, // Use determined food status
            'Tickets:' => $tickets_status // Use determined tickets status
        );
        
        // Output data in table format
        $pdf->SetY(30); // Set vertical position to align with the top
        $pdf->SetFont('Arial', '', 20);
        $pdf->SetFillColor(200, 220, 255); // Set background color for header row
        $pdf->SetTextColor(0); // Set text color to black

        // Add table data
        foreach($data as $field => $value) {
            // Set width for description and value columns
            $descWidth = 60;
            $valueWidth = 70;

            // Set line height
            $lineHeight = 10.4; // Adjust as needed

            // Add an empty cell for spacing on the left side
            $pdf->Cell(1); // Adjust the width as needed for the desired space

            // Output description in the left column
            $pdf->Cell($descWidth, $lineHeight, $field, 0);

            // Set font style to bold for the value
            $pdf->SetFont('Arial', 'B', 20);

            // Check if the field is 'balance_due' to set its color to red
            if ($field === 'Balance Due:') {
                $pdf->SetTextColor(255, 0, 0); // Set text color to red
            }

            // Output value in the right column
            if ($field === 'Phone No:') {
                $whatsappLink = 'https://wa.me/' . $value;
                $pdf->SetFont('Arial', 'B', 20); // Set underline for the phone number
                $pdf->Cell($valueWidth, $lineHeight, $value, 0, 0, 'L', false, $whatsappLink); // Make the phone number clickable
                $pdf->SetFont('Arial', '', 20); // Reset font
                $pdf->Ln(); // Move to the next line after displaying the phone number
            } else {
                // Output value in the right column
                $pdf->Cell($valueWidth, $lineHeight, $value, 0, 1, 'L');
            }

            // Reset font style to normal
            $pdf->SetFont('Arial', '', 20);

            // Reset text color to black
            $pdf->SetTextColor(0);
        }

        // Define the phone number, WhatsApp link, Instagram link, and their respective icons
        $phone = '+994502844451';
        $whatsapp = 'https://wa.me/994502844451';
        $whatsappIcon = 'img/whatsapp.png'; // Adjust the path to your WhatsApp icon PNG file
        $link = "https://ati.az/";
        $instagram = "https://instagram.com/atitravelen/";
        $instagramIcon = 'img/instagram.png'; // Adjust the path to your Instagram icon PNG file
        $websiteIcon = 'img/web.png'; // Adjust the path to your Website icon PNG file

        // Add vertical spacing before the contact information
        $pdf->Ln(3);

        // Set the font
        $pdf->SetFont('Arial', '', 18);

        // Set the text color to blue
        $pdf->SetTextColor(0, 0, 255);

        // Set the font for underlined text
        $pdf->SetFont('Arial', '', 18);

        // Set the position (X and Y coordinates) for the WhatsApp icon
        $iconX = $pdf->GetX() + 14; // Get the current X position
        $iconY = $pdf->GetY() + 2; // Adjust the Y position if needed

        // Display the WhatsApp icon with specified X and Y coordinates
        $pdf->Image($whatsappIcon, $iconX, $iconY, 10); // Adjust 10 to fit the size of your icon

        // Display the phone number
        $pdf->Cell(70, 12, $phone, 0, 0, 'R', false, $whatsapp);

        // Set the position (X and Y coordinates) for the Website icon
        $websiteIconX = $pdf->GetX() + 5; // Get the current X position
        $websiteIconY = $pdf->GetY() + 1; // Adjust the Y position if needed

        // Display the Website icon with specified X and Y coordinates
        $pdf->Image($websiteIcon, $websiteIconX, $websiteIconY, 10); // Adjust 10 to fit the size of your icon

        // Display the clickable website link
        $pdf->Cell(60, 12, 'www.ati.az', 0, 0, 'C', false, $link);

        // Set the position (X and Y coordinates) for the Instagram icon
        $instagramIconX = $pdf->GetX() - 9; // Get the current X position
        $instagramIconY = $pdf->GetY() + 1; // Adjust the Y position if needed

        // Display the Instagram icon with specified X and Y coordinates
        $pdf->Image($instagramIcon, $instagramIconX, $instagramIconY, 10); // Adjust 10 to fit the size of your icon

        // Display the clickable Instagram link
        $pdf->Cell(60, 12, '@atitravelen', 0, 0, 'L', false, $instagram);

        // Reset text color to black
        $pdf->SetTextColor(0, 0, 0);

        // Move to the next line
        $pdf->Ln();

        // Add a new section for the note
        $pdf->Ln(1); // Add vertical spacing before the note
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(190, 12, 'Conditions of individual and group tours', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 3.85, "1. In case of cancellation of tour / travel services due to any possible / unavoidable reasons, we shall be notified.\n\n2. If the Customer has cancelled (cancelled, cancelled) three days prior to the tour, the Customer will not be charged a penalty.\n\n3. If the Client refuses (cancels, cancels) two days prior to the start of the excursion, the Client will be charged a fine of 25% of the excursion cost.\n\n4. If the Client refuses (cancels, cancels) one day before the excursion, the Client will be charged a fine of 50% of the excursion cost.\n\n5. If the Client refused (cancelled, cancelled) on the day of the excursion, the Client is withheld a fine in the amount of 100% of the cost of the excursion.\n\n6. In case of lateness for 10 minutes for group tours and more than 30 minutes for individual tours without warning are accepted as a cancellation of the Tour Company.");

        // Output the PDF as a download
        $pdf->Output($fileName, 'D');

    } else {
        echo "Failed to fetch booking data.";
    }
} else {
    echo "No ID parameter provided in the URL.";
}
?>
