<?php

// Function to fetch data from the booking table, ordered by tour_date and include max_seats
// function fetchBookingData($pdo) {
//     try {
//         $sql = "SELECT b.*, g.max_seats, u.First_Name AS agent_first_name, u.Last_Name AS agent_last_name
//                 FROM booking b
//                 LEFT JOIN users u ON b.agent_id = u.Id
//                 LEFT JOIN groups g ON b.group_id = g.group_id
//                 ORDER BY b.tour_date ASC, b.tour_name ASC";  // Sorting by tour_date ascending, then tour_name ascending
//         $stmt = $pdo->prepare($sql);
//         $stmt->execute();
//         return $stmt->fetchAll(PDO::FETCH_ASSOC);
//     } catch (PDOException $e) {
//         throw new Exception("Error fetching booking data: " . $e->getMessage());
//     }
// }

function fetchBookingData($pdo) {
    try {
        $sql = "
            SELECT 
                b.*, 
                g.max_seats, 
                g.board_display,  -- Ensure board_display is included
                u.First_Name AS agent_first_name, 
                u.Last_Name AS agent_last_name,
                (SELECT MIN(b_inner.pickup_time)
                 FROM booking b_inner 
                 WHERE b_inner.group_id = b.group_id) AS earliest_pickup_time
            FROM 
                booking b
            LEFT JOIN 
                users u ON b.agent_id = u.Id
            LEFT JOIN 
                groups g ON b.group_id = g.group_id
            ORDER BY 
                b.tour_date ASC, b.tour_name ASC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error fetching booking data: " . $e->getMessage());
    }
}
// Function to fetch data from the 'groups' table, ordered by group_id or another relevant field
function fetchGroupsData($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM groups ORDER BY group_id DESC"); // Add sorting as needed
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error fetching groups data: " . $e->getMessage());
    }
}

// Function to fetch tours, ordered by tour date (assuming there’s a tour_date field)
function fetchToursData($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM tours"); // Removed the ORDER BY clause for tour_date
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error fetching tours data: " . $e->getMessage());
    }
}


// If you retrieve bookings by date range, make sure to include the ORDER BY clause there too
function fetchBookingDataByDateRange($pdo, $startDate, $endDate) {
    try {
        $sql = "
            SELECT 
                b.*, 
                u.First_Name AS agent_first_name, 
                u.Last_Name AS agent_last_name,
                t.food_expense AS adult_food_price,
                t.child_food_expense AS child_food_price,
                t.tickets_expense AS adult_ticket_price,
                t.child_tickets_expense AS child_ticket_price
            FROM 
                booking b
            LEFT JOIN users u ON b.agent_id = u.Id
            LEFT JOIN tours t ON b.tour_name = t.tour_name
            WHERE 
                b.booking_date BETWEEN :start_date AND :end_date
            ORDER BY 
                b.booking_date DESC, b.time DESC"; // Sort by date and time

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Error fetching booking data: " . $e->getMessage());
    }
}
