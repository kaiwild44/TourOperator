<?php
include('inc/header.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic = $_POST['topic'];
    $text = $_POST['text'];
    $author_id = $_SESSION['user_id']; // Assuming you store the logged-in user's ID in the session

    try {
        $stmt = $pdo->prepare("INSERT INTO docs (topic, text, author_id) VALUES (?, ?, ?)");
        $stmt->execute([$topic, $text, $author_id]);
        header("Location: documentation.php"); // Redirect to the documentation overview page
        exit();
    } catch (PDOException $e) {
        echo "Error while creating documentation: " . $e->getMessage();
    }
}
?>

<div class="container">
    <h1>New Document</h1>
    <form method="POST">
        <div>
            <label for="topic">Topic:</label>
            <input type="text" name="topic" id="topic" required>
        </div>
        <div>
            <label for="text">Text:</label>
            <textarea name="text" id="text" required></textarea>
        </div>
        <button type="submit" class="btn">Create</button>
    </form>
</div>

<?php include('inc/footer.php'); ?>