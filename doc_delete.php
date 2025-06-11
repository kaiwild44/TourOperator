<?php
include('inc/header.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM docs WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: documentation.php"); // Redirect to the documentation overview page
        exit();
    } catch (PDOException $e) {
        echo "Error while deleting documentation: " . $e->getMessage();
    }
} else {
    echo "Invalid documentation ID specified.";
}
?>

<div class="container">
    <h1>Documentation Deleted</h1>
    <p>The selected documentation entry has been deleted successfully.</p>
    <a href="documentation.php">Go back to documentation overview.</a>
</div>

<?php include('inc/footer.php'); ?>