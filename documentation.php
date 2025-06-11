<?php
include('inc/header.php');

try {
    $stmt = $pdo->query("SELECT d.id, d.topic, u.Username as author, d.date 
                         FROM docs d 
                         JOIN users u ON d.author_id = u.Id 
                         ORDER BY d.date DESC");
    $docsData = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Error fetching documentation: " . $e->getMessage();
}
?>

<style>
.btn {
    background-color: #007bff;
    color: white;
    padding: 10px 15px;
    text-decoration: none;
    border-radius: 5px;
    margin-right: 5px; /* Adds spacing between buttons */
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn:hover {
    opacity: 0.9;
}

.actions {
    display: flex; /* Aligns buttons in a row */
    gap: 5px; /* Adds spacing between buttons */
}
</style>

<div class="container">
    <div class="wrapper-fit-centered">
        <h1>Training Topics</h1>
        <br>
        <a href="doc_new.php" class="btn">New Document</a>
        <br>
        <?php if (!empty($docsData)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Topic</th>
                        <th>Author</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($docsData as $doc): ?>
                        <tr>
                            <td>
                                <a href="documentation_view.php?id=<?php echo $doc['id']; ?>"><?php echo htmlspecialchars($doc['topic']); ?></a>
                            </td>
                            <td><?php echo htmlspecialchars($doc['author']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($doc['date'])); ?></td>
                            <td class="actions"> <!-- Added class to contain action buttons -->
                                <a href="doc_edit.php?id=<?php echo $doc['id']; ?>" class="btn"><i class="fa fa-edit"></i> Edit</a>
                                <a href="doc_delete.php?id=<?php echo $doc['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this documentation?');"><i class="fa fa-trash"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No documentation available.</p>
        <?php endif; ?>
    </div>
</div>

<?php include('inc/footer.php'); ?>