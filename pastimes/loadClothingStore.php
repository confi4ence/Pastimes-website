<?php

// loadClothingStore.php - Full DB rebuild from SQL file

require_once 'DBConn.php';

$messages = [];
$errors   = [];
$sqlFile  = 'myClothingStore.sql';

if (!file_exists($sqlFile)) {
    $errors[] = "$sqlFile not found.";
} else {
    $sql = file_get_contents($sqlFile);
    if ($conn->multi_query($sql)) {
        // Drain all result sets
        do {
            if ($res = $conn->store_result()) $res->free();
        } while ($conn->more_results() && $conn->next_result());

        $messages[] = "Database rebuilt successfully from $sqlFile.";
        $messages[] = "Note: Admin and user password hashes in the SQL file are placeholders. Run createTable.php to generate real hashed passwords for tblUser.";
    } else {
        $errors[] = "SQL execution error: " . $conn->error;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Load Clothing Store - Pasttime</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card card-gold">
        <div class="auth-logo">
            <div class="logo-icon" style="background:var(--gold);">
                <svg viewBox="0 0 24 24" fill="white"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14l-5-5 1.41-1.41L12 14.17l7.59-7.59L21 8l-9 9z"/></svg>
            </div>
            <h1>Database Loader</h1>
            <p>Pasttime Clothing Store</p>
        </div>

        <?php foreach ($messages as $msg): ?>
            <div class="msg msg-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>

        <?php foreach ($errors as $err): ?>
            <div class="msg msg-error"><?php echo htmlspecialchars($err); ?></div>
        <?php endforeach; ?>

        <div class="mt-4 flex-gap">
            <a href="createTable.php"   class="btn btn-primary">Also Run createTable.php</a>
            <a href="admin_login.php"   class="btn btn-dark">Admin Login</a>
        </div>
    </div>
</div>
</body>
</html>