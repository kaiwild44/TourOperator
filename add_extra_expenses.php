<?php
include('inc/db.php');

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = $_POST['group_id'];
    $extra_expense = $_POST['extra_expense'];
    $expense_assignment = $_POST['expense_assignment'];

    try {
        $stmt = $pdo->prepare("INSERT INTO extra_expenses (group_id, expense_assignment, expense_amount) VALUES (:group_id, :assignment, :amount)");
        $stmt->execute([
            ':group_id' => $group_id,
            ':assignment' => $expense_assignment,
            ':amount' => $extra_expense
        ]);

        header("Location: program.php?group_id=" . urlencode($group_id));
        exit();
    } catch (PDOException $e) {
        echo "Error adding extra expenses: " . $e->getMessage();
        exit();
    }
} else {
    // Redirect if accessed directly without POST data
    header("Location: index.php");
    exit();
}