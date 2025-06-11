<?php
include('inc/header.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $stmt = $pdo->prepare("SELECT topic, text FROM docs WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
} catch (PDOException $e) {
    echo "Error fetching documentation for edit: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topic = $_POST['topic'];
    $text = $_POST['text'];

    try {
        $stmt = $pdo->prepare("UPDATE docs SET topic = ?, text = ? WHERE id = ?");
        $stmt->execute([$topic, $text, $id]);
        header("Location: documentation.php"); // Redirect to the documentation overview page
        exit();
    } catch (PDOException $e) {
        echo "Error while updating documentation: " . $e->getMessage();
    }
}
?>

<div class="container">
    <h1>Edit Documentation</h1>
    <form method="POST">
        <div>
            <label for="topic">Topic:</label>
            <input type="text" name="topic" id="topic" value="<?php echo htmlspecialchars($doc['topic']); ?>" required>
        </div>
        <div>
            <label for="text">Text:</label>
            <textarea name="text" id="text" required><?php echo htmlspecialchars($doc['text']); ?></textarea>
        </div>
        <button type="submit" class="btn">Update</button>
    </form>
</div>

<?php include('inc/footer.php'); ?>