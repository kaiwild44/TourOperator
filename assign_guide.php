<?php
include('inc/db.php');
include('inc/functions.php');

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch posted data
    $group_id = $_POST['group_id'];
    $guide_id = $_POST['guide_id'];
    $guide_lang = $_POST['guide_lang'];
    $second_guide_id = isset($_POST['second_guide_id']) ? $_POST['second_guide_id'] : null;
    $second_guide_lang = isset($_POST['second_guide_lang']) ? $_POST['second_guide_lang'] : null;
    $driver_id = $_POST['driver_id'];

    // Handle 'none' selection
    if ($guide_id === 'null') {
        $guide_id = null;
        $guide_lang = null; 
    }
    if ($second_guide_id === 'null') {
        $second_guide_id = null;
        $second_guide_lang = null;
    }

    // Validation check
    if (empty($group_id) || empty($driver_id)) {
        echo "Group ID or Driver ID is missing.";
        exit;
    }

    // Check if group_id exists
    $sqlCheckGroup = "SELECT COUNT(*) FROM groups WHERE group_id = :group_id";
    $stmtCheckGroup = $pdo->prepare($sqlCheckGroup);
    $stmtCheckGroup->execute(['group_id' => $group_id]);
    $groupExists = $stmtCheckGroup->fetchColumn();

    if ($groupExists == 0) {
        echo "Group ID does not exist.";
        exit;
    }

    // Fetch current values
    $sqlFetchCurrent = "SELECT guide_id, guide_lang, second_guide_id, second_guide_lang, driver_id FROM groups WHERE group_id = :group_id";
    $stmtFetchCurrent = $pdo->prepare($sqlFetchCurrent);
    $stmtFetchCurrent->execute(['group_id' => $group_id]);
    $currentValues = $stmtFetchCurrent->fetch(PDO::FETCH_ASSOC);

    if ($currentValues) {
        $isDifferent = ($guide_id != $currentValues['guide_id']) || 
                       ($guide_lang != $currentValues['guide_lang']) ||
                       ($second_guide_id != $currentValues['second_guide_id']) ||
                       ($second_guide_lang != $currentValues['second_guide_lang']) ||
                       ($driver_id != $currentValues['driver_id']);
        
        if (!$isDifferent) {
            echo "No changes made. The provided values are the same as the existing values.";
            exit;
        }
    }

    try {
        // Prepare the SQL update statement
        $sql = "UPDATE groups SET driver_id = :driver_id"; // Start with mandatory fields

        // Prepare parameters array
        $params = [
            'driver_id' => $driver_id,
            'group_id' => $group_id
        ];

        // Conditionally add first guide if not null
        if (!is_null($guide_id)) {
            $sql .= ", guide_id = :guide_id, guide_lang = :guide_lang";
            $params['guide_id'] = $guide_id;
            $params['guide_lang'] = $guide_lang;
        } else {
            $sql .= ", guide_id = NULL, guide_lang = NULL"; // Reset guide in DB
        }

        // Conditionally add second guide if not null
        if (!is_null($second_guide_id)) {
            $sql .= ", second_guide_id = :second_guide_id, second_guide_lang = :second_guide_lang";
            $params['second_guide_id'] = $second_guide_id;
            $params['second_guide_lang'] = $second_guide_lang;
        } else {
            $sql .= ", second_guide_id = NULL, second_guide_lang = NULL"; // Reset second guide in DB
        }

        $sql .= " WHERE group_id = :group_id";

        // Debugging output
        echo "SQL: $sql<br>";
        echo "Parameters: ";
        print_r($params);
        echo "<br>";

        // Prepare and execute the SQL statement
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Check if any rows were affected
        if ($stmt->rowCount() > 0) {
            header("Location: program.php?group_id=" . urlencode($group_id));
            exit();
        } else {
            echo "No changes made, please check if the values are different from the existing values.";
        }

    } catch (PDOException $e) {
        echo "Error assigning guides and driver: " . $e->getMessage();
    }
} else {
    echo "Invalid request method.";
}
?>