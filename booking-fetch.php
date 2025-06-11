<?php
include('inc/db.php'); // Ensure db.php is included for database connection

// Check if the type of fetch is specified
if (isset($_POST['type'])) {
    $type = $_POST['type'];

    if ($type == 'tour' && isset($_POST['search'])) {
        $query = $_POST['search'];

        try {
            // Fetch tour names based on the search query
            $stmt = $pdo->prepare("SELECT tour_name FROM tours WHERE tour_name LIKE :tour_name LIMIT 10");
            $stmt->execute(['tour_name' => '%' . $query . '%']);
            $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($tours) {
                foreach ($tours as $tour) {
                    echo '<div class="tour-result-item" data-tour="' . htmlspecialchars($tour['tour_name'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($tour['tour_name'], ENT_QUOTES, 'UTF-8') . '</div>';
                }
            } else {
                echo '<div class="tour-result-item">No tour names found</div>';
            }
        } catch (PDOException $e) {
            echo 'Error fetching tours: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }

    } elseif ($type == 'group' && isset($_POST['tour_name']) && isset($_POST['tour_date'])) {
        $tourName = $_POST['tour_name'];
        $tourDate = $_POST['tour_date']; // Capture the selected tour date

        try {
            // Query to fetch groups with detailed information
            $stmt = $pdo->prepare("SELECT g.group_id, g.tour_name, g.start_time, g.max_seats, g.time_display,
                                       (SELECT COALESCE(SUM(b.adult_no + b.child_no), 0) FROM booking b WHERE b.group_id = g.group_id) AS booked_seats,
                                       (SELECT COALESCE(SUM(b.adult_no), 0) FROM booking b WHERE b.group_id = g.group_id AND b.lang = 'Rus') AS rus_adults,
                                       (SELECT COALESCE(SUM(b.child_no), 0) FROM booking b WHERE b.group_id = g.group_id AND b.lang = 'Rus') AS rus_children,
                                       (SELECT COALESCE(SUM(b.adult_no), 0) FROM booking b WHERE b.group_id = g.group_id AND b.lang = 'Eng') AS eng_adults,
                                       (SELECT COALESCE(SUM(b.child_no), 0) FROM booking b WHERE b.group_id = g.group_id AND b.lang = 'Eng') AS eng_children
                                    FROM groups g 
                                    WHERE g.tour_name = :tour_name AND g.group_date = :tour_date;");
            $stmt->execute(['tour_name' => $tourName, 'tour_date' => $tourDate]);
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Always add "New" option as the first available option
            echo '<div class="available-tour-result-item" data-group="New">New</div>';

            if ($groups) {
                foreach ($groups as $group) {
                    $availableSeats = $group['max_seats'] - $group['booked_seats'];
                    $startTime = $group['start_time']; 
                    $displayTime = $group['time_display'] == 1 && $startTime !== NULL ? ' (' . date('H:i', strtotime($startTime)) . ')' : '';
            
                    echo '<div class="available-tour-result-item" data-group="' . htmlentities($group['group_id']) . '">'
                         . htmlentities($group['tour_name']) . $displayTime
                         . ' ' . htmlspecialchars($group['rus_adults'] + $group['rus_children']) . '/'
                         . htmlspecialchars($group['eng_adults'] + $group['eng_children']) . '/'
                         . htmlspecialchars($availableSeats) .
                         '</div>';
                }
            
            } else {
                // Display 'New' if no groups are found
                echo '<div class="available-tour-result-item" data-group="new">New</div>';
            }
        } catch (PDOException $e) {
            echo 'Error fetching groups: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    } elseif ($type == 'agent' && isset($_POST['search'])) {
        // Handle agent search
        $searchTerm = $_POST['search'];
        try {
            // Modify the query to exclude specific roles
            $stmt = $pdo->prepare("SELECT Id, First_Name, Last_Name
                                   FROM users
                                   WHERE (First_Name LIKE ? OR Last_Name LIKE ?)
                                   AND Role NOT IN ('Superadmin', 'Admin', 'Coordinator')");
            $stmt->execute(["%$searchTerm%", "%$searchTerm%"]);
            $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($agents) {
                foreach ($agents as $agent) {
                    echo '<div class="result-item" data-id="' . htmlentities($agent['Id']) . '">' . htmlentities($agent['First_Name'] . ' ' . $agent['Last_Name']) . '</div>';
                }
            } else {
                echo '<div class="result-item">No results found</div>';
            }
        } catch (PDOException $e) {
            echo 'Error fetching agents: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    } elseif ($type == 'country' && isset($_POST['search'])) {
        // Handle country search
        $searchTerm = $_POST['search'];
        try {
            $stmt = $pdo->prepare("SELECT country_name FROM country_list WHERE country_name LIKE ?");
            $stmt->execute(["%$searchTerm%"]);
            $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($countries) {
                foreach ($countries as $country) {
                    echo '<div class="country-result-item" data-country="' . htmlentities($country['country_name']) . '">' . htmlentities($country['country_name']) . '</div>';
                }
            } else {
                echo '<div class="country-result-item">No results found</div>';
            }
        } catch (PDOException $e) {
            echo 'Error fetching countries' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    } elseif ($type === 'expense') {
        $searchTerm = $_POST['search'];
        $stmt = $pdo->prepare("SELECT food_expense, tickets_expense, child_food_expense, child_tickets_expense FROM tours WHERE tour_name = ?");
        $stmt->execute([$searchTerm]);
        $expenses = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode($expenses); 
    }
}
exit;
?>