    <?php
    include('inc/header.php');

    if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager' && $_SESSION['role'] !== 'Online_Manager' && $_SESSION['role'] !== 'Coordinator' && $_SESSION['role'] !== 'Guide' && $_SESSION['role'] !== 'Hired_Guide'&& $_SESSION['role'] !== 'Driver'&& $_SESSION['role'] !== 'Hired_Driver') {
        header("Location: access_denied.php");
        exit();
    }

    // Check if group_id is provided
    if (!isset($_GET['group_id'])) {
        echo "No group ID provided.";
        exit;
    }
    $group_id = $_GET['group_id'];

    // Add this section to handle specific booking ID
    // Check if a specific booking ID is provided via GET
    if (isset($_GET['booking_id'])) {
        // Fetch the pickup time for this specific booking ID
        $bookingId = $_GET['booking_id'];
        
        $queryPickupTime = "SELECT pickup_time FROM booking WHERE id = :booking_id";
        $stmtPickupTime = $pdo->prepare($queryPickupTime);
        $stmtPickupTime->bindParam(':booking_id', $bookingId, PDO::PARAM_INT);
        $stmtPickupTime->execute();
        
        $pickupTimeData = $stmtPickupTime->fetch(PDO::FETCH_ASSOC);
        
        if ($pickupTimeData) {
            // Use the pickup time as needed
            $specificPickupTime = $pickupTimeData['pickup_time'];
        } else {
            $specificPickupTime = "No pickup time found for this booking.";
        }
    }


    $total_balance_due = 0; // Initialize total balance due variable

    // Initialize total for extra expenses
    $extraExpenseTotal = 0;

    // Query to fetch extra expenses for the group
    $sqlExtraExpenses = "
        SELECT id, expense_assignment, expense_amount
        FROM extra_expenses
        WHERE group_id = :group_id
    ";
    $stmtExtraExpenses = $pdo->prepare($sqlExtraExpenses);
    $stmtExtraExpenses->execute(['group_id' => $group_id]);
    $extraExpenses = $stmtExtraExpenses->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total of extra expenses
    foreach ($extraExpenses as $expense) {
        $extraExpenseTotal += $expense['expense_amount'];
    }

    try {
        // Query to fetch booking details along with agent information and group data
$sql = "
    SELECT 
        b.*,  -- This includes all fields from the 'booking' table, including 'balance_due' and 'price'
        u.First_Name AS agent_first_name, 
        u.Last_Name AS agent_last_name, 
        g.group_date,
        g.notes,
        g.max_seats, 
        g.guide_id, 
        g.guide_lang,
        g.second_guide_id, 
        g.second_guide_lang,
        g.driver_id,
        g.time_display,
        COALESCE(g.start_time, '') AS start_time, 
        (SELECT MIN(b_inner.pickup_time) FROM booking b_inner WHERE b_inner.group_id = b.group_id) AS earliest_pickup_time,
        b.adult_food, b.child_food, b.adult_tickets, b.child_tickets
    FROM booking b
    LEFT JOIN users u ON b.agent_id = u.Id
    LEFT JOIN groups g ON b.group_id = g.group_id
    WHERE b.group_id = :group_id
    ORDER BY b.pickup_time ASC;
";
    
        // Prepare and execute the booking query
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['group_id' => $group_id]);
        $groupBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        if (!$groupBookings) {
            echo "No bookings found for this group.";
            exit;
        }

        // Initialize total price variable
        $total_price = 0; // Initialize total price variable

        // Calculate total price
        foreach ($groupBookings as $booking) {
            $total_price += $booking['price'] ?? 0; // Accumulate total price
        }
    
        // Initialize group variables
        $group_date     = $groupBookings[0]['group_date'] ?? '';
        $notes          = $groupBookings[0]['notes'] ?? '';
        $groupStartTime = $groupBookings[0]['start_time'] ?? ''; 
        $formattedStartTime = $groupStartTime ? date('H:i', strtotime($groupStartTime)) : '';
        $maxSeats       = $groupBookings[0]['max_seats'] ?? 0; //Handle potential null
    
        // Initialize totals
        $foodExpenseTotal = 0;
        $ticketsExpenseTotal = 0;
        $totalAdults = 0;
        $totalChildren = 0;
    
        // Loop through the fetched bookings to aggregate totals
        foreach ($groupBookings as $booking) {
            // Sum adults and children
            $totalAdults += $booking['adult_no'];
            $totalChildren += $booking['child_no'];
    
            // Sum up food and ticket expenses directly
            $foodExpenseTotal += $booking['adult_food'] + $booking['child_food'];
            $ticketsExpenseTotal += $booking['adult_tickets'] + $booking['child_tickets'];
        }
    
        // Calculate total tourists
        $totalTourists = $totalAdults + $totalChildren;
    
        // Count required guide seats
        $guideSeats = ($totalAdults > 0 ? 1 : 0) + ($totalChildren > 0 ? 1 : 0);
    
        // Calculate available seats
        $maxSeats = $groupBookings[0]['max_seats'];
        $availableSeats = $maxSeats - ($totalTourists + $guideSeats);
    
        // Fetch assigned guides and driver details
        $guide_full_name = '';
        $guide_lang = '';
        $second_guide_full_name = '';
        $second_guide_lang = '';
        $driver_full_name = '';

        if (!empty($groupBookings[0]['guide_id'])) {
            $guide_id = $groupBookings[0]['guide_id'];
            $sqlGuide = "SELECT CONCAT(First_Name, ' ', Last_Name) AS full_name FROM users WHERE Id = :guide_id";
            $stmtGuide = $pdo->prepare($sqlGuide);
            $stmtGuide->execute(['guide_id' => $guide_id]);
            $guideData = $stmtGuide->fetch(PDO::FETCH_ASSOC);
            $guide_full_name = $guideData['full_name'] ?? '';
            $guide_lang = $groupBookings[0]['guide_lang'] ?? '';
        }
    
        if (!empty($groupBookings[0]['second_guide_id'])) {
            $second_guide_id = $groupBookings[0]['second_guide_id'];
            $sqlSecondGuide = "SELECT CONCAT(First_Name, ' ', Last_Name) AS full_name FROM users WHERE Id = :second_guide_id";
            $stmtSecondGuide = $pdo->prepare($sqlSecondGuide);
            $stmtSecondGuide->execute(['second_guide_id' => $second_guide_id]);
            $second_guideData = $stmtSecondGuide->fetch(PDO::FETCH_ASSOC);
            $second_guide_full_name = $second_guideData['full_name'] ?? '';
            $second_guide_lang = $groupBookings[0]['second_guide_lang'] ?? '';
        }
    
        if (!empty($groupBookings[0]['driver_id'])) {
            $driver_id = $groupBookings[0]['driver_id'];
            $sqlDriver = "SELECT CONCAT(First_Name, ' ', Last_Name) AS full_name FROM users WHERE Id = :driver_id";
            $stmtDriver = $pdo->prepare($sqlDriver);
            $stmtDriver->execute(['driver_id' => $driver_id]);
            $driverData = $stmtDriver->fetch(PDO::FETCH_ASSOC);
            $driver_full_name = $driverData['full_name'] ?? '';
        }
    
        // Calculate available seats based on assigned guides
        if (empty($guide_full_name) && empty($second_guide_full_name)) {
            // No guides assigned
            $availableSeats = $maxSeats - $totalTourists;
        } elseif (!empty($guide_full_name) && empty($second_guide_full_name)) {
            // One guide assigned
            $availableSeats = $maxSeats - ($totalTourists + 1);
        } else {
            // Two guides assigned
            $availableSeats = $maxSeats - ($totalTourists + 2);
        }
    
        // Fetch all available guides for selection
        $queryGuides = "SELECT Id, CONCAT(First_Name, ' ', Last_Name) AS full_name FROM users WHERE Role IN ('Guide', 'Hired_Guide')";
        $stmtGuides = $pdo->prepare($queryGuides);
        $stmtGuides->execute();
        $guides = $stmtGuides->fetchAll(PDO::FETCH_ASSOC);
    
        // Fetch all available drivers for selection
        $queryDrivers = "SELECT Id, CONCAT(First_Name, ' ', Last_Name) AS full_name FROM users WHERE Role IN ('Driver', 'Hired_Driver')";
        $stmtDrivers = $pdo->prepare($queryDrivers);
        $stmtDrivers->execute();
        $drivers = $stmtDrivers->fetchAll(PDO::FETCH_ASSOC);
    
    } catch (PDOException $e) {
        echo "Error fetching bookings: " . $e->getMessage();
        exit;
    }

    ?>
    
    <style>
        .red-text {
            color: red;
        }

        .green-text {
            color: green;
        }
        
        .program th,
        .program td {
            padding: 2px 10px 2px;
            /*border: 1px solid red;*/
        }
        
        .program td input {
            padding: 2px 10px 2px;
            margin-bottom: 0;
            margin: 2px;
        }
    </style>

    <h1>
        <?php 
        // Display the tour name from the first record if available
        $tourName = isset($groupBookings[0]['tour_name']) ? $groupBookings[0]['tour_name'] : 'Unknown Tour';
        echo htmlspecialchars($tourName); 
        ?> (<?php echo htmlspecialchars($group_date); ?>)<br>
    </h1>
    <h3>ID: <?php echo htmlspecialchars($group_id); ?> | Total Pax: <?php echo $totalTourists; ?> | Available Seats: 
    <span class="<?php echo $availableSeats <= 0 ? 'red-text' : 'green-text'; ?>">
        <?php echo $availableSeats; ?>
    </span></h3>

    <?php
    // Displaying session messages, if any
    if (isset($_SESSION['message'])) {
        echo "<div class='message'>".htmlspecialchars($_SESSION['message'])."</div>";
        unset($_SESSION['message']);
    }

    ?>

    <div class="wrapper-fit-centered">
        <?php if ($userRole === 'Superadmin' || $userRole === 'Admin' || $userRole === 'Sales_Manager' || $userRole === 'Online_Manager' || $userRole === 'Coordinator') { ?>
        <div class="table-container">
            <!-- <form action="update_pickup_time.php" method="POST"> -->
                <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>">
                <table class="table font-small-8 program">
                    <thead>
                        <tr class="sticky-header">
                            <th>No.</th>
                            <th>Lang</th>
                            <th>Tourist</th>
                            <th>Food/Tkts</th>
                            <th>Agent</th>
                            <th>Balance</th>
                            <th>Price</th>
                            <th>Phone Numbers</th>
                            <th>Adl</th>
                            <th>Chl</th>
                            <th>Time</th>
                            <th><i class="fa fa-save"></i></th>
                            <th>Pickup</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1; 
                        foreach ($groupBookings as $booking): 
                            $rowClass = ($count % 2 === 0) ? 'even-row' : 'odd-row'; 
                            // Accumulate total balance due
                            $total_balance_due += $booking['balance_due'] ?? 0; ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td><?php echo $count; ?></td>
                            <td><?php echo htmlspecialchars($booking['lang']); ?></td>
                            <td>
                                <a href="booking-edit.php?id=<?php echo $booking['id']; ?>">
                                    <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                                </a>
                                <?php if (!empty($booking['note'])): ?>  <!-- Conditional display -->
                                <span class="tooltip">
                                    <i class="fa fa-info-circle note-icon" 
                                        onclick="toggleTooltip(this, 'noteTooltip-<?php echo $booking['id']; ?>')"></i>
                                    <div class="tooltip-content" id="noteTooltip-<?php echo $booking['id']; ?>" style="display: none;">
                                        <?php echo htmlspecialchars($booking['note']); ?>
                                    </div>
                                </span>
                            <?php endif; ?>
                            </td>
                            <td>
                                <!-- Combine Food and Tickets in one cell -->
                                <?php
                                    $adultFood = (int)$booking['adult_food'];
                                    $childFood = (int)$booking['child_food'];
                                    $adultTickets = (int)$booking['adult_tickets'];
                                    $childTickets = (int)$booking['child_tickets'];

                                    // Format it as 'Food: Adult/Child | Tickets: Adult/Child'
                                    echo "$adultFood/$childFood | $adultTickets/$childTickets";
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($booking['agent_first_name'] . ' ' . $booking['agent_last_name']); ?></td>
                            <td style="<?php echo (isset($booking['balance_due']) && $booking['balance_due'] > 0) ? 'text-align: center; color: red; font-weight: bold;' : ''; ?>">
                                <?php echo htmlspecialchars(number_format($booking['balance_due'] ?? 0, 2)); ?>
                            </td>
                            <td><?php echo htmlspecialchars(number_format($booking['price'] ?? 0, 2)); ?></td> <!-- New Price Column -->
                            <td>
                                <?php 
                                // Assuming $booking['phone_no'] is in the correct format
                                $phone_no = htmlspecialchars($booking['phone_no']);
                                $phone_no_extra = htmlspecialchars($booking['phone_no_extra']);
                                $whatsapp_link = 'https://wa.me/' . ltrim($booking['phone_no'], '+'); // Remove the '+' sign if there is one
                                echo '<a href="' . $whatsapp_link . '" target="_blank">' . $phone_no . '</a>'; 
                                if (!empty($phone_no_extra)) {
                                    echo ', <a href="https://wa.me/' . ltrim($phone_no_extra, '+') . '" target="_blank">' . $phone_no_extra . '</a>';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($booking['adult_no']); ?></td>
                            <td><?php echo htmlspecialchars($booking['child_no']); ?></td>
                            <form action="update_pickup_time.php" method="POST">
                            <td>
                                <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>">
                                <input class="input-80" type="text" name="pickup_time[]" value="<?php echo htmlspecialchars($booking['pickup_time']); ?>" required>
                                <input type="hidden" name="booking_ids[]" value="<?php echo $booking['id']; ?>">
                            </td>
                            <td style="text-align:center">
                                &nbsp;<button type="submit" class="btn-save"><i class="fa fa-save"></i></button>&nbsp;
                            </td>
                            </form>
                            <td><?php echo htmlspecialchars($booking['hotel']); ?></td>
                        </tr>
                        <?php 
                        $count++; 
                        endforeach; ?>
                        <!-- Start Time -->
                        <tr>
                            <td colspan="10"></td>
                            <form action="update_start_time.php" method="POST">
                            <td>
                                    <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>">
                                    <input class="input-80" type="time" name="start_time" value="<?php echo htmlspecialchars($formattedStartTime); ?>" required>
                                </td>
                                <td>&nbsp;<button type="submit" class="btn-save"><i class="fa fa-save"></i></button>&nbsp;</td>
                            </form>
                            <td></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="text-align: right; font-weight: bold">Total:</td>
                            <td style="<?php echo ($total_balance_due > 0) ? 'text-align: center; color: red; font-weight: bold;' : ''; ?>">
                                <?php echo htmlspecialchars(number_format($total_balance_due, 2)); ?>
                            </td>
                            <td><?php echo htmlspecialchars(number_format($total_price, 2)); ?></td>
                            <td style="text-align: right; font-weight:bold;">Total:</td>
                            <td style="font-weight:bold;"><?php echo htmlspecialchars($totalAdults); ?></td>
                            <td style="font-weight:bold;"><?php echo htmlspecialchars($totalChildren); ?></td>

                            <!-- Update Max Seats -->
                            <td colspan="4" style="text-align: right; font-weight:bold;">
                                <div style="width: fit-content; display: flex;">
                                    <form action="update_max_seats.php" method="POST" style="display: flex; gap: 3px; flex-direction: column;">
                                        <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>">

                                        <div style="display: flex; gap: 5px; align-items: center;">
                                            <input class="input-80" type="number" name="max_seats" value="<?php echo htmlspecialchars($groupBookings[0]['max_seats']); ?>" placeholder="Update Max Seats" min="0" style="margin: 0;">
                                            <button type="submit" class="btn-save"><i class="fa fa-save"></i></button>
                                        </div>

                                        <label style="margin: 0;">
                                            <input type="checkbox" name="time_display" value="1" <?php echo ($groupBookings[0]['time_display'] == 1) ? 'checked' : ''; ?>>
                                            Display start time on board
                                        </label>
                                    </form>
                                </div>
                            </td>
                            <!-- // Update Max Seats -->
                            </td>
                        </tr>
                        <!-- Display Assigned Guides -->
                        <tr>
                            <td colspan="2">
                                <?php 
                                echo 'Guide (' . htmlspecialchars($guide_lang) . ')'; 
                                ?>
                            </td>
                            <td colspan="4" style="text-align: left;">
                                <?php 
                                $assigned_guides = [];
                                if (!empty($guide_full_name)) {
                                    $assigned_guides[] = htmlspecialchars($guide_full_name);
                                }
                                echo implode(', ', $assigned_guides);
                                ?>
                            </td>
                            <td colspan="7" style="text-align: left; vertical-align:top" rowspan="3">
                                <div style="max-width: 400px;">
                                    <form action="update_group_note.php" method="POST">
                                        <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>">
                                        <textarea name="notes" rows="4" placeholder="Enter notes here..." style="max-width: 400px; width: 100%;"><?php echo htmlspecialchars($notes); ?></textarea>
                                        <br>
                                        <!-- Add an input field for max_seats and set the value to the actual current max_seats -->
                                        <!-- <input type="number" name="max_seats" value="<?php echo htmlspecialchars($groupBookings[0]['max_seats']); ?>" placeholder="Update Max Seats" min="0" style="width: 100%; margin-top: 5px;">
                                        <br> -->
                                        <button type="submit" class="btn">Update</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php 
                                echo 'Guide (' . htmlspecialchars($second_guide_lang) . ')'; 
                                ?>
                            </td>
                            <td colspan="4" style="text-align: left;">
                                <?php 
                                $assigned_guides = [];

                                if (!empty($second_guide_full_name)) {
                                    $assigned_guides[] = htmlspecialchars($second_guide_full_name);
                                }

                                echo implode(', ', $assigned_guides);
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">Driver</td>
                            <td colspan="5" style="text-align: left;">
                                <?php 
                                echo htmlspecialchars($driver_full_name) ?: 'N/A'; 
                                ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            <!-- </form> -->
        </div>

        <br><br>
        <?php } else { ?>
            <div class="table-container">
            <form action="update_pickup_time.php" method="POST">
                <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>">
                <table class="table font-small-8">
                    <thead>
                        <tr class="sticky-header">
                            <th>No.</th>
                            <th>Lang</th>
                            <th>Tourist</th>
                            <th>Food/Tkts</th>
                            <th>Agent</th>
                            <th>Balance</th>
                            <th>Phone Numbers</th>
                            <th>Adl</th>
                            <th>Chl</th>
                            <th>Time</th>
                            <th>Pickup</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1; 
                        $total_balance_due = 0; // Initialize total balance due variable

                        foreach ($groupBookings as $booking): 
                            $rowClass = ($count % 2 === 0) ? 'even-row' : 'odd-row'; 
                            // Accumulate total balance due
                            $total_balance_due += $booking['balance_due'] ?? 0;
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td><?php echo $count; ?></td>
                            <td><?php echo htmlspecialchars($booking['lang']); ?></td>
                            <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                            <td>
                                <!-- Combine Food and Tickets in one cell -->
                                <?php
                                    $adultFood = (int)$booking['adult_food'];
                                    $childFood = (int)$booking['child_food'];
                                    $adultTickets = (int)$booking['adult_tickets'];
                                    $childTickets = (int)$booking['child_tickets'];

                                    // Format it as 'Food: Adult/Child | Tickets: Adult/Child'
                                    echo "$adultFood/$childFood | $adultTickets/$childTickets";
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($booking['agent_first_name'] . ' ' . $booking['agent_last_name']); ?></td>
                            <td style="<?php echo (isset($booking['balance_due']) && $booking['balance_due'] > 0) ? 'text-align: center; color: red; font-weight: bold;' : ''; ?>">
                                <?php echo htmlspecialchars(number_format($booking['balance_due'] ?? 0, 2)); ?>
                            </td>
                            <td>
                                <?php 
                                // Assuming $booking['phone_no'] is in the correct format
                                $phone_no = htmlspecialchars($booking['phone_no']);
                                $phone_no_extra = htmlspecialchars($booking['phone_no_extra']);
                                
                                // Format phone numbers for WhatsApp link
                                $whatsapp_link = 'https://wa.me/' . ltrim($booking['phone_no'], '+'); // Remove the '+' sign if there is one

                                // Display as a WhatsApp link
                                echo '<a href="' . $whatsapp_link . '" target="_blank">' . $phone_no . '</a>'; 

                                // Include extra phone number if it exists
                                if (!empty($phone_no_extra)) {
                                    echo ', <a href="https://wa.me/' . ltrim($phone_no_extra, '+') . '" target="_blank">' . $phone_no_extra . '</a>';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($booking['adult_no']); ?></td>
                            <td><?php echo htmlspecialchars($booking['child_no']); ?></td>
                            <td><?php echo htmlspecialchars($booking['pickup_time']); ?></td>
                            <td><?php echo htmlspecialchars($booking['hotel']); ?></td>
                        </tr>
                        <?php 
                        $count++; 
                        endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" style="text-align: right; font-weight: bold;">Total:</td>
                            <td style="<?php echo ($total_balance_due > 0) ? 'text-align: center; color: red; font-weight: bold;' : ''; ?>">
                                <?php echo htmlspecialchars(number_format($total_balance_due, 2)); ?>
                            </td>
                            <td style="text-align: right; font-weight: bold;">Total: </td>
                            <td style="font-weight: bold;"><?php echo htmlspecialchars($totalAdults); ?></td>
                            <td style="font-weight: bold;"><?php echo htmlspecialchars($totalChildren); ?></td>
                            <!-- <td></td> -->
                            <td colspan="2 style="text-align: right; font-weight: bold;">Total Seats: <?php echo htmlspecialchars($maxSeats); ?></td>

                        </tr>
                        <!-- Display Assigned Guides -->
                        <tr>
                            <td colspan="2">
                                <?php 
                                echo 'Guide (' . htmlspecialchars($guide_lang) . ')'; 
                                ?>
                            </td>
                            <td colspan="5" style="text-align: left;">
                                <?php 
                                $assigned_guides = [];

                                if (!empty($guide_full_name)) {
                                    $assigned_guides[] = htmlspecialchars($guide_full_name);
                                }

                                echo implode(', ', $assigned_guides);
                                ?>
                            </td>
                            <td colspan="5" style="text-align: left; vertical-align:top" rowspan="3">
                                <div style="max-width: 300px;">
                                    <?php echo htmlspecialchars($notes); ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <?php 
                                echo 'Guide (' . htmlspecialchars($second_guide_lang) . ')'; 
                                ?>
                            </td>
                            <td colspan="5" style="text-align: left;">
                                <?php 
                                $assigned_guides = [];

                                if (!empty($second_guide_full_name)) {
                                    $assigned_guides[] = htmlspecialchars($second_guide_full_name);
                                }

                                echo implode(', ', $assigned_guides);
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">Driver</td>
                            <td colspan="5" style="text-align: left;">
                                <?php 
                                echo htmlspecialchars($driver_full_name) ?: 'N/A'; 
                                ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </form>
        </div>
        <br>
        <?php } ?>

        <?php if ($userRole === 'Superadmin' || $userRole === 'Admin' || $userRole === 'Sales_Manager' || $userRole === 'Coordinator') { ?>
        <!-- Form to assign guides to the tour -->
        <div class="assign-guide-container side-by-side wrapper-fit-centered">
            <h2>Assign Driver/Guides</h2> 
            <form action="assign_guide.php" method="POST">
                <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>">

                <div>
                    <label for="guide_id">First Guide:</label>
                    <select name="guide_id">
                        <option value="">Select a guide</option>
                        <option value="null">None</option> <!-- Option to set to null -->
                        <?php foreach ($guides as $guide): ?>
                            <option value="<?php echo htmlspecialchars($guide['Id']); ?>" 
                                <?php echo (isset($guide_id) && $guide_id == $guide['Id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($guide['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="guide_lang">Language:</label>
                    <select name="guide_lang">
                        <option value="">Select language</option>
                        <option value="null">None</option> <!-- Option to set to null -->
                        <option value="Rus" <?php echo (isset($guide_lang) && $guide_lang == 'Rus') ? 'selected' : ''; ?>>Russian</option>
                        <option value="Eng" <?php echo (isset($guide_lang) && $guide_lang == 'Eng') ? 'selected' : ''; ?>>English</option>
                    </select>
                </div>

                <div>
                    <label for="second_guide_id">Second Guide:</label>
                    <select name="second_guide_id">
                        <option value="">Select a second guide</option>
                        <option value="null">None</option> <!-- Option to set to null -->
                        <?php foreach ($guides as $guide): ?>
                            <option value="<?php echo htmlspecialchars($guide['Id']); ?>" 
                                <?php echo (isset($second_guide_id) && $second_guide_id == $guide['Id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($guide['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="second_guide_lang">Language:</label>
                    <select name="second_guide_lang">
                        <option value="">Select language</option>
                        <option value="null">None</option> <!-- Option to set to null -->
                        <option value="Rus" <?php echo (isset($second_guide_lang) && $second_guide_lang == 'Rus') ? 'selected' : ''; ?>>Russian</option>
                        <option value="Eng" <?php echo (isset($second_guide_lang) && $second_guide_lang == 'Eng') ? 'selected' : ''; ?>>English</option>
                    </select>
                </div>

                <div class="driver-section">
                    <label for="driver_id">Select Driver:</label>
                    <select name="driver_id" required>
                        <option value="">Select a driver</option>
                        <?php foreach ($drivers as $driver): ?>
                            <option value="<?php echo htmlspecialchars($driver['Id']); ?>" 
                                <?php echo (isset($driver_id) && $driver_id == $driver['Id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($driver['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select><br><br>
                    <button type="submit" class="btn">Assign Guides</button>
                </div>
            </form>
        </div>
        <br>
        <?php } ?>

        <?php if ($userRole === 'Superadmin' || $userRole === 'Admin' || $userRole === 'Sales_Manager' || $userRole === 'Coordinator') { ?>
        <!-- Form to add extra expenses -->
        <div class="extra-expenses-container wrapper-fit-centered">
            <h2>Add Extra Expenses</h2>
            <form action="add_extra_expenses.php" method="POST">
                <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>">

                <label for="expense_assignment">Assignment:</label>
                <input type="text" name="expense_assignment" required placeholder="Enter assignment" maxlength="255">

                <label for="extra_expense">Extra Expense Amount:</label>
                <input type="text" name="extra_expense" required placeholder="Enter extra expense" pattern="^\d+(\.\d{1,2})?$">

                <button type="submit" class="btn">Add Expense</button>
            </form>
        </div>
        <br>
        <?php } ?>

        <div class="expense-table-container wrapper-fit-centered">
    <h2>Expenses</h2>
    <form action="update_extra_expenses.php" method="POST">
        <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>">

        <table class="table font-small-8">
            <thead>
                <tr class="sticky-header">
                    <th>Expense Type</th>
                    <th>Amount</th>
                    <th>Action</th> <!-- Added column for actions -->
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Food Expense</td>
                    <td><?php echo htmlspecialchars(number_format($foodExpenseTotal, 2)); ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Total Ticket Expense</td>
                    <td><?php echo htmlspecialchars(number_format($ticketsExpenseTotal, 2)); ?></td>
                    <td></td>
                </tr>
                
                <!-- Loop through extra expenses and display them -->
                <?php foreach ($extraExpenses as $expense): ?>
                    <tr>
                        <td>
                            <input type="text" name="expense_assignment[]" value="<?php echo htmlspecialchars($expense['expense_assignment']); ?>" required maxlength="255" readonly>
                        </td>
                        <td>
                            <input class="input-80" type="number" name="extra_expense[]" step="0.01" value="<?php echo htmlspecialchars(number_format($expense['expense_amount'], 2)); ?>" required readonly>
                        </td>
                        <td>
                            <!-- Delete button -->
                            <form action="delete_extra_expense.php" method="POST" style="display:inline;">
                                <input type="hidden" name="expense_id" value="<?php echo htmlspecialchars($expense['id']); ?>">
                                <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>"> <!-- Include group_id for redirection -->
                                <button type="submit" style="border:none;font-size:2em;color:red; background:none;">
                                    <i class="fa fa-times" aria-hidden="true"></i> <!-- Font Awesome delete icon -->
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <tr>
                    <td><strong>Total Expenses</strong></td>
                    <td>
                        <strong>
                            <?php 
                            // No need for separate extra expense calculation, just use the food and ticket totals
                            $totalExpenses = $foodExpenseTotal + $ticketsExpenseTotal + array_sum(array_column($extraExpenses, 'expense_amount'));
                            echo htmlspecialchars(number_format($totalExpenses, 2));
                            ?>
                        </strong>
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        
        <?php if ($userRole === 'Superadmin' || $userRole === 'Admin' || $userRole === 'Sales_Manager' || $userRole === 'Coordinator') { ?>
        <br>
        <button type="submit" class="btn">Update Expenses</button>
        <br>
        <a href="index.php">Back to Board</a>
        <?php } ?>
    </form>
</div>

    <?php include('inc/footer.php'); ?>