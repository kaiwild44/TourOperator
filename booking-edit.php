<?php
include('inc/header.php');

// Check user role for access control
if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager' && $_SESSION['role'] !== 'Online_Manager') {
    header("Location: access_denied.php");
    exit();
}

// Success message to indicate booking has been successfully added
if (isset($_SESSION['booking_success'])) {
    echo "<div class='msg-success text-center'>Booking successfully added!</div>";
    unset($_SESSION['booking_success']);
}

// Fetch booking ID from request
$bookingId = $_GET['id'] ?? null;

if (!$bookingId) {
    echo "No booking ID provided.";
    exit();
}

try {
    // Modify SQL query to include agent name retrieval
    $stmt = $pdo->prepare("SELECT b.*, 
            u.First_Name AS agent_first_name, 
            u.Last_Name AS agent_last_name
        FROM booking b
        LEFT JOIN users u ON b.agent_id = u.Id
        WHERE b.id = :id");
    $stmt->execute(['id' => $bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo "Booking not found.";
        exit();
    }
} catch (PDOException $e) {
    echo "Error fetching booking data: " . $e->getMessage();
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $agentId = $_POST['agent_id'] ?? null;
    // Capture form data
    $tourName = $_POST['tour_name'] ?? $booking['tour_name']; 
    $tourDate = $_POST['tour_date'] ?? $booking['tour_date'];
    $tourType = $_POST['tour_type'] ?? $booking['tour_type'];
    $firstName = $_POST['first_name'] ?? $booking['first_name'];
    $lastName = $_POST['last_name'] ?? $booking['last_name'];
    $lang = $_POST['lang'] ?? $booking['lang'];
    $bookingDate = $_POST['booking_date'] ?? $booking['booking_date']; // Ensure booking date is captured
    $pickupTime = $_POST['pickup_time'] ?? $booking['pickup_time'];
    $hotel = $_POST['hotel'] ?? $booking['hotel'];
    $roomNo = $_POST['room_no'] ?? $booking['room_no'];
    $phoneNo = $_POST['phone_no'] ?? $booking['phone_no'];
    $phoneNoExtra = $_POST['phone_no_extra'] ?? $booking['phone_no_extra'];
    $whatsapp = isset($_POST['whatsapp']) ? 1 : $booking['whatsapp'];
    $telegram = isset($_POST['telegram']) ? 1 : $booking['telegram'];
    $viber = isset($_POST['viber']) ? 1 : $booking['viber'];
    $wechat = isset($_POST['wechat']) ? 1 : $booking['wechat'];
    $email = $_POST['email'] ?? $booking['email'];
    $country = $_POST['country'] ?? $booking['country'];
    $city = $_POST['city'] ?? $booking['city'];
    $category = $_POST['category'] ?? $booking['category'];
    $adultNo = (int)($_POST['adult_no'] ?? $booking['adult_no']);
    $childNo = (int)($_POST['child_no'] ?? $booking['child_no']);
    $infantNo = (int)($_POST['infant_no'] ?? $booking['infant_no']);
    $adultFood = $_POST['adult_food'] ?? 0;
    $adultTickets = $_POST['adult_tickets'] ?? 0;
    $childFood = $_POST['child_food'] ?? 0;
    $childTickets = $_POST['child_tickets'] ?? 0;
    $price = (float)$_POST['price'] ?? $booking['price'];
    $paidCash = (float)$_POST['paid_cash'] ?? $booking['paid_cash'];
    $paidCard = (float)$_POST['paid_card'] ?? $booking['paid_card'];
    $paymentMethod = $_POST['payment_method'] ?? $booking['payment_method'];
    $food = isset($_POST['food']) ? 1 : 0;
    $tickets = isset($_POST['tickets']) ? 1 : 0;
    $note = $_POST['note'] ?? $booking['note'];
    // $agentId = $_POST['agent_id'] ?? $booking['agent_id'];
    $selectedGroupId = $_POST['selected_group_id'] ?? '';

    // Create a new group if needed
    if ($selectedGroupId === '' && $tourType === 'Group') { // New group needed for Groups
        try {
            // Generate a base group ID
            $suffix = $tourType === 'Private' ? 'P' : 'G';
            $baseGroupId = strtoupper(substr($tourName, 0, 3)) . $suffix . date('Ymd', strtotime($tourDate));

            // Function to generate a unique group ID, if not already defined
            function generateUniqueGroupId($baseGroupId, $pdo) {
                $i = 1;
                while (true) {
                    $newGroupId = $baseGroupId . '-' . $i;
                    $stmtCheckGroupId = $pdo->prepare("SELECT COUNT(*) FROM groups WHERE group_id = :group_id");
                    $stmtCheckGroupId->execute(['group_id' => $newGroupId]);
                    $exists = $stmtCheckGroupId->fetchColumn();

                    if ($exists == 0) {
                        return $newGroupId;
                    }
                    $i++;
                }
            }

            // Generate a unique group ID
            $newGroupId = generateUniqueGroupId($baseGroupId, $pdo);

            // Insert the new group into the database
            $stmtInsertGroup = $pdo->prepare("INSERT INTO groups (group_id, group_date, tour_name, max_seats) VALUES (:group_id, :group_date, :tour_name, :max_seats)");
            $stmtInsertGroup->execute([
                ':group_id' => $newGroupId,
                ':group_date' => $tourDate,
                ':tour_name' => $tourName,
                ':max_seats' => 18 // Default maximum seats
            ]);

            // Set the new group ID for booking update
            $selectedGroupId = $newGroupId;

        } catch (PDOException $e) {
            echo "Error creating new group: " . $e->getMessage();
            exit();
        }
    }

    // Prepare and execute the update query
    try {
        $stmt = $pdo->prepare("UPDATE booking SET 
            tour_name = :tour_name, 
            tour_date = :tour_date,
            tour_type = :tour_type,
            booking_date = :booking_date,
            first_name = :first_name, 
            last_name = :last_name, 
            lang = :lang,
            pickup_time = :pickup_time,
            hotel = :hotel,
            room_no = :room_no,
            phone_no = :phone_no,
            phone_no_extra = :phone_no_extra,
            whatsapp = :whatsapp,
            telegram = :telegram,
            viber = :viber,
            wechat = :wechat,
            email = :email,
            country = :country,
            city = :city,
            category = :category,
            adult_no = :adult_no,
            child_no = :child_no,
            infant_no = :infant_no,
            adult_food = :adult_food,
            adult_tickets = :adult_tickets,
            child_food = :child_food,
            child_tickets = :child_tickets,
            price = :price,
            paid_cash = :paid_cash,
            paid_card = :paid_card,
            payment_method = :payment_method,
            food = :food,
            tickets = :tickets,
            note = :note,
            agent_id = :agent_id,  -- Add this line to update agent ID
            group_id = :group_id
            WHERE id = :booking_id");
    
        $stmt->execute([
            ':tour_name' => $tourName,
            ':tour_date' => $tourDate,
            ':tour_type' => $tourType,
            ':booking_date' => $bookingDate,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':lang' => $lang,
            ':pickup_time' => $pickupTime,
            ':hotel' => $hotel,
            ':room_no' => $roomNo,
            ':phone_no' => $phoneNo,
            ':phone_no_extra' => $phoneNoExtra,
            ':whatsapp' => $whatsapp,
            ':telegram' => $telegram,
            ':viber' => $viber,
            ':wechat' => $wechat,
            ':email' => $email,
            ':country' => $country,
            ':city' => $city,
            ':category' => $category, 
            ':adult_no' => $adultNo,
            ':child_no' => $childNo,
            ':infant_no' => $infantNo,
            ':adult_food' => $adultFood,
            ':adult_tickets' => $adultTickets,
            ':child_food' => $childFood,
            ':child_tickets' => $childTickets,
            ':price' => $price,
            ':paid_cash' => $paidCash,
            ':paid_card' => $paidCard,
            ':payment_method' => $paymentMethod,
            ':food' => $food,
            ':tickets' => $tickets,
            ':note' => $note,
            ':agent_id' => $agentId,  // Ensure the agent ID is included
            ':group_id' => $selectedGroupId,
            ':booking_id' => $booking['id']
        ]);    
        
        $_SESSION['booking_success'] = true;
        header("Location: booking-edit.php?id=" . $bookingId);
        exit();

    } catch (PDOException $e) {
        echo "Error updating booking: " . $e->getMessage();
    }
}
?>

<style>
    /* Styling for dropdown results */
    #agent_results,
    #country_results,
    #tour_results,
    #group_results,
    #available_tours_results {
        border: 1px solid #ccc;
        max-height: 150px; /* Limit dropdown height */
        overflow-y: auto; /* Allow scrolling if content exceeds height */
        display: none; /* Initially hidden */
        position: absolute; /* Absolute positioning */
        background: white;
        z-index: 1000;
        box-sizing: border-box; /* Include padding/border in width/height */
        max-width: 100%; /* Full width but constrained by the parent */
        width: auto; /* Width based on content */
    }

    /* Result items styling */
    .result-item,
    .country-result-item,
    .tour-result-item,
    .group-result-item,
    .available-tour-result-item {
        padding: 8px; /* Padding for items */
        cursor: pointer; /* Pointer cursor on hover */
        white-space: nowrap; /* Prevent text wrapping */
    }

    /* Hover and selected state */
    .result-item:hover,
    .result-item.selected,
    .country-result-item:hover,
    .country-result-item.selected,
    .tour-result-item:hover,
    .tour-result-item.selected,
    .group-result-item:hover,
    .group-result-item.selected,
    .available-tour-result-item:hover,
    .available-tour-result-item.selected {
        background-color: #2266cc; /* Background for selected/hover */
        color: white; /* Text color for selected/hover */
    }
</style>

<div class="wrapper-fit-centered">
    <h2 class="my-20 text-center">Edit Booking</h2>
    <form method="post" action="" class="booking">
        <div>
            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['id'], ENT_QUOTES); ?>">
            <input type="hidden" name="selected_group_id" id="selected_group_id" value="<?php echo htmlspecialchars($booking['group_id'], ENT_QUOTES); ?>">
            <input type="hidden" name="voucher_no" id="voucher_no" value="<?php echo htmlspecialchars($booking['voucher_no'], ENT_QUOTES); ?>">
            <label for="tour_search">Tour Name:</label>
            <input type="text" id="tour_search" 
                placeholder="Type tour name..." 
                value="<?php echo htmlspecialchars($booking['tour_name'], ENT_QUOTES); ?>" 
                autocomplete="off">
            <div id="tour_results" style="display:none;"></div>
            <input type="hidden" name="tour_name" id="tour_name_hidden" value="<?php echo htmlspecialchars($booking['tour_name'], ENT_QUOTES); ?>">

            <label for="tour_type">Tour Type:</label>
            <select name="tour_type" id="tour_type">
            <option value="Group" <?php echo $booking['tour_type'] === 'Group' ? 'selected' : ''; ?>>Group</option>
            <option value="Private" <?php echo $booking['tour_type'] === 'Private' ? 'selected' : ''; ?>>Private</option>
            </select>
            
            <div class="yanyana">
                <div>
                    <label for="booking_date">Booking Date:</label>
                    <input type="date" name="booking_date" id="booking_date" class="w30" value="<?php echo htmlspecialchars($booking['booking_date'], ENT_QUOTES); ?>"><br>
                </div>
                <div>
                    <label for="tour_date"><span class="error-msg font-bold">* </span>Tour Date:</label>
                    <input type="date" name="tour_date" id="tour_date" class="w30" required value="<?php echo htmlspecialchars($booking['tour_date'], ENT_QUOTES); ?>"><br>
                </div>
            </div>

            <div>
            <label for="available_tours_search">Available Tours:</label>
                <input type="text" id="tour_search" placeholder="Type tour name..." value="<?php echo htmlspecialchars($booking['tour_name'], ENT_QUOTES); ?>" autocomplete="off">
                <div id="available_tours_results" style="display:none;"></div>
                <input type="hidden" name="available_tour_name" id="available_tour_name_hidden" value="">
            </div>

            <label for="first_name"><span class="error-msg font-bold">* </span>First Name:</label>
            <input type="text" name="first_name" id="first_name" required value="<?php echo htmlspecialchars($booking['first_name'], ENT_QUOTES); ?>"><br>

            <label for="last_name"><span class="error-msg font-bold">* </span>Last Name:</label>
            <input type="text" name="last_name" id="last_name" required value="<?php echo htmlspecialchars($booking['last_name'], ENT_QUOTES); ?>"><br>

            <label for="phone_no">Phone No:</label>
            <input type="tel" name="phone_no" id="phone_no" value="<?php echo htmlspecialchars($booking['phone_no'], ENT_QUOTES); ?>"><br>

            <label for="phone_no_extra">Extra Phone No:</label>
            <input type="tel" name="phone_no_extra" id="phone_no_extra" value="<?php echo htmlspecialchars($booking['phone_no_extra'], ENT_QUOTES); ?>"><br>

                        <div class="messenger-check-col-container">
                <div class="messenger-check">
                    <div>
                        <input type="checkbox" name="whatsapp" id="whatsapp" value="1" <?php echo $booking['whatsapp'] ? 'checked' : ''; ?>>&nbsp;
                        <label for="whatsapp">WhatsApp</label>
                    </div>

                    <div>
                        <input type="checkbox" name="telegram" id="telegram" value="1" <?php echo $booking['telegram'] ? 'checked' : ''; ?>>&nbsp;
                        <label for="telegram">Telegram</label>
                    </div>
                </div>
                <div class="messenger-check">
                    <div>
                        <input type="checkbox" name="viber" id="viber" value="1" <?php echo $booking['viber'] ? 'checked' : ''; ?>>&nbsp;
                        <label for="viber">Viber</label>
                    </div>
                    <div>
                        <input type="checkbox" name="wechat" id="wechat" value="1" <?php echo $booking['wechat'] ? 'checked' : '';                         ?>>&nbsp;
                        <label for="wechat">WeChat</label>
                    </div>
                </div>
            </div><br>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($booking['email'], ENT_QUOTES); ?>"><br>
        </div>
        <div>
            <label for="lang">Language:</label>
            <select name="lang" id="lang">
            <option value="Eng" <?php echo $booking['lang'] == 'Eng' ? 'selected' : ''; ?>>English</option>
            <option value="Rus" <?php echo $booking['lang'] == 'Rus' ? 'selected' : ''; ?>>Russian</option>
            </select>

            <label for="adult_no">Adult No:</label>
            <input type="number" name="adult_no" id="adult_no" value="<?php echo htmlspecialchars($booking['adult_no'], ENT_QUOTES); ?>"><br>

            <label for="child_no">Child No:</label>
            <input type="number" name="child_no" id="child_no" value="<?php echo htmlspecialchars($booking['child_no'], ENT_QUOTES); ?>"><br>

            <label for="infant_no">Infant No:</label>
            <input type="number" name="infant_no" id="infant_no" value="<?php echo htmlspecialchars($booking['infant_no'], ENT_QUOTES); ?>"><br>

            <div class="dorddene">
    <div>
        <label for="adult_food"><i class="fas fa-user"></i> <i class="fas fa-utensils"></i></label>
        <input type="text" name="adult_food" id="adult_food" value="<?php echo htmlspecialchars($booking['adult_food'], ENT_QUOTES); ?>"><br>
    </div>
    <div>    
        <label for="adult_tickets"><i class="fas fa-user"></i> <i class="fas fa-ticket-alt"></i></label>
        <input type="text" name="adult_tickets" id="adult_tickets" value="<?php echo htmlspecialchars($booking['adult_tickets'], ENT_QUOTES); ?>"><br>
    </div>
    <div>   
        <label for="child_food"><i class="fas fa-child"></i> <i class="fas fa-utensils"></i></label>
        <input type="text" name="child_food" id="child_food" value="<?php echo htmlspecialchars($booking['child_food'], ENT_QUOTES); ?>"><br>
    </div>
    <div>  
        <label for="child_tickets"><i class="fas fa-child"></i> <i class="fas fa-ticket-alt"></i></label>
        <input type="text" name="child_tickets" id="child_tickets" value="<?php echo htmlspecialchars($booking['child_tickets'], ENT_QUOTES); ?>"><br>
    </div>
</div>

            <p class="food">Food/Tickets Incl/Excl.</p>
            <div class="food-ticket-check-col-container">
                <input type="checkbox" name="food" id="food" value="1" <?php echo $booking['food'] ? 'checked' : ''; ?>>&nbsp;
                <label for="food">Food</label>&nbsp;

                <input type="checkbox" name="tickets" id="tickets" value="1" <?php echo $booking['tickets'] ? 'checked' : ''; ?>>&nbsp;
                <label for="tickets">Tickets</label>
            </div><br>

            <label for="hotel"><span class="error-msg font-bold">* </span>Pickup Location (Hotel):</label>
            <input type="text" name="hotel" id="hotel" required value="<?php echo htmlspecialchars($booking['hotel'], ENT_QUOTES); ?>"><br>

            <label for="room_no">Room No:</label>
            <input type="text" name="room_no" id="room_no" value="<?php echo htmlspecialchars($booking['room_no'], ENT_QUOTES); ?>"><br>

            <label for="pickup_time">Pickup Time:</label>
            <input type="text" name="pickup_time" id="pickup_time" value="<?php echo htmlspecialchars($booking['pickup_time'], ENT_QUOTES); ?>"><br>
        </div>
        <div>
            <label for="payment_method">Payment Method:</label>
            <select name="payment_method" id="payment_method">
                <option value="Cash" <?php echo $booking['payment_method'] == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                <option value="Card" <?php echo $booking['payment_method'] == 'Card' ? 'selected' : ''; ?>>Card</option>
                <option value="Card-Cash" <?php echo $booking['payment_method'] == 'Card-Cash' ? 'selected' : ''; ?>>Card+Cash</option>
            </select><br>

            <label for="price">Price:</label>
            <input type="text" name="price" id="price" value="<?php echo htmlspecialchars($booking['price'], ENT_QUOTES); ?>"><br>

            <label for="paid_cash">Paid (Cash):</label>
            <input type="text" name="paid_cash" id="paid_cash" value="<?php echo htmlspecialchars($booking['paid_cash'], ENT_QUOTES); ?>"><br>

            <label for="paid_card">Paid (Card):</label>
            <input type="text" name="paid_card" id="paid_card" value="<?php echo htmlspecialchars($booking['paid_card'], ENT_QUOTES); ?>"><br>

            <label for="country_search">Country:</label>
            <input type="text" id="country_search" placeholder="Type a country name..." value="<?php echo htmlspecialchars($booking['country'], ENT_QUOTES); ?>" autocomplete="off">
            <div id="country_results" style="display:none;"></div>
            <input type="hidden" name="country" id="country" value="<?php echo htmlspecialchars($booking['country'], ENT_QUOTES); ?>">

            <label for="city">City:</label>
            <input type="text" name="city" id="city" value="<?php echo htmlspecialchars($booking['city'], ENT_QUOTES); ?>"><br>

            <label for="category">Category:</label>
            <select name="category" id="category">
                <option value="Promoter" <?php echo $booking['category'] == 'Promoter' ? 'selected' : ''; ?>>Promoter</option>
                <option value="Street" <?php echo $booking['category'] == 'Street' ? 'selected' : ''; ?>>Street</option>
                <option value="Hotel" <?php echo $booking['category'] == 'Hotel' ? 'selected' : ''; ?>>Hotel</option>
                <option value="Online" <?php echo $booking['category'] == 'Online' ? 'selected' : ''; ?>>Online</option>
                <option value="Multiday" <?php echo $booking['category'] == 'Multiday' ? 'selected' : ''; ?>>Multiday</option>
                <option value="Web" <?php echo $booking['category'] == 'Web' ? 'selected' : ''; ?>>Web</option>
                <option value="localpartner" <?php echo $booking['category'] == 'localpartner' ? 'selected' : ''; ?>>Loc. Partn</option>
                <option value="intpartner" <?php echo $booking['category'] == 'intpartner' ? 'selected' : ''; ?>>Int. Partn</option>
                <option value="walkin" <?php echo $booking['category'] == 'walkin' ? 'selected' : ''; ?>>Walk-in</option>
            </select><br>

            <div>
                <label for="agent_search">Agent:</label>
                <input type="text" id="agent_search" placeholder="Type agent's name..." 
                    value="<?php echo htmlspecialchars(($booking['agent_first_name'] ?? '') . ' ' . ($booking['agent_last_name'] ?? ''), ENT_QUOTES); ?>" 
                    autocomplete="off">
                <div id="agent_results" style="display:none;"></div>
                <input type="hidden" name="agent_id" id="agent_id" value="<?php echo htmlspecialchars($booking['agent_id'] ?? '', ENT_QUOTES); ?>">
            </div>

            <label for="note">Note:</label>
            <textarea name="note" id="note" rows="2" cols="31"><?php echo htmlspecialchars($booking['note'], ENT_QUOTES); ?></textarea><br>

            <input type="submit" value="Update Booking" class="btn">
        </div>
    </form>
</div>

<?php include('inc/footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Add the tour_type change event handler here
    $('#tour_type').on('change', function() {
        const selectedTourType = $(this).val(); 

        if (selectedTourType === 'Private') {
            // Disable group selection
            $('#available_tours_search').prop('disabled', true);

            // Clear any existing selections
            $('#available_tours_search').val('New'); // Set 'New' as the only option

            // Hide any existing group results
            $('#available_tours_results').hide(); 
        } else if (selectedTourType === 'Group') { 
            // Enable tour name, date, and group selection for Group tours
            $('#tour_search').prop('disabled', false);
            $('#tour_date').prop('disabled', false); 
            $('#available_tours_search').prop('disabled', false); 

            // If tour name is already selected, re-fetch groups
            const tourName = $('#tour_name_hidden').val();
            if (tourName) {
                const selectedDate = $('#tour_date').val();
                if (selectedDate) {
                    fetchGroupsForTour(tourName); 
                }
            }
        }
    });

    let selectedAgentIndex = -1; // Track selected agent index
    let selectedCountryIndex = -1; // Track selected country index
    let selectedTourIndex = -1; // Track selected tour index
    let selectedGroupIndex = -1; // Track selected group index
    let selectedIndex = -1; // Track selected index for focused input

    // Declare expense variables
    let adultFoodExpense = 0;
    let adultTicketsExpense = 0;
    let childFoodExpense = 0;
    let childTicketsExpense = 0;

    // Function to fetch all tour names
    function fetchAllTours() {
        $.ajax({
            url: 'booking-fetch.php', // Script to fetch all tours
            type: 'POST',
            data: { search: '', type: 'tour' }, // Fetch tours with an empty search
            success: function(response) {
                $('#tour_results').html(response).show(); // Show results
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + status + ': ' + error);
            }
        });
    }

    // General search function for various fields
    function search(inputId, resultsId, type) {
        const query = $(inputId).val();
        if (query.length > 1) {
            $.ajax({
                url: 'booking-fetch.php',
                type: 'POST',
                data: { search: query, type: type },
                success: function(response) {
                    $(resultsId).html(response).show();
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: " + status + ': ' + error);
                }
            });
        } else {
            $(resultsId).hide();
        }
    }

    // Show all tours when the input is focused
    $('#tour_search').on('focus', function() {
        fetchAllTours();
    });

    // Event for searching tours
    $('#tour_search').on('input', function() {
        const query = $(this).val();
        if (query.length > 0) {
            search('#tour_search', '#tour_results', 'tour');
        } else {
            $('#tour_results').hide(); // Hide if input is empty
        }
    });

    // Event for searching countries
    $('#country_search').on('input', function() {
        search('#country_search', '#country_results', 'country');
    });

    // Handle tour selection
    $(document).on('click', '.tour-result-item', function() {
        const selectedTour = $(this).text(); 
        $('#tour_search').val(selectedTour); // Set selected tour in input
        $('#tour_name_hidden').val(selectedTour); // Hidden input
        $('#tour_results').hide(); // Hide results

        // Fetch expenses for the selected tour
        fetchExpenses(selectedTour); 
        
        // After selecting the tour, check if a date is already selected
        const selectedDate = $('#tour_date').val();
        if (selectedDate) {
            fetchGroupsForTour(selectedTour); // Fetch groups if a date is already set
        }

        function fetchExpenses(tourName) {
            $.ajax({
                url: 'booking-fetch.php',
                type: 'POST',
                data: { search: tourName, type: 'expense' },
                success: function(response) {
                    const expenses = JSON.parse(response);
                    adultFoodExpense = parseFloat(expenses.food_expense) || 0;
                    adultTicketsExpense = parseFloat(expenses.tickets_expense) || 0;
                    childFoodExpense = parseFloat(expenses.child_food_expense) || 0;
                    childTicketsExpense = parseFloat(expenses.child_tickets_expense) || 0;

                    $('#adult_no, #child_no, #food, #tickets').on('input change', function() {
                    const adultCount = parseInt($('#adult_no').val()) || 0;
                    const childCount = parseInt($('#child_no').val()) || 0;

                    $('#adult_food').val(Math.round(adultCount * ($('#food').is(':checked') ? adultFoodExpense : 0)));
                    $('#adult_tickets').val(Math.round(adultCount * ($('#tickets').is(':checked') ? adultTicketsExpense : 0)));
                    $('#child_food').val(Math.round(childCount * ($('#food').is(':checked') ? childFoodExpense : 0)));
                    $('#child_tickets').val(Math.round(childCount * ($('#tickets').is(':checked') ? childTicketsExpense : 0)));
            });
                    calculateExpenses(); 
                }
            });
        }

        // Function to calculate expenses
        function calculateExpenses() {
            const adultCount = parseInt($('#adult_no').val()) || 0;
            const childCount = parseInt($('#child_no').val()) || 0;

            $('#adult_food').val(Math.round(adultCount * ($('#food').is(':checked') ? adultFoodExpense : 0)));
            $('#adult_tickets').val(Math.round(adultCount * ($('#tickets').is(':checked') ? adultTicketsExpense : 0)));
            $('#child_food').val(Math.round(childCount * ($('#food').is(':checked') ? childFoodExpense : 0)));
            $('#child_tickets').val(Math.round(childCount * ($('#tickets').is(':checked') ? childTicketsExpense : 0)));
        }

        // Trigger calculation on input changes
        $('#adult_no, #child_no, #food, #tickets').on('input change', calculateExpenses); 

        // Trigger calculation on initial load
        calculateExpenses(); 
    });

    // Handle country selection
    $(document).on('click', '.country-result-item', function() {
        const selectedCountry = $(this).text();
        $('#country_search').val(selectedCountry); 
        $('#country').val(selectedCountry); 
        $('#country_results').hide(); 
        selectedCountryIndex = -1; 
    });

    // Handle country selection
    $(document).on('click', '.country-result-item', function() {
        const selectedCountry = $(this).text();
        $('#country_search').val(selectedCountry); 
        $('#country').val(selectedCountry); 
        $('#country_results').hide(); 
        selectedCountryIndex = -1; 
    });

    // Handle date change for group fetching
    $('#tour_date').on('change', function() {
        const selectedDate = $(this).val(); 
        const tourName = $('#tour_name_hidden').val(); 

        if (selectedDate) { 
            // If a date is selected, check if tourName is available 
            if (tourName) { 
                fetchGroupsForTour(tourName); // Fetch groups if tour is already selected
            } else {
                // If tourName is not selected, clear available tours 
                $('#available_tours_search').val(''); 
                $('#available_tours_results').hide(); 
            }
        } 
    });

    // Function to fetch groups based on selected tour
    function fetchGroupsForTour(tourName) {
        const selectedDate = $('#tour_date').val(); // Get the selected tour date
        $.ajax({
            url: 'booking-fetch.php', // Combined script
            type: 'POST',
            data: { tour_name: tourName, tour_date: selectedDate, type: 'group' }, // Fetch groups based on selected tour name and date
            success: function(response) {
                $('#available_tours_results').html(response).show(); // Show available groups
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: " + status + ': ' + error);
            }
        });
    }

    // Handle selection of available tours/groups
    $(document).on('click', '.available-tour-result-item', function() {
        const selectedGroup = $(this).text(); // Get text of the selected group
        $('#available_tours_search').val(selectedGroup); // Set selected group in input
        $('#available_tours_results').hide(); // Hide results
        $('#available_tour_name_hidden').val(selectedGroup); // Set hidden input with selected group

        // Check if 'New' is selected
        if (selectedGroup === 'New') {
            $('#selected_group_id').val(''); // Prepare for new group ID generation
        } else {
            // Use the data attribute for existing group ID
            $('#selected_group_id').val($(this).data('group')); // Assuming data-group holds the group ID
        }
    });

    // Handle focus event for available tours search input
    $('#available_tours_search').on('focus', function() {
        const tourName = $('#tour_name_hidden').val(); 
        const selectedDate = $('#tour_date').val(); 

        if (tourName && selectedDate) { 
            // Fetch groups if both tour name and date are selected
            fetchGroupsForTour(tourName);
        }
    });

    // Handle clicking outside of available tours results
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#available_tours_results').length && e.target.id !== 'available_tours_search') {
            $('#available_tours_results').hide(); // Hide available tours results
        }
    });

    // Keyboard navigation for tour results
    $('#tour_search').on('keydown', function(e) {
        const results = $('#tour_results .tour-result-item');
        if (e.key === "ArrowDown") {
            e.preventDefault();
            selectedTourIndex = (selectedTourIndex + 1) % results.length; // Navigate down
            updateSelection(results, selectedTourIndex); // Update highlight for navigation
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            selectedTourIndex = (selectedTourIndex - 1 + results.length) % results.length; // Navigate up
            updateSelection(results, selectedTourIndex); // Update highlight for navigation
        } else if (e.key === "Enter") {
            e.preventDefault();
            if (selectedTourIndex >= 0 && selectedTourIndex < results.length) {
                results.eq(selectedTourIndex).click(); // Trigger click on the selected item
            }
        }
    });

    // Keyboard navigation for available tours results
    $('#available_tours_search').on('keydown', function(e) {
        const results = $('#available_tours_results .available-tour-result-item');
        if (e.key === "ArrowDown") {
            e.preventDefault();
            selectedIndex = (selectedIndex + 1) % results.length; // Navigate down
            updateSelection(results, selectedIndex); // Update highlight for navigation
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            selectedIndex = (selectedIndex - 1 + results.length) % results.length; // Navigate up
            updateSelection(results, selectedIndex); // Update highlight for navigation
        } else if (e.key === "Enter") {
            e.preventDefault();
            if (selectedIndex >= 0 && selectedIndex < results.length) {
                results.eq(selectedIndex).click(); // Trigger click on the selected item
            }
        }
    });

    // Function to highlight the selected item in the dropdown
    function updateSelection(results, index) {
        results.removeClass('selected'); // Clear previous selection
        if (index >= 0 && index < results.length) {
            results.eq(index).addClass('selected'); // Highlight the selected option
        }
    }

    // Hide dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#tour_results').length && e.target.id !== 'tour_search') {
            $('#tour_results').hide(); // Hide tour results
        }
        if (!$(e.target).closest('#available_tours_results').length && e.target.id !== 'available_tours_search') {
            $('#available_tours_results').hide(); // Hide available tours results
        }
        // Hiding other dropdowns for agent and country
        if (!$(e.target).closest('#agent_results').length && e.target.id !== 'agent_search') {
            $('#agent_results').hide(); // Hide agent results
        }
        if (!$(e.target).closest('#country_results').length && e.target.id !== 'country_search') {
            $('#country_results').hide(); // Hide country results
        }
    });
});
$(document).ready(function() {
    // Function to toggle values based on checkbox status
    function toggleFoodTickets() {
        const foodChecked = $('#food').is(':checked');
        const ticketsChecked = $('#tickets').is(':checked');

        // Check if food is checked, show/hide corresponding input values
        if (foodChecked) {
            $('#adult_food').val("<?php echo htmlspecialchars($booking['adult_food'], ENT_QUOTES); ?>");
            $('#child_food').val("<?php echo htmlspecialchars($booking['child_food'], ENT_QUOTES); ?>");
        } else {
            $('#adult_food').val('');
            $('#child_food').val('');
        }

        // Check if tickets is checked, show/hide corresponding input values
        if (ticketsChecked) {
            $('#adult_tickets').val("<?php echo htmlspecialchars($booking['adult_tickets'], ENT_QUOTES); ?>");
            $('#child_tickets').val("<?php echo htmlspecialchars($booking['child_tickets'], ENT_QUOTES); ?>");
        } else {
            $('#adult_tickets').val('');
            $('#child_tickets').val('');
        }
    }

    // Initial toggle on page load
    toggleFoodTickets();

    // Bind change event to the food and tickets checkboxes
    $('#food').change(toggleFoodTickets);
    $('#tickets').change(toggleFoodTickets);
});

// Function to fetch all agents (similar to fetching all tours)
function fetchAllAgents() {
    $.ajax({
        url: 'booking-fetch.php', // Use the centralized fetch script
        type: 'POST',
        data: { type: 'agent', search: '' }, // Empty search fetches all agents
        success: function(response) {
            $('#agent_results').html(response).show(); // Show initial load of agents
        },
        error: function(xhr, status, error) {
            console.error("Error fetching agents: " + status + ': ' + error);
        }
    });
}

$(document).ready(function() {
    // Initialize with focus event to show all agents
    $('#agent_search').on('focus', function() {
        fetchAllAgents(); // Load and display agents when focusing on input
    });

    // Handle input to filter agent results
    $('#agent_search').on('input', function() {
        const query = $(this).val();
        if (query.length > 1) { // Start filtering when input is more than 1 character
            $.ajax({
                url: 'booking-fetch.php',
                type: 'POST',
                data: { type: 'agent', search: query },
                success: function(response) {
                    $('#agent_results').html(response).show(); // Show filtered results
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching agents: " + status + ': ' + error);
                }
            });
        } else {
            $('#agent_results').hide(); // Hide if less than 2 characters
        }
    });

    // Handle agent selection from results
    $(document).on('click', '.result-item', function() {
        const selectedAgent = $(this).text();
        $('#agent_search').val(selectedAgent); // Set selected agent name in input
        $('#agent_id').val($(this).data('id')); // Set agent ID for form submission
        $('#agent_results').hide(); // Hide dropdown
    });
});
</script>