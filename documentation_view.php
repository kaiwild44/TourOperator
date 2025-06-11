<?php
include('inc/header.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $stmt = $pdo->prepare("SELECT d.topic, d.text, u.Username as author, d.date 
                           FROM docs d 
                           JOIN users u ON d.author_id = u.Id 
                           WHERE d.id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
} catch (PDOException $e) {
    echo "Error fetching documentation: " . $e->getMessage();
}
?>

<div class="container">
    <?php if ($doc): ?>
        <h1><?php echo htmlspecialchars($doc['topic']); ?></h1>
        <p><strong>Author:</strong> <?php echo htmlspecialchars($doc['author']); ?></p>
        <p><strong>Date:</strong> <?php echo date('d-m-Y', strtotime($doc['date'])); ?></p>
        <div><?php echo nl2br(htmlspecialchars($doc['text'])); ?></div>
    <?php else: ?>
        <p>Document not found.</p>
    <?php endif; ?>
</div>

<?php include('inc/footer.php'); ?>