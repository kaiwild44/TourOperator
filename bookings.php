<?php
include('inc/header.php');

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager' && $_SESSION['role'] !== 'Online_Manager') {
    header("Location: access_denied.php");
    exit();
}

try {
    // Adjust SQL query to exclude the non-existing expense fields
    $sql = "
    SELECT b.*, 
           u.First_Name AS agent_first_name, 
           u.Last_Name AS agent_last_name
    FROM booking b
    LEFT JOIN users u ON b.agent_id = u.Id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $bookings = $stmt->fetchAll(); 

} catch (PDOException $e) {
    // Handle any errors
    echo "Error: " . htmlspecialchars($e->getMessage());
}

// Fetch default data for the current month initially
$currentYear = date('Y');
$currentMonth = date('m');
$startDate = "$currentYear-$currentMonth-01";
$endDate = date("Y-m-t", strtotime($startDate));
$bookingData = fetchBookingDataByDateRange($pdo, $startDate, $endDate);
?>

<style>
    .tooltip {
    position: relative;
    display: inline-block;
}

.tooltip .tooltip-content {
    position: absolute;
    background-color: #333;
    color: #fff;
    padding: 10px;
    border-radius: 5px;
    z-index: 200;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    opacity: 1; /* Make it always visible when opened */
    transition: opacity 0.3s;
    pointer-events: all; /* Allow interactions within the tooltip */
    max-width: 300px;  
}

.tooltip:hover .tooltip-content {
    opacity: 1;
}

.note-icon {
    /* Add any necessary styling for the icon */
    cursor: pointer;
}
</style>

<h1>Bookings</h1>
<div class="wrapper">
<?php
// Check and display the booking success message
if (isset($_SESSION['booking_success'])) {
    echo "<div class='msg msg-success'>Booking successfully added!</div>";
    unset($_SESSION['booking_success']); // Clear session variable
}
?>
    <button id="filters-btn" class="btn">Filters</button><br>
    <div class="filter-section" id="filter-section">
        <button id="clear-filters-btn" class="btn">Clear Filters</button><br>
        <input type="date" id="start_date"><br>
        <input type="date" id="end_date"><br>
        <input type="date" id="booking_start_date"><br>
        <input type="date" id="booking_end_date"><br>
        <input type="text" id="first_name" placeholder="First Name"><br>
        <input type="text" id="last_name" placeholder="Last Name"><br>
        <input type="text" id="tour_name" placeholder="Tour Name"><br>
        <input type="text" id="agent_first_name" placeholder="Agent"><br>
        <input type="hidden" id="agent_last_name" placeholder="Filter by Agent Last Name"><br>
        <input type="text" id="tour_lang" placeholder="Language"><br>
                <input type="text" id="phone" placeholder="Phone No"><br>
        <input type="text" id="hotel" placeholder="Pickup Location"><br>
        <input type="text" id="date_range" placeholder="Select Date Range" style="display: none;"><br>
        <select id="month_filter">
            <option value="1">Current month</option>
            <option value="2">2 months</option>
            <option value="3">3 months</option>
            <option value="6">6 months</option>
            <option value="12">12 months</option>
            <option value="24">24 months</option>
        </select><br>
    </div>
    <div class="table-container">
        <table class="table font-small-8">
            <thead>
                <tr class="sticky-header">
                    <!-- <th>Group ID</th> -->
                    <th>Voucher</th>
                    <th>Booking Date</th>
                    <th>Tour Name</th>
                    <th>Type / Lang</th> 
                    <th>Tour Date</th>
                    <th>Pickup</th>
                    <th>Full Name</th>
                    <th>Hotel</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Adl/Chl/Inf</th>
                    <th>Food/Tkts</th>
                    <th>Price</th>
                    <th>Paid</th>
                    <th>Bal</th>
                    <th>Net Price</th>
                    <th>Agent</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="table-data">
                <?php 
                $count = 0;
                foreach ($bookingData as $row): 
                    $rowClass = ($count % 2 === 0) ? 'even-row' : 'odd-row';
                    $count++;
                ?>
                <tr class="<?php echo $rowClass; ?>">
                    <!-- <td><?php echo $row['group_id']; ?></td> -->
                        <td><?php 
                            // Category display logic
                            $category_code = '';
                            switch ($row['category']) {
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
                                    $category_code = '<span style="background-color: #50C878; padding: 2px; color: white;">O</span>';
                                    break;
                                case 'Web':
                                    $category_code = '<span style="background-color: #FFA500; padding: 2px; color: white;">W</span>';
                                    break;
                                case 'Multiday':
                                    $category_code = '<span style="background-color: #FF77FF; padding: 2px; color: white;">M</span>';
                                    break;
                                default:
                                    $category_code = $row['category']; // If no match, display category name
                                    break;
                            }
                            echo $category_code . " " . htmlspecialchars($row['voucher_no']); // Concatenate with the voucher number
                        ?></td>
                    <td><?php echo $row['booking_date']; ?></td>
                    <td><?php echo $row['tour_name']; ?></td>
                    <td><?php echo $row['tour_type'] . ' / ' . $row['lang']; ?></td>
                    <td><?php echo $row['tour_date']; ?></td>
                    <td><?php echo $row['pickup_time']; ?></td>
                    <td>
                        <?php echo $row['first_name'] . ' ' . $row['last_name']; ?>
                        <?php if (!empty($row['note'])): ?>
                        <span class="tooltip">
                            <i class="fa fa-info-circle note-icon" 
                                onclick="toggleTooltip(this, 'noteTooltip-<?php echo $row['id']; ?>')"></i>
                            <div class="tooltip-content" id="noteTooltip-<?php echo $row['id']; ?>">
                                <?php echo $row['note']; ?>
                            </div>
                        </span>
                        <?php endif; ?>                        
                    </td>
                    <td>
                        <?php echo $row['hotel']; ?>
                        <?php if (!empty($row['room_no'])): ?>
                        <span class="tooltip">
                            <i class="fa fa-info-circle" 
                                data-room="<?php echo $row['room_no']; ?>"
                                onclick="toggleTooltip(this, 'roomTooltip-<?php echo $row['id']; ?>')"></i>
                            <div class="tooltip-content" id="roomTooltip-<?php echo $row['id']; ?>">
                                Room Number: <?php echo $row['room_no']; ?>
                            </div>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $row['phone_no']; ?>
                        
                        <?php if (!empty($row['phone_no_extra'])): ?>
                        <span class="tooltip">
                            <i class="fa fa-mobile-alt" 
                                onclick="toggleTooltip(this, 'phoneTooltip-<?php echo $row['id']; ?>')"></i>
                            <div class="tooltip-content" id="phoneTooltip-<?php echo $row['id']; ?>">
                                <?php echo $row['phone_no_extra']; ?>
                            </div>
                        </span>
                        <?php endif; ?>

                        <?php 
                        // Initialize messaging platforms array
                        $messagingPlatforms = array();
                        $phone_number = $row['phone_no'];

                        // Check for each messaging platform
                        if ($row['whatsapp'] == 1) {
                            $whatsappMessage = "Hello, dear " . $row['first_name'] . ' ' . $row['last_name'] . ". Thank you for choosing Azerbaijan Travel International!";
                            $messagingPlatforms[] = '<a href="https://wa.me/' . $phone_number . '?text=' . urlencode($whatsappMessage) . '" target="_blank">WhatsApp</a>';
                        }
                        if ($row['viber'] == 1) {
                            $messagingPlatforms[] = '<a href="viber://pa?chatURI=' . $phone_number . '" target="_blank">Viber</a>';
                        }
                        if ($row['wechat'] == 1) {
                            $messagingPlatforms[] = '<a href="https://wechat.com/' . $phone_number . '" target="_blank">WeChat</a>';
                        }
                        if ($row['telegram'] == 1) {
                            $messagingPlatforms[] = '<a href="https://t.me/' . $phone_number . '" target="_blank">Telegram</a>';
                        }   

                        // Output the messaging platforms if any are available
                        if (!empty($messagingPlatforms)) {
                            echo ' <span class="tooltip">';
                            echo ' <i class="fa fa-info-circle" 
                                onclick="toggleTooltip(this, \'messagingTooltip-' . $row['id'] . '\')"></i>';
                            echo ' <div class="tooltip-content" id="messagingTooltip-' . $row['id'] . '">';
                            echo implode(', ', $messagingPlatforms);
                            echo '</div>';
                            echo '</span>';
                        }
                        ?>
                    </td>

                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['adult_no'] . ' / ' . $row['child_no'] . ' / ' . $row['infant_no']; ?></td>
                    <td>
                        <?php 
                        // Combine food and ticket amounts without breaking them down further
                        $totalFood = number_format($row['adult_food'] + $row['child_food'], 2); // Total food 
                        $totalTickets = number_format($row['adult_tickets'] + $row['child_tickets'], 2); // Total tickets
                        
                        // Output combined food and ticket amounts separated by a backslash
                        echo "$totalFood \\ $totalTickets";
                        ?>
                    </td>
                    <td><?php echo number_format($row['price'], 0); ?></td>
                    <td><?php echo $row['paid_cash'] + $row['paid_card']; ?></td>
                    <td><?php echo number_format($row['balance_due'], 0); ?></td>
                    <td>
                        <?php 
                        // Calculate the net price based on price and total food/ticket costs
                        $netPrice = $row['price'] - ($row['adult_food'] + $row['child_food'] + $row['adult_tickets'] + $row['child_tickets']);
                        echo number_format($netPrice, 2); 
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars(($row['agent_first_name'] ?? 'N/A') . ' ' . ($row['agent_last_name'] ?? 'N/A')); ?></td>
                    <td>
                        <a href="booking-edit.php?id=<?php echo $row['id']; ?>"><i class="fa fa-edit" style="color:blue">&nbsp;&nbsp;</i></a>
                        <a href="booking-duplicate.php?id=<?php echo $row['id']; ?>"><i class="fa fa-copy" style="color:green">&nbsp;&nbsp;</i></a>
                        <a href="booking-delete.php?id=<?php echo $row['id']; ?>"><i class="fa fa-trash" style="color:red">&nbsp;&nbsp;</i></a>
                        <a href="booking-pdf.php?id=<?php echo $row['id']; ?>"><i class="fa fa-ticket" style="color:gray"></i></a>
                    </td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Store the currently open tooltip ID
    var openTooltipId = null;

    function toggleTooltip(iconElement, tooltipId) {
        var tooltip = document.getElementById(tooltipId);
        // If the tooltip is already open, reset it
        if (openTooltipId === tooltipId) {
            // Keep the tooltip open
            return; // Exit function — do not close it
        } else {
            // Close the previously opened tooltip
            if (openTooltipId) {
                var previousTooltip = document.getElementById(openTooltipId);
                previousTooltip.style.display = 'none'; // Hide the previous tooltip
            }
            // Show the new tooltip
            tooltip.style.display = 'block';
            openTooltipId = tooltipId; // Set the currently open tooltip
        }
    }

    // Close all tooltips when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.tooltip')) {
            if (openTooltipId) {
                var tooltip = document.getElementById(openTooltipId);
                tooltip.style.display = 'none'; // Hide the tooltip if clicked outside
                openTooltipId = null; // Reset the open tooltip ID
            }
        }
    });
</script>

<script>
    // Function to fetch bookings based on the selected date range
    function fetchBookings() {
        var startDate = document.getElementById('start_date').value;
        var endDate = document.getElementById('end_date').value;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'fetch-date-range.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        // Prepare parameters to send
        var params = 'start_date=' + encodeURIComponent(startDate) +
                     '&end_date=' + encodeURIComponent(endDate);

        xhr.onload = function() {
            if (this.status === 200) {
                document.getElementById('table-data').innerHTML = this.responseText; // Update the table
            }
        };

        xhr.send(params); // Send the AJAX request
    }

    // Add event listeners for the date inputs
    document.getElementById('start_date').addEventListener('change', fetchBookings);
    document.getElementById('end_date').addEventListener('change', fetchBookings);
</script>

<?php include('inc/footer.php'); ?>


